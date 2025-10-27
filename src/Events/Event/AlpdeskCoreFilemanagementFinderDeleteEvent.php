<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Events\Event;

use Alpdesk\AlpdeskCore\Library\Mandant\AlpdescCoreBaseMandantInfo;
use Symfony\Contracts\EventDispatcher\Event;

class AlpdeskCoreFilemanagementFinderDeleteEvent extends Event
{
    public const string NAME = 'alpdesk.finder_delete';

    private array $inputData;
    private bool $resultData;
    private AlpdescCoreBaseMandantInfo $mandant;

    public function __construct(array $inputData, bool $resultData, AlpdescCoreBaseMandantInfo $mandant)
    {
        $this->inputData = $inputData;
        $this->resultData = $resultData;
        $this->mandant = $mandant;
    }

    public function getInputData(): array
    {
        return $this->inputData;
    }

    public function setInputData(array $inputData): void
    {
        $this->inputData = $inputData;
    }

    public function getResultData(): bool
    {
        return $this->resultData;
    }

    public function setResultData(bool $resultData): void
    {
        $this->resultData = $resultData;
    }

    public function getMandant(): AlpdescCoreBaseMandantInfo
    {
        return $this->mandant;
    }

    public function setMandant(AlpdescCoreBaseMandantInfo $mandant): void
    {
        $this->mandant = $mandant;
    }
}
