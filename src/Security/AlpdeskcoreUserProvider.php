<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Security;

use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Alpdesk\AlpdeskCore\Jwt\JwtToken;
use Alpdesk\AlpdeskCore\Model\Auth\AlpdeskcoreSessionsModel;
use Alpdesk\AlpdeskCore\Model\Mandant\AlpdeskcoreMandantModel;

class AlpdeskcoreUserProvider
{
    public static function createJti(string $username): string
    {
        return \base64_encode('alpdesk_' . $username);
    }

    public static function createToken(string $username, int $ttl): string
    {
        return JwtToken::generate(self::createJti($username), $ttl, array('username' => $username));
    }

    public static function createRefreshToken(string $username, int $ttl): string
    {
        return JwtToken::generate(self::createJti($username), $ttl, array('username' => $username, 'isRefreshToken' => true));
    }

    public static function validateAndVerifyToken(string $jwtToken, string $username): bool
    {
        return JwtToken::validateAndVerify($jwtToken, self::createJti($username));
    }

    /**
     * @param string $jwtToken
     * @return string
     * @throws \Exception
     */
    public static function extractUsernameFromToken(string $jwtToken): string
    {
        $username = JwtToken::getClaim($jwtToken, 'username');

        if ($username === null || $username === '') {
            throw new AuthenticationException('invalid username');
        }

        $validateAndVerify = self::validateAndVerifyToken($jwtToken, $username);

        if ($validateAndVerify === false) {
            throw new AuthenticationException('invalid JWT-Token for username:' . $username);
        }

        return AlpdeskcoreInputSecurity::secureValue($username);
    }

    /**
     * Override from UserProviderInterface
     * @param string $username
     * @return AlpdeskcoreUser
     * @throws AuthenticationException
     */
    public function loadUserByUsername(string $username): AlpdeskcoreUser
    {
        try {

            $alpdeskUser = AlpdeskcoreMandantModel::findByUsername($username);

            $sessionModel = AlpdeskcoreSessionsModel::findByUsername($alpdeskUser->getUsername());
            if ($sessionModel !== null) {
                if (self::validateAndVerifyToken($sessionModel->token, $alpdeskUser->getUsername())) {
                    $alpdeskUser->setToken($sessionModel->token);
                }
            }

            return $alpdeskUser;

        } catch (\Exception $ex) {
            throw new AuthenticationException($ex->getMessage());
        }

    }

}
