<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Security;

use Contao\Input;
use Contao\Config;

class AlpdeskcoreInputSecurity
{
    /**
     * @param $varValue
     * @param false $blnDecodeEntities
     * @return array|mixed|null
     * @throws \Exception
     */
    public static function secureValue($varValue, $blnDecodeEntities = false)
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
     * @param $varValue
     * @param false $blnDecodeEntities
     * @return array|mixed|null
     * @throws \Exception
     */
    public static function secureHtmlValue($varValue, $blnDecodeEntities = false)
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
     * @param $varValue
     * @return array|bool|int|mixed|string|null
     * @throws \Exception
     */
    public static function secureRawValue($varValue)
    {
        if ($varValue === null) {
            throw new \Exception('value is null at secureValue');
        }

        $varValue = Input::preserveBasicEntities($varValue);
        $varValue = Input::xssClean($varValue);

        return $varValue;
    }
}
