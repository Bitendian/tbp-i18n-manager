<?php

namespace Bitendian\TBP\I18n\Modules\Gettext\SelectLanguages;

use Bitendian\TBP\I18n\Services\LanguageService;
use Bitendian\TBP\UI\AbstractComponent;
use Bitendian\TBP\UI\Templater;

use Bitendian\TBP\Utils\SystemMessages;

class SelectLanguagesComponent extends AbstractComponent
{
    public $languages = array();
    public $rows = array();

    public $languageConfig;

    public function action(&$params)
    {
        $this->initAttributesFromParams($params);

        $operation = 0;
        foreach ($this->languages as &$language) {
            $service = new LanguageService();
            $languageLocale = $params[$language->Name];
            $languageId = $service->getLanguageByLocale($languageLocale)->LanguageId;
            $stateActive = isset($params[$language->Name . 'State']) ? 1 : 0 ;
            if ($service->setActiveLanguage($languageId, $stateActive)) {
                $operation++;
            }
        }

        if ($operation == count($this->languages)) {
            SystemMessages::addInfo(_('Selected languages updated successfully'));
        }
    }

    public function fetch(&$params)
    {
        $this->initAttributesFromParams($params);
    }

    public function render()
    {
        foreach( $this->languages as $language) {
            $ctx = new \stdClass();
            $ctx->Locale = $language->Locale;
            $ctx->Id = $language->LanguageId;
            $ctx->Name = $language->Name;
            $ctx->FullName = $language->Name . "(" . $language->Locale . ")";
            $ctx->Checked = $language->Active ? 'checked' : '';
            $ctx->Value = $language->Active;

            $this->rows[] = new Templater ( __DIR__ . DIRECTORY_SEPARATOR . 'SelectLanguage.template', $ctx);
        }

        echo new Templater( __DIR__ . DIRECTORY_SEPARATOR . 'SelectLanguages.template', $this);
    }

    private function initAttributesFromParams($params)
    {
        $this->languageConfig = 'language';

        $serviceLanguage = new LanguageService();
        $this->languages = $serviceLanguage->getAllLanguages();
    }
}
