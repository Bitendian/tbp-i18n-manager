<?php

namespace Bitendian\TBP\I18n\Modules\Gettext\Create;

use Bitendian\TBP\I18n\Services\LanguageService;
use Bitendian\TBP\UI\AbstractComponent;
use Bitendian\TBP\UI\Templater;
use Bitendian\TBP\Utils\Router;
use Bitendian\TBP\Utils\SystemMessages;

class CreateComponent extends AbstractComponent
{
    public $name;
    public $locale;
    public $createModal;
    public $angularController;

    public function action(&$params)
    {
        $this->initAttributesFromParams($params);
        $languageService = new LanguageService();
        if ($languageService->createLanguage($this->name, $this->locale)) {
            SystemMessages::addInfo(_('Language created successfully'));
            SystemMessages::save();
            return Router::getRoute('translations', $params['locale']);
        }

        return '';
    }

    public function fetch(&$params)
    {
    }

    public function render()
    {
        $this->createModal = new Templater(__DIR__ . DIRECTORY_SEPARATOR . 'CreateModal.template', $this);
        $this->angularController = new Templater(__DIR__ . DIRECTORY_SEPARATOR . 'CreateController.js', $this);
        echo new Templater(__DIR__ . DIRECTORY_SEPARATOR . 'Create.template', $this);
    }

    public function initAttributesFromParams(&$params)
    {
        $this->name = isset($params['Name']) ? $params['Name'] : '';
        $this->locale = isset($params['Locale']) ? $params['Locale'] : '';
    }
}
