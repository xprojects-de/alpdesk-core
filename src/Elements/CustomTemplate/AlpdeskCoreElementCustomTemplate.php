<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Elements\CustomTemplate;

use Alpdesk\AlpdeskCore\Elements\AlpdeskCoreElement;
use Alpdesk\AlpdeskCore\Library\Mandant\AlpdescCoreBaseMandantInfo;
use Contao\Environment;
use Contao\FrontendTemplate;

class AlpdeskCoreElementCustomTemplate extends AlpdeskCoreElement {

  protected bool $customTemplate = true;

  public function execute(AlpdescCoreBaseMandantInfo $mandantInfo, array $data): array {
    $template = new FrontendTemplate('alpdeskcore_customTemplate');
    $template->title = 'Hello from Alpdesk';
    $template->data = $data;
    return array(
        'ngContent' => $template->parse(),
        'ngStylesheetUrl' => array(
            0 => Environment::get('base') . 'bundles/alpdeskcore/customTemplate/customTemplate.css'
        ),
        'ngScriptUrl' => array(
            0 => Environment::get('base') . 'assets/jquery/js/jquery.js',
            1 => Environment::get('base') . 'bundles/alpdeskcore/customTemplate/customTemplate.js'
        )
    );
  }

}
