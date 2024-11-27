<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Utils;

use Contao\BackendUser;
use Contao\Database;
use Contao\Date;
use Contao\System;
use Symfony\Component\Security\Core\User\UserInterface;

class Utils
{
    /**
     * @param UserInterface $backendUser
     * @return void
     */
    public static function mergeUserGroupPermissions(UserInterface $backendUser): void
    {
        if ($backendUser instanceof BackendUser) {

            if ($backendUser->inherit === 'group' || $backendUser->inherit === 'extend') {

                $time = Date::floorToMinute();

                foreach ((array)$backendUser->groups as $id) {

                    $objGroup = Database::getInstance()->prepare("SELECT alpdeskcorelogs_enabled FROM tl_user_group WHERE id=? AND disable!='1' AND (start='' OR start<='$time') AND (stop='' OR stop>'$time')")->limit(1)->execute($id);
                    if ($objGroup->numRows > 0 && (int)$backendUser->alpdeskcorelogs_enabled === 0) {
                        $backendUser->alpdeskcorelogs_enabled = $objGroup->alpdeskcorelogs_enabled;
                    }
                }

            }

        }

    }

    /**
     * @param mixed $strBuffer
     * @param bool $blnCache
     * @return string
     */
    public static function replaceInsertTags(mixed $strBuffer, bool $blnCache = true): string
    {
        try {

            $parser = System::getContainer()->get('contao.insert_tag.parser');

            if ($blnCache) {
                return $parser->replace((string)$strBuffer);
            }

            return $parser->replaceInline((string)$strBuffer);

        } catch (\Exception) {
            return (string)$strBuffer;
        }

    }

}
