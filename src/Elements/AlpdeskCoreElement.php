<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Elements;

use Alpdesk\AlpdeskCore\Library\Mandant\AlpdescCoreBaseMandantInfo;

/**
 * @deprecated Deprecated since 1.0, to be removed in Contao 2.0; use the Symfony-Events instead => alpdesk.plugincall
 */
abstract class AlpdeskCoreElement
{
    // if true returnArray MUST have the key 'ngContent', 'ngStylesheetUrl' and 'ngScriptUrl'
    // ngScriptUrl must be an array
    // e.g.
    // return array(
    //    'ngContent' => '<h3>Hallo Welt</h3>',
    //    'ngStylesheetUrl' => array(
    //        0 => 'http://alpdesk.de/script.css'
    //    ),
    //    'ngScriptUrl' => array(
    //        0 => 'http://alpdesk.de/script.js'
    //    ),
    // )
    //
    protected bool $customTemplate = false;

    public function getCustomTemplate(): bool
    {
        return $this->customTemplate;
    }

    abstract public function execute(AlpdescCoreBaseMandantInfo $mandantInfo, array $data): array;
}
