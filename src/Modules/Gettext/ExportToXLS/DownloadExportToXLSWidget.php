<?php

namespace Bitendian\TBP\I18n\Modules\Gettext\ExportToXLS;

use Bitendian\TBP\I18n\Services\LanguageService;
use Bitendian\TBP\Utils\Config;
use Gettext\Translation;
use Gettext\Translations;


class DownloadExportToXLSWidget extends AbstractExportExcelWidget
{
    const ANNOTATIONS_FILE = 'annotations.json';

    public $locale;
    public $localeAbsolutePath;

    public $languages;

    public $translations;
    /**
     * @var Translations
     */
    public $masterDictionary;

    public $annotations;

    public $rows;

    public function fetch(&$params)
    {
        $this->initAttributesFromParams($params);

        /* get masterDictionary and annotations */
        $this->loadFromPoFiles();
        $this->getAnnotations();

        $this->uasortDictionary();
        $this->excelRowsBodyFromMasterDictionary();

        $this->structureXLS();
    }

    private function initAttributesFromParams(&$params)
    {
        $this->locale = isset($params['locale']) ? $params['locale'] : null;

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
            $files[] = $this->localeAbsolutePath . DIRECTORY_SEPARATOR .  $language->Locale . DIRECTORY_SEPARATOR . 'LC_MESSAGES' . DIRECTORY_SEPARATOR . 'default.po';
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

    /**
     *
     */
    private function uasortDictionary()
    {
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
    }

    private function excelRowsBodyFromMasterDictionary()
    {
        $id = 0;
        $this->rows = array();
        /**
         * @var Translation $masterEntry
         */
        foreach ($this->masterDictionary as &$masterEntry) {
            $ctx = new \stdClass();
            $ctx->key = $masterEntry->getOriginal();

            /* language cells */
            $row = new \stdClass();
            $row->cells = array();
            $row->cells[0] = $ctx->key;
            /**
             * @var Translations $translation
             */
            $i = 1;
            foreach ($this->translations as $file => $translation) {
                $row->cells[$i] = new \stdClass();
                $row->cells[$i] = $translation->find(null, $ctx->key)->getTranslation();
                $i++;
            }

            /* annotations */
            $row->cells[$i] = isset($this->annotations[$masterEntry->getOriginal()]) ? $this->annotations[$masterEntry->getOriginal()] : '';

            $row->idRow = $id;
            $this->rows[$id] = $row;
            $id += 1;
        }
    }

    private function structureXLS()
    {
        $this->title = _('Translations');

        $this->headers = array();
        $this->headers[] = 'Key';

        foreach ($this->languages as $language) {
            $this->headers[] = '(' . $language->Locale . ')';
        }

        $this->headers[] = 'Annotations';

        $this->body = array();
        foreach ($this->rows as $row) {
            $rowBody = array();
            foreach ($row->cells as $cell) {
                $rowBody[] = $cell;
            }
            $this->body[] = $rowBody;
        }
    }
}