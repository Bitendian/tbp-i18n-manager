<?php

namespace Bitendian\TBP\I18n\Modules\Gettext\ImportFromXLS;

use Bitendian\TBP\I18n\Services\LanguageService;
use Bitendian\TBP\UI\AbstractComponent;
use Bitendian\TBP\UI\Templater;
use Bitendian\TBP\Utils\SystemMessages;
use Bitendian\TBP\Utils\Config;

use PhpOffice\PhpSpreadsheet\IOFactory;

use Gettext\Translations;
use Gettext\Translation;

class ImportFromXLSComponent extends AbstractComponent
{
    const ANNOTATIONS_FILE = 'annotations.json';

    public $localeAbsolutePath = null;
    private $languages = [];

    /**
     * @var \Gettext\Translations
     */
    public $masterDictionary = null;

    public $translationsByFile = [];
    public $translationsByLocale = [];
    public $master = null;
    public $annotations;

    public $objPHPExcel;

    public function action(&$params)
    {
        $this->initAttributesFromParams($params);
        if ($this->VerifyXLSFile()) {
            if (is_uploaded_file($_FILES['File']['tmp_name'])) {
                $this->loadDataFromXLS($_FILES['File']['tmp_name']);
                if ($this->VerifyXLSHeaderFormat()) {
                    $this->VerifyXLSBodyTranslations();
                }
            }
        } else {
            SystemMessages::addError(_('The file must be an xlsx format'));
        }
    }

    public function fetch(&$params)
    {
        $this->initAttributesFromParams($params);
        if ($this->localeAbsolutePath == null) {
            SystemMessages::addError(_('undefined locale absolute path'));
            return;
        }
    }

    public function render()
    {
        $templater = new Templater(__DIR__ . DIRECTORY_SEPARATOR . 'Main.template', $this);
        return $templater->render();
    }

    private function initAttributesFromParams(&$params)
    {
        $config = new Config( __CONFIG_DIR__);
        $appConfig = $config->getConfig('language');

        $this->localeAbsolutePath = __BASE_PATH__ . DIRECTORY_SEPARATOR . $appConfig->localePath;

        $serviceLanguages = new LanguageService();
        $this->languages = $serviceLanguages->getActiveLanguages();
    }

    private function VerifyXLSFile()
    {
        $type = $_FILES['File']['type'];
        if ($type == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        {
            return true;
        } else {
            return false;
        }
    }

    private function LoadDataFromXLS($inputFileName)
    {
        //  Read your Excel workbook
        try {
            $inputFileType = IOFactory::identify($inputFileName);
            $objReader =IOFactory::createReader($inputFileType);
            $this->objPHPExcel = $objReader->load($inputFileName);
        } catch(\Exception $e) {
            die('Error loading file "'.pathinfo($inputFileName,PATHINFO_BASENAME).'": '.$e->getMessage());
        }
    }

    private function VerifyXLSHeaderFormat()
    {
        $sheet = $this->objPHPExcel->getSheet(0);
        $highestColumn = $sheet->getHighestDataColumn();
        $rowHeader = 1;
        $header = $sheet->rangeToArray('A' . $rowHeader . ':' . $highestColumn . $rowHeader,
            NULL,
            TRUE,
            FALSE)[0];

        /* Fixing verification header */
        $headerTitles = array();
        $headerTitles[] = 'Key';
        foreach ($this->languages as $language) {
            $headerTitles[] = '(' . $language->Locale . ')';
        }
        $headerTitles[] = 'Annotations';
        /* Verify header length */
        $lengthHeader =  count($header);
        $lengthHeaderTitles = count($headerTitles);
        if ($lengthHeader != $lengthHeaderTitles) {
            SystemMessages::addError(_('Excel not have the accurate number of columns'));
            return false;
        }
        /* Verify header items */
        for ( $i=0; $i< $lengthHeaderTitles; $i++) {
            if ($header[$i] != $headerTitles[$i]) {
                SystemMessages::addError(_('Excel doesn\'t comply with the format'));
                return false;
            }
        }

        return true;
    }

    private function loadFromPoFiles()
    {
        $files = [];
        $locales = [];
        foreach ($this->languages as &$language) {
            $files[] = $this->localeAbsolutePath . DIRECTORY_SEPARATOR . $language->Locale . DIRECTORY_SEPARATOR . 'LC_MESSAGES' . DIRECTORY_SEPARATOR . 'default.po';
            $locales[] = $language->Locale;
        }

        $this->masterDictionary = new Translations();

        $pos = 0;
        foreach ($files as &$file) {
            if (file_exists($file)) {
                $translations = Translations::fromPoFile($file);
                $this->translationsByFile[$file] = $translations;
                $this->translationsByLocale[$locales[$pos]] = $translations;
                $this->masterDictionary->mergeWith($translations);
                $pos++;
            }
        }
    }

    private function VerifyXLSRepeatedKeys()
    {
        $keys = array();
        $sheet = $this->objPHPExcel->getSheet(0);

        $highestRow = $sheet->getHighestDataRow();
        $highestColumn = $sheet->getHighestDataColumn();
        /* load info Keys from Excel */
        for ($row = 2; $row <= $highestRow; $row++){
            $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,
                NULL,
                TRUE,
                FALSE)[0];

            $keys[] = $rowData[0];
        }
        /* search for repeated Keys */
        $repeatedKeys = array_count_values($keys);
        foreach ($repeatedKeys as $key => $value) {
            if ($value > 1){
                return false;
            }
        }
        return true;
    }

    private function storeAnnotations()
    {
        $annotationsFile = $this->localeAbsolutePath . DIRECTORY_SEPARATOR . self::ANNOTATIONS_FILE;
        $fFile = fopen($annotationsFile, "w");
        fwrite($fFile, json_encode($this->annotations));
        fclose($fFile);
    }

    private function VerifyXLSBodyTranslations() {

        $this->loadFromPoFiles();
        $files = array_keys($this->translationsByFile);
        $locales = array_keys($this->translationsByLocale);
        $this->annotations = array();

        if (!$this->VerifyXLSRepeatedKeys()) {
            SystemMessages::addError(_('Exist keys repeated in the Excel document.'));
            return false;
        }

        /**
         * @var Translation $translation
         */
        $sheet = $this->objPHPExcel->getSheet(0);

        $highestRow = $sheet->getHighestDataRow();
        $highestColumn = $sheet->getHighestDataColumn();

        for ($row = 2; $row <= $highestRow; $row++){
            $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,
                NULL,
                TRUE,
                FALSE)[0];

            $posLocale = 1;
            foreach ($locales as $locale) {
                $translation = $this->translationsByLocale[$locale]->find(null, $rowData[0]);
                if ($translation) {
                    $translation->setTranslation($rowData[$posLocale]);
                } else {
                    $translation = $this->translationsByLocale[$locale]->insert(null, $rowData[0]);
                    $translation->setTranslation($rowData[$posLocale]);
                }
                $posLocale++;
            }
            /* Annotations */
            $this->annotations[$rowData[0]] = $rowData[count($this->languages)+1];
        }

        /* Store PO, MO files */
        foreach ($files as &$file) {
            $this->translationsByFile[$file]->toPoFile($file);
            $this->translationsByFile[$file]->toMoFile(str_replace('.po', '.mo', $file));
        }

        /* Store Annotations */
        $this->storeAnnotations();
    }
}
