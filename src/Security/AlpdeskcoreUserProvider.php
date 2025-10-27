<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Security;

use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Alpdesk\AlpdeskCore\Jwt\JwtToken;
use Alpdesk\AlpdeskCore\Model\Auth\AlpdeskcoreSessionsModel;
use Alpdesk\AlpdeskCore\Model\Mandant\AlpdeskcoreMandantModel;
use Alpdesk\AlpdeskCore\Logging\AlpdeskcoreLogger;

/**
 * @implements UserProviderInterface<AlpdeskcoreUser>
 */
class AlpdeskcoreUserProvider implements UserProviderInterface
{
    private ContaoFramework $framework;
    protected AlpdeskcoreLogger $logger;

    public function __construct(ContaoFramework $framework, AlpdeskcoreLogger $logger)
    {
        $this->framework = $framework;
        $this->logger = $logger;
    }

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
        $this->framework->initialize();

        try {

            $alpdeskUser = AlpdeskcoreMandantModel::findByUsername($username);

            $sessionModel = AlpdeskcoreSessionsModel::findByUsername($alpdeskUser->getUsername());
            if (
                $sessionModel !== null &&
                self::validateAndVerifyToken($sessionModel->token, $alpdeskUser->getUsername())
            ) {
                $alpdeskUser->setToken($sessionModel->token);
            }

            return $alpdeskUser;

        } catch (\Exception $ex) {
            throw new AuthenticationException($ex->getMessage());
        }

    }

    /**
     * @param string $identifier
     * @return UserInterface
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        try {
            return $this->loadUserByUsername($identifier);
        } catch (\Throwable $tr) {
            throw new UserNotFoundException($tr->getMessage());
        }

    }

    /**
     * @param UserInterface $user
     * @return UserInterface
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        throw new UnsupportedUserException('Refresh not possible');
    }

    /**
     * @param mixed $class
     * @return bool
     */
    public function supportsClass(mixed $class): bool
    {
        return $class === AlpdeskcoreUser::class;
    }

}
