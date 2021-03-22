<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Security;

use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Alpdesk\AlpdeskCore\Jwt\JwtToken;
use Alpdesk\AlpdeskCore\Model\Auth\AlpdeskcoreSessionsModel;
use Alpdesk\AlpdeskCore\Model\Mandant\AlpdeskcoreMandantModel;
use Alpdesk\AlpdeskCore\Logging\AlpdeskcoreLogger;

class AlpdeskcoreUserProvider implements UserProviderInterface
{
    private ContaoFramework $framework;
    protected AlpdeskcoreLogger $logger;

    public function __construct(ContaoFramework $framework, AlpdeskcoreLogger $logger)
    {
        $this->framework = $framework;
        $this->framework->initialize();
        $this->logger = $logger;
    }

    public static function createJti($username): string
    {
        return base64_encode('alpdesk_' . $username);
    }

    public static function createToken(string $username, int $ttl): string
    {
        return JwtToken::generate(self::createJti($username), $ttl, array('username' => $username));
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

        if ($username == null || $username == '') {
            throw new AuthenticationException('invalid username');
        }

        $validateAndVerify = self::validateAndVerifyToken($jwtToken, $username);

        if ($validateAndVerify == false) {
            $msg = 'invalid JWT-Token for username:' . $username . ' at verification and validation';
            throw new AuthenticationException($msg);
        }

        return AlpdeskcoreInputSecurity::secureValue($username);
    }

    /**
     * @param string $token
     * @return string
     * @throws \Exception
     */
    public function getValidatedUsernameFromToken(string $token): string
    {
        return self::extractUsernameFromToken($token);
    }

    /**
     * Override from UserProviderInterface
     * @param string $username
     * @return AlpdeskcoreUser
     * @throws AuthenticationException
     */
    public function loadUserByUsername($username)
    {
        $alpdeskUser = new AlpdeskcoreUser();
        $alpdeskUser->setUsername($username);

        $sessionModel = AlpdeskcoreSessionsModel::findByUsername($username);
        if ($sessionModel !== null) {
            if (self::validateAndVerifyToken($sessionModel->token, $username)) {
                $alpdeskUser->setToken($sessionModel->token);
            }
        }

        try {
            $alpdeskUserInstance = AlpdeskcoreMandantModel::findByUsername($username);

            $alpdeskUser->setPassword($alpdeskUserInstance->getPassword());
            $alpdeskUser->setMemberId($alpdeskUserInstance->getMemberId());
            $alpdeskUser->setFirstname($alpdeskUserInstance->getFirstname());
            $alpdeskUser->setLastname($alpdeskUserInstance->getLastname());
            $alpdeskUser->setEmail($alpdeskUserInstance->getEmail());
            $alpdeskUser->setMandantPid($alpdeskUserInstance->getMandantPid());
            $alpdeskUser->setIsAdmin($alpdeskUserInstance->getIsAdmin());
            $alpdeskUser->setMandantWhitelist($alpdeskUserInstance->getMandantWhitelist());
            $alpdeskUser->setFixToken($alpdeskUserInstance->getFixToken());
            $alpdeskUser->setInvalidElements($alpdeskUserInstance->getInvalidElements());
            $alpdeskUser->setHomeDir($alpdeskUserInstance->getHomeDir());
            $alpdeskUser->setAccessDownload($alpdeskUserInstance->getAccessDownload());
            $alpdeskUser->setAccessUpload($alpdeskUserInstance->getAccessUpload());
            $alpdeskUser->setAccessCreate($alpdeskUserInstance->getAccessCreate());
            $alpdeskUser->setAccessDelete($alpdeskUserInstance->getAccessDelete());
            $alpdeskUser->setAccessRename($alpdeskUserInstance->getAccessRename());
            $alpdeskUser->setAccessMove($alpdeskUserInstance->getAccessMove());
            $alpdeskUser->setAccessCopy($alpdeskUserInstance->getAccessCopy());

        } catch (\Exception $ex) {

        }

        return $alpdeskUser;
    }

    public function refreshUser(UserInterface $user)
    {
        throw new UnsupportedUserException('Refresh not possible');
    }

    public function supportsClass($class)
    {
        return $class === AlpdeskcoreUser::class;
    }

}
