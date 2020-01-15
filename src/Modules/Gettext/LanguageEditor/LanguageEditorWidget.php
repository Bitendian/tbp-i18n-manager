<?php

namespace Bitendian\TBP\I18n\Modules\Gettext\LanguageEditor;

use Bitendian\TBP\UI\AbstractWidget;
use Bitendian\TBP\UI\Templater;

use Bitendian\TBP\I18n\Modules\Gettext\Create\CreateComponent;
use Bitendian\TBP\I18n\Modules\Gettext\ExportToXLS\ExportToXLSWidget;
use Bitendian\TBP\I18n\Modules\Gettext\ImportFromXLS\ImportFromXLSComponent;
use Bitendian\TBP\I18n\Modules\Gettext\Parser\ParserComponent;
use Bitendian\TBP\I18n\Modules\Gettext\SelectLanguages\SelectLanguagesComponent;
use Bitendian\TBP\I18n\Modules\Gettext\Translator\TranslatorComponent;

class LanguageEditorWidget extends AbstractWidget
{
    public $translator;
    public $parser;
    public $exportToXLS;
    public $importFromXLS;
    public $selectLanguages;
    public $createLanguageComponent;

    public function fetch(&$params)
    {
        $this->translator = new TranslatorComponent();
        $this->translator->fetch($params);

        $this->parser = new ParserComponent();
        $this->parser->fetch($params);

        $this->importFromXLS = new ImportFromXLSComponent();
        $this->importFromXLS->fetch($params);

        $this->exportToXLS = new ExportToXLSWidget();
        $this->exportToXLS->fetch($params);

        $this->selectLanguages = new SelectLanguagesComponent();
        $this->selectLanguages->fetch($params);

        $this->createLanguageComponent = new CreateComponent();
        $this->createLanguageComponent->fetch($params);
    }

    public function render()
    {
        echo new Templater(__DIR__ . DIRECTORY_SEPARATOR . 'LanguageEditor.template', $this);
    }
}
