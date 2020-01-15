<?php

namespace Bitendian\TBP\I18n\Modules\Gettext\ExportToXLS;

use Bitendian\TBP\UI\AbstractWidget;
use Bitendian\TBP\UI\Templater;
use Bitendian\TBP\Utils\Router;

class ExportToXLSWidget extends AbstractWidget
{
    public $locale;
    public $downloadExportXLS;

    public function fetch(&$params)
    {
        $this->initAttributesFromParams($params);
        $this->downloadExportXLS = Router::getRoute('download-export-xls', $this->locale);
    }

    public function render()
    {
        $templater = new Templater( __DIR__ . DIRECTORY_SEPARATOR . 'Main.template', $this);
        echo $templater;
    }

    private function initAttributesFromParams($params)
    {
        $this->locale = isset($params['locale']) ? $params['locale'] : null;
    }
}