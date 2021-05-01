<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Model\Auth;

use Contao\Model;

/**
 * @property string $username
 * @property string $token
 * @property string $refresh_token
 * @property integer $tstamp
 *
 * @method static Model|AlpdeskcoreSessionsModel|null findByUsername(string $username)
 */
class AlpdeskcoreSessionsModel extends Model
{
    protected static $strTable = 'tl_alpdeskcore_sessions';
}
