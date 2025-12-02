<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Backend;

use Alpdesk\AlpdeskCore\Library\Cryption\Cryption;
use Alpdesk\AlpdeskCore\Security\AlpdeskcoreUserProvider;
use Contao\DataContainer;

readonly class AlpdeskCoreDcaUtils
{
    public function __construct(
        private AlpdeskcoreUserProvider $userProvider
    )
    {
    }

    /**
     * @param mixed $row
     * @return string
     */
    public function sessionsLabel(mixed $row): string
    {
        try {
            $validateAndVerify = $this->userProvider->getJwtToken()->validateWithJti($row['token'], AlpdeskcoreUserProvider::createJti($row['username']));
        } catch (\Exception) {
            $validateAndVerify = false;
        }

        $color = ($validateAndVerify === true ? 'green' : 'red');

        return '<span style="display:inline-block;width:20px;height:20px;margin-right:10px;background-color:' . $color . ';">&nbsp;</span>' . $row['username'] . ' - ' . \substr($row['token'], 0, 25) . ' ...';
    }

    /**
     * @param mixed $varValue
     * @param DataContainer $dc
     * @return string
     */
    public function generateFixToken(mixed $varValue, DataContainer $dc): string
    {
        if ($varValue === null || $varValue === '') {

            $currentRecord = $dc->getCurrentRecord();
            if (!\is_array($currentRecord)) {
                return '';
            }

            $username = $currentRecord['username'] ?? null;
            if (!\is_string($username) || $username === '') {
                return '';
            }

            try {
                $varValue = $this->userProvider->createToken($username, 0);
            } catch (\Exception $ex) {
                $varValue = $ex->getMessage();
            }
        }

        return $varValue;
    }

    /**
     * @param mixed $varValue
     * @return string
     * @throws \Exception
     */
    public function generateEncryptPassword(mixed $varValue): string
    {
        if ($varValue === '') {
            return $varValue;
        }

        return (new Cryption(true))->safeEncrypt($varValue);
    }

    /**
     * @param mixed $varValue
     * @param DataContainer $dc
     * @return string
     * @throws \Exception
     */
    public function regenerateEncryptPassword(mixed $varValue, DataContainer $dc): string
    {
        if ($varValue === '') {
            return $varValue;
        }

        $currentRecord = $dc->getCurrentRecord();
        if (!\is_array($currentRecord)) {
            return $varValue;
        }

        return (new Cryption(true))->safeDecrypt($varValue);

    }

}
