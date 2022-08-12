<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Security;

use Contao\Input;
use Contao\Config;

class AlpdeskcoreInputSecurity
{
    /**
     * @param mixed $varValue
     * @param false $blnDecodeEntities
     * @return mixed
     * @throws \Exception
     */
    public static function secureValue(mixed $varValue, bool $blnDecodeEntities = false): mixed
    {
        if ($varValue === null) {
            throw new \Exception('value is null at secureValue');
        }

        $varValue = Input::decodeEntities($varValue);
        $varValue = Input::xssClean($varValue, true);
        $varValue = Input::stripTags($varValue);

        if (!$blnDecodeEntities) {
            $varValue = Input::encodeSpecialChars($varValue);
        }

        return $varValue;
    }

    /**
     * @param mixed $varValue
     * @param false $blnDecodeEntities
     * @return mixed
     * @throws \Exception
     */
    public static function secureHtmlValue(mixed $varValue, bool $blnDecodeEntities = false): mixed
    {
        if ($varValue === null) {
            throw new \Exception('value is null at secureValue');
        }

        $varValue = Input::decodeEntities($varValue);
        $varValue = Input::xssClean($varValue);
        $varValue = Input::stripTags($varValue, Config::get('allowedTags'));

        if (!$blnDecodeEntities) {
            $varValue = Input::encodeSpecialChars($varValue);
        }

        return $varValue;
    }

    /**
     * @param mixed $varValue
     * @return mixed
     * @throws \Exception
     */
    public static function secureRawValue(mixed $varValue): mixed
    {
        if ($varValue === null) {
            throw new \Exception('value is null at secureValue');
        }

        $varValue = Input::preserveBasicEntities($varValue);

        return Input::xssClean($varValue);
    }
}
