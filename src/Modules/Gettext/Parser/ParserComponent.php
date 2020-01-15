<?php

namespace Bitendian\TBP\I18n\Modules\Gettext\Parser;

use Bitendian\TBP\I18n\Services\LanguageService;
use Bitendian\TBP\UI\AbstractComponent;
use Bitendian\TBP\UI\Templater;
use Bitendian\TBP\Utils\SystemMessages;
use Bitendian\TBP\Utils\Config;

use Gettext\Extractors\PhpCode;
use Gettext\Translations;


class ParserComponent extends AbstractComponent
{
    public $localeAbsolutePath = null;
    public $sourceAbsolutePaths = array();
    private $languages = [];

    public $translations = [];
    public $master = null;

    public function action(&$params)
    {
        $this->initAttributesFromParams($params);
        if ($this->localeAbsolutePath == null) {
            SystemMessages::addError(_('Ruta absoluta de la configuración regional no definida'));
            return;
        }

        $parsedTranslations = new Translations();

        $targets = PhpCode::$options;
        $targets['functions']['_'] = 'gettext';
        $targets['functions']['url'] = 'gettext';

        foreach($this->sourceAbsolutePaths as $sourceAbsolutePath) {
            $contents = $this->getAllPhpFilesFromFolder($sourceAbsolutePath);
            foreach ($contents as &$content) {
                $translations = Translations::fromPhpCodeFile($content, $targets);
                $parsedTranslations->mergeWith($translations);
            }

            $contents = $this->getAllTemplateFilesFromFolder($sourceAbsolutePath);
            foreach ($contents as &$content) {
                $template = new Templater($content);
                $labels = $template->getGettextTags();
                $translations = new Translations();
                foreach ($labels as &$label) {
                    $translations->insert(null, $label);
                }
                $parsedTranslations->mergeWith($translations);
            }
        }

        $this->addTranslationsToSiteLanguageFiles($parsedTranslations);
    }

    public function fetch(&$params)
    {
        $this->initAttributesFromParams($params);
        if ($this->localeAbsolutePath == null) {
            SystemMessages::addError(_('Ruta absoluta de la configuración regional no definida'));
            return;
        }
    }

    public function render()
    {
        echo new Templater(__DIR__ . DIRECTORY_SEPARATOR . 'Main.template', $this);
    }

    private function initAttributesFromParams(&$params)
    {
        $config = new Config( __CONFIG_DIR__);
        $appConfig = $config->getConfig('language');

        $this->localeAbsolutePath = __BASE_PATH__ . DIRECTORY_SEPARATOR . $appConfig->localePath;
        $this->sourceAbsolutePaths = __BASE_PATH__ . DIRECTORY_SEPARATOR . $appConfig->sourcePaths;

        $this->localeAbsolutePath = __BASE_PATH__ . DIRECTORY_SEPARATOR . $appConfig->localePath;
        $this->sourceAbsolutePaths = explode(",", $appConfig->sourcePaths);
        foreach($this->sourceAbsolutePaths as &$sourceAbsolutePath){
            $sourceAbsolutePath = __BASE_PATH__ . DIRECTORY_SEPARATOR . $sourceAbsolutePath;
        }

        $serviceLanguages = new LanguageService();
        $this->languages = $serviceLanguages->getActiveLanguages();
    }

    private function getAllPhpFilesFromFolder($folder)
    {
        $files = array();
        if ($handle = \opendir($folder)) {
            while (false !== ($entry = \readdir($handle))) {
                if ($entry != '.' && $entry != '..') {
                    $current = $folder . DIRECTORY_SEPARATOR . $entry;
                    if (is_dir($current)) {
                        $files = array_merge($files, $this->getAllPhpFilesFromFolder($current));
                    } else {
                        if ($this->endsWith($entry, '.php')) {
                            $files[] = $current;
                        }
                    }
                }
            }
            closedir($handle);
        }
        return $files;
    }

    private function getAllTemplateFilesFromFolder($folder)
    {
        $files = array();
        if ($handle = \opendir($folder)) {
            while (false !== ($entry = \readdir($handle))) {
                if ($entry != '.' && $entry != '..') {
                    $current = $folder . DIRECTORY_SEPARATOR . $entry;
                    if (is_dir($current)) {
                        $files = array_merge($files, $this->getAllTemplateFilesFromFolder($current));
                    } else {
                        if ($this->endsWith($entry, '.template') || $this->endsWith($entry, '.jstemplate')) {
                            $files[] = $current;
                        }
                    }
                }
            }
            closedir($handle);
        }
        return $files;
    }

    private function endsWith($string, $test)
    {
        $strlen = \strlen($string);
        $testlen = \strlen($test);
        if ($testlen > $strlen) {
            return false;
        }
        return \substr_compare($string, $test, $strlen - $testlen, $testlen) === 0;
    }

    private function addTranslationsToSiteLanguageFiles(&$parsedTranslations)
    {
        $this->loadFromPoFiles();
        $this->mergeParsedTranslationsWithMaster($parsedTranslations);
        $this->populateMasterKeysToLanguageTranslations();
        $this->writeToPoFiles();
    }

    private function loadFromPoFiles()
    {
        $files = [];
        foreach ($this->languages as &$language) {
            $files[] = $this->localeAbsolutePath . DIRECTORY_SEPARATOR . $language->Locale . DIRECTORY_SEPARATOR . 'LC_MESSAGES' . DIRECTORY_SEPARATOR . 'default.po';
        }

        $this->master = new Translations();

        foreach ($files as &$file) {
            if (!file_exists($file)) {
                mkdir(dirname($file), 0777, true);
                file_put_contents($file, PHP_EOL); // create new empty .po file
            }
            $translations = Translations::fromPoFile($file);
            $this->translations[$file] = $translations;
            $this->master->mergeWith($translations);
        }
    }

    /**
     * @param $parsedTranslations Translations
     */
    private function mergeParsedTranslationsWithMaster(&$parsedTranslations)
    {
        foreach ($parsedTranslations as $parsedTranslation) {
            $translation = $this->master->find(null, $parsedTranslation->getOriginal());
            if (!$translation) {
                $translation = $this->master->insert(null, $parsedTranslation->getOriginal());
                $translation->setTranslation($parsedTranslation->getOriginal());
            }
        }
    }

    private function populateMasterKeysToLanguageTranslations()
    {
        $files = array_keys($this->translations);
        foreach ($this->master as &$master) {
            foreach ($files as &$file) {
                $translation = $this->translations[$file]->find(null, $master->getOriginal());
                if (!$translation) {
                    $translation = $this->translations[$file]->insert(null, $master->getOriginal());
                    $translation->setTranslation('');
                }
            }
        }
    }

    private function writeToPoFiles()
    {
        $files = array_keys($this->translations);
        foreach ($files as &$file) {
            $this->translations[$file]->toPoFile($file);
        }
    }
}
