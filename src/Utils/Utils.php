<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Utils;

use Contao\BackendUser;
use Contao\Database;
use Contao\Date;

class Utils
{
    public static function mergeUserGroupPermissions()
    {
        $backendUser = BackendUser::getInstance();

        if ($backendUser->inherit == 'group' || $backendUser->inherit == 'extend') {

            $time = Date::floorToMinute();

            foreach ((array)$backendUser->groups as $id) {
                $objGroup = Database::getInstance()->prepare("SELECT alpdeskcorelogs_enabled FROM tl_user_group WHERE id=? AND disable!='1' AND (start='' OR start<='$time') AND (stop='' OR stop>'$time')")->limit(1)->execute($id);
                if ($objGroup->numRows > 0) {
                    if ($backendUser->alpdeskcorelogs_enabled == 0) {
                        $backendUser->alpdeskcorelogs_enabled = $objGroup->alpdeskcorelogs_enabled;
                    }
                }
            }
        }
    }

}
