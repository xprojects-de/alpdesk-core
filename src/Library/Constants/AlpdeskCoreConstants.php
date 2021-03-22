<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Constants;

class AlpdeskCoreConstants
{
    public static int $TOKENTTL = 3600;
    public static int $STATUSCODE_OK = 200;
    public static int $STATUSCODE_COMMONERROR = 400;
    public static int $ERROR_COMMON = 10000;
    public static int $ERROR_INVALID_AUTH = 10001;
    public static int $ERROR_FILEMANAGEMENT_INVALIDFILES = 10002;
    public static int $ERROR_INVALID_MEMBER = 10003;
    public static int $ERROR_INVALID_USERNAME_PASSWORD = 10004;
    public static int $ERROR_INVALID_KEYPARAMETERS = 10005;
    public static int $ERROR_INVALID_MANDANT = 10006;
    public static int $ERROR_INVALID_PLUGIN = 10007;
    public static int $ERROR_INVALID_INPUT = 10008;
    public static int $ERROR_INVALID_PATH = 10009;
    public static int $ERROR_ACCESS_DENIED = 10010;
}
