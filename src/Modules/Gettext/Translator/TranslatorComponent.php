<?php

namespace Bitendian\TBP\I18n\Modules\Gettext\Translator;

use Bitendian\TBP\I18n\Services\LanguageService;
use Bitendian\TBP\UI\AbstractComponent;
use Bitendian\TBP\UI\Templater;
use Bitendian\TBP\Utils\SystemMessages;
use Bitendian\TBP\Utils\Config;

use Gettext\Translation;
use Gettext\Translations;


class TranslatorComponent extends AbstractComponent
{
    const ANNOTATIONS_FILE = 'annotations.json';

    public $localeAbsolutePath = null;
    public $cols = array();
    public $rows = array();

    private $languages = [];

    /**
     * @var Translations
     */
    public $masterDictionary = null;
    public $translations = array();
    public $annotations;

    public function action(&$params)
    {
        $this->initAttributesFromParams($params);
        if ($this->localeAbsolutePath == null) {
            SystemMessages::addError(_('undefined locale absolute path'));
            return;
        }

        $this->loadFromPoFiles();
        $files = array_keys($this->translations);
        $this->annotations = array();
        foreach ($this->masterDictionary as &$master) {
            foreach ($files as &$file) {
                $name = '_' . sha1($file . $master->getOriginal());
                /**
                 * @var Translation $translation
                 */
                $translation = $this->translations[$file]->find(null, $master->getOriginal());
                if ($translation) {
                    $translation->setTranslation($params[$name]);
                } else {
                    $translation = $this->translations[$file]->insert(null, $master->getOriginal());
                    $translation->setTranslation($params[$name]);
                }
            }
            /* Annotations */
            $name = '_' . sha1(self::ANNOTATIONS_FILE . $master->getOriginal());
            $this->annotations[$master->getOriginal()] = $params[$name];
        }
        foreach ($files as &$file) {
            $this->translations[$file]->toPoFile($file);
            $this->translations[$file]->toMoFile(str_replace('.po', '.mo', $file));
        }

        /* Annotations */
        $this->storeAnnotations();

        SystemMessages::addInfo(_('translations updated successfully'));
    }

    public function fetch(&$params)
    {
        $this->initAttributesFromParams($params);
        if ($this->localeAbsolutePath == null) {
            SystemMessages::addError(_('undefined locale absolute path'));
            return;
        }
        $this->loadFromPoFiles();
        $this->getAnnotations();
    }

    public function render()
    {
        $this->cols = array();
        $ctx = new \stdClass();
        $ctx->key = 'Key';
        $this->cols[] = new Templater(__DIR__ . DIRECTORY_SEPARATOR . 'Col.template', $ctx);
        foreach ($this->translations as $file => &$translation) {
            $languageLocale = str_replace($this->localeAbsolutePath . DIRECTORY_SEPARATOR, '', $file);
            $languageLocale = str_replace(DIRECTORY_SEPARATOR . 'LC_MESSAGES' . DIRECTORY_SEPARATOR . 'default.po', '', $languageLocale);
            $languageName = 'unknown';
            foreach ($this->languages as &$language) {
                if ($language->Locale == $languageLocale) {
                    $languageName = $language->Name;
                }
            }
            $ctx->key = $languageName . ' (' . $languageLocale . ')';
            $this->cols[] = new Templater(__DIR__ . DIRECTORY_SEPARATOR . 'Col.template', $ctx);
        }
        $ctx = new \stdClass();
        $ctx->key = 'Annotations';
        $this->cols[] = new Templater(__DIR__ . DIRECTORY_SEPARATOR . 'Col.template', $ctx);

        $this->masterDictionary->uasort(
            function($a, $b) {
                /**
                 * @var Translation $a
                 * @var Translation $b
                 */
                if (strtolower($a->getOriginal()) == strtolower($b->getOriginal())) {
                    return 0;
                }
                return strtolower($a->getOriginal()) < strtolower($b->getOriginal()) ? -1 : 1;
            }
        );
        $id = 0;
        foreach ($this->masterDictionary as &$masterEntry) {
            $ctx = new \stdClass();
            $ctx->key = $masterEntry->getOriginal();

            $row = new \stdClass();
            $row->cells = array();
            $row->cells[] = (new Templater(__DIR__ . DIRECTORY_SEPARATOR . 'KeyCell.template', $ctx));
            foreach ($this->translations as $file => &$translation) {
                $ctx->value = '';
                $ctx->name = '_' . sha1($file . $masterEntry->getOriginal());
                if ($translation->find(null, $ctx->key)) {
                    $ctx->value = htmlspecialchars($translation->find(null, $ctx->key)->getTranslation());
                }
                $row->cells[] = (new Templater(__DIR__ . DIRECTORY_SEPARATOR . 'TranslationCell.template', $ctx));
            }

            /* Annotations */
            $ctx->value = isset($this->annotations[$masterEntry->getOriginal()]) ? $this->annotations[$masterEntry->getOriginal()] : '';
            $ctx->name = '_' . sha1(self::ANNOTATIONS_FILE . $masterEntry->getOriginal());
            $row->cells[] = (new Templater(__DIR__ . DIRECTORY_SEPARATOR . 'TranslationCell.template', $ctx));

            $row->idRow = $id;
            $this->rows[] = (new Templater(__DIR__ . DIRECTORY_SEPARATOR . 'Row.template', $row));
            $id += 1;
        }
        echo new Templater(__DIR__ . DIRECTORY_SEPARATOR . 'Main.template', $this);
    }

    private function initAttributesFromParams(&$params)
    {
        $config = new Config( __CONFIG_DIR__);
        $appConfig = $config->getConfig('language');

        $this->localeAbsolutePath = __BASE_PATH__ . DIRECTORY_SEPARATOR . $appConfig->localePath;

        $serviceLanguages = new LanguageService();
        $this->languages = $serviceLanguages->getActiveLanguages();
    }

    private function loadFromPoFiles()
    {
        $files = [];
        foreach ($this->languages as &$language) {
            $files[] = $this->localeAbsolutePath . DIRECTORY_SEPARATOR . $language->Locale . DIRECTORY_SEPARATOR . 'LC_MESSAGES' . DIRECTORY_SEPARATOR . 'default.po';
        }

        $this->masterDictionary = new Translations();

        foreach ($files as &$file) {
            if (file_exists($file)) {
                $translations = Translations::fromPoFile($file);
                $this->translations[$file] = $translations;
                $this->masterDictionary->mergeWith($translations);
            }
        }
    }

    private function getAnnotations()
    {
        $this->annotations = array();
        $annotationsFile = $this->localeAbsolutePath . DIRECTORY_SEPARATOR . self::ANNOTATIONS_FILE;
        if (file_exists($annotationsFile)) {
            $this->annotations = json_decode(file_get_contents($annotationsFile), true);
        }
    }

    private function storeAnnotations()
    {
        $annotationsFile = $this->localeAbsolutePath . DIRECTORY_SEPARATOR . self::ANNOTATIONS_FILE;
        $fFile = fopen($annotationsFile, "w");
        fwrite($fFile, json_encode($this->annotations));
        fclose($fFile);
    }

}
