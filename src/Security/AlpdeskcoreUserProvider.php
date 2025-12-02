<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Security;

use Alpdesk\AlpdeskCore\Model\Auth\AlpdeskcoreSessionsModel;
use Alpdesk\AlpdeskCore\Model\Mandant\AlpdeskcoreMandantModel;
use Alpdesk\AlpdeskCore\Security\Jwt\JwtToken;
use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Contao\User as ContaoUser;

/**
 * @implements UserProviderInterface<AlpdeskcoreUser>
 */
readonly class AlpdeskcoreUserProvider implements UserProviderInterface
{
    public function __construct(
        private ContaoFramework                $framework,
        private PasswordHasherFactoryInterface $passwordHasherFactory,
        private JwtToken                       $jwtToken
    )
    {
    }

    public static function createJti(string $username): string
    {
        return \base64_encode('alpdesk_' . $username);
    }

    public function createToken(string $username, int $ttl): string
    {
        return $this->jwtToken->generate(self::createJti($username), $ttl, array('username' => $username));
    }

    public function createRefreshToken(string $username, int $ttl): string
    {
        return $this->jwtToken->generate(self::createJti($username), $ttl, array('username' => $username, 'isRefreshToken' => true));
    }

    public function getClaimFromToken(string $jwtToken, string $claim): mixed
    {
        return $this->jwtToken->getClaim($jwtToken, $claim);
    }

    public function getJwtToken(): JwtToken
    {
        return $this->jwtToken;
    }

    /**
     * @param string $username
     * @param string $password
     * @param int $ttl
     * @return AlpdeskcoreUser
     * @throws \Exception
     */
    public function login(string $username, string $password, int $ttl = 3600): AlpdeskcoreUser
    {
        $userInstance = $this->loadUserByUsername($username);

        if (!$this->passwordHasherFactory->getPasswordHasher(ContaoUser::class)->verify($userInstance->getPassword(), $password)) {
            throw new \Exception("error auth - invalid password for username:" . $username);
        }

        $jti = self::createJti($username);
        $userInstance->setToken($this->jwtToken->generate($jti, $ttl, array('username' => $username)));

        return $userInstance;

    }

    /**
     * @param string $jwtToken
     * @return string
     * @throws \Exception
     */
    public function extractUsernameFromToken(string $jwtToken): string
    {
        $username = $this->jwtToken->getClaim($jwtToken, 'username');

        if ($username === null || $username === '') {
            throw new AuthenticationException('invalid username');
        }

        $validateAndVerify = $this->jwtToken->validateWithJti($jwtToken, self::createJti($username));

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
                $this->jwtToken->validateWithJti($sessionModel->token, $alpdeskUser->getUsername())
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
