<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Security;

use Alpdesk\AlpdeskCore\Library\Constants\AlpdeskCoreConstants;
use Alpdesk\AlpdeskCore\Library\Exceptions\AlpdeskCoreAuthException;
use Alpdesk\AlpdeskCore\Model\Auth\AlpdeskcoreSessionsModel;
use Alpdesk\AlpdeskCore\Model\Mandant\AlpdeskcoreMandantModel;
use Alpdesk\AlpdeskCore\Security\Jwt\JwtToken;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\User\ContaoUserProvider;
use Contao\StringUtil;
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
        private JwtToken                       $jwtToken,
        private ContaoUserProvider             $frontendUserProvider,
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

        $token = $this->jwtToken->generate(self::createJti($username), $ttl, array('username' => $username));
        $refreshToken = $this->createRefreshToken($userInstance->getUsername(), $ttl);

        $sessionModel = AlpdeskcoreSessionsModel::findByUsername($username);

        if ($sessionModel === null) {
            $sessionModel = new AlpdeskcoreSessionsModel();
        }

        $sessionModel->tstamp = \time();
        $sessionModel->username = $userInstance->getUsername();
        $sessionModel->token = $token;
        $sessionModel->refresh_token = $refreshToken;
        $sessionModel->save();

        $userInstance->setToken($token);
        $userInstance->setRefreshToken($refreshToken);

        return $userInstance;

    }

    /**
     * @param AlpdeskcoreUser $user
     * @param string $refreshToken
     * @param int $ttl
     * @return void
     * @throws \Exception
     */
    public function refresh(AlpdeskcoreUser $user, string $refreshToken, int $ttl = 3600): void
    {
        $tokenUsername = $this->extractUsernameFromToken($refreshToken);
        if ($tokenUsername !== $user->getUsername()) {
            throw new AlpdeskCoreAuthException('refresh_token does not match with username', AlpdeskCoreConstants::$ERROR_INVALID_AUTH);
        }

        $isRefreshToken = $this->getClaimFromToken($refreshToken, 'isRefreshToken');
        if (!$isRefreshToken) {
            throw new AlpdeskCoreAuthException('invalid refresh_token', AlpdeskCoreConstants::$ERROR_INVALID_AUTH);
        }

        $sessionModel = AlpdeskcoreSessionsModel::findByUsername($user->getUsername());
        if ($sessionModel === null) {
            throw new AlpdeskCoreAuthException('invalid member session', AlpdeskCoreConstants::$ERROR_INVALID_AUTH);
        }

        $sessionRefreshToken = (string)$sessionModel->refresh_token;

        $sessionRefreshTokenUsername = $this->extractUsernameFromToken($sessionRefreshToken);
        if ($sessionRefreshTokenUsername !== $tokenUsername) {
            throw new AlpdeskCoreAuthException('session_refresh_token does not match with username', AlpdeskCoreConstants::$ERROR_INVALID_AUTH);
        }

        $isSessionRefreshToken = $this->getClaimFromToken($sessionRefreshToken, 'isRefreshToken');
        if (!$isSessionRefreshToken) {
            throw new AlpdeskCoreAuthException('invalid session_refresh_token', AlpdeskCoreConstants::$ERROR_INVALID_AUTH);
        }

        if ($refreshToken !== $sessionRefreshToken) {
            throw new AlpdeskCoreAuthException('refresh_token does not match with session_refresh_token', AlpdeskCoreConstants::$ERROR_INVALID_AUTH);
        }

        $token = $this->jwtToken->generate(self::createJti($user->getUsername()), $ttl, array('username' => $user->getUsername()));
        $refreshToken = $this->createRefreshToken($user->getUsername(), $ttl);

        $sessionModel = AlpdeskcoreSessionsModel::findByUsername($user->getUsername());

        if ($sessionModel === null) {
            $sessionModel = new AlpdeskcoreSessionsModel();
        }

        $sessionModel->tstamp = \time();
        $sessionModel->username = $user->getUsername();
        $sessionModel->token = $token;
        $sessionModel->refresh_token = $refreshToken;
        $sessionModel->save();

        $user->setToken($token);
        $user->setRefreshToken($refreshToken);

    }

    /**
     * @param AlpdeskcoreUser $user
     * @return void
     * @throws \Exception
     */
    public function logout(AlpdeskcoreUser $user): void
    {
        $sessionModel = AlpdeskcoreSessionsModel::findOneBy(['tl_alpdeskcore_sessions.username=?', 'tl_alpdeskcore_sessions.token=?'], [$user->getUsername(), $user->getToken()]);

        if ($sessionModel !== null) {

            $token = $this->jwtToken->generate(self::createJti($user->getUsername()), 1, array('username' => $user->getUsername()));
            $refreshToken = $this->createRefreshToken($user->getUsername(), 1);

            $sessionModel->token = $token;
            $sessionModel->refresh_token = $refreshToken;
            $sessionModel->save();

        } else {
            throw new \Exception('Auth-Session not found for username:' . $user->getUsername(), AlpdeskCoreConstants::$ERROR_COMMON);
        }
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

            $user = $this->frontendUserProvider->loadUserByIdentifier($username);
            $alpdeskUser = $this->createUserInstance($user);

            $sessionModel = AlpdeskcoreSessionsModel::findByUsername($alpdeskUser->getUsername());
            if (
                $sessionModel !== null &&
                $this->jwtToken->validateWithJti($sessionModel->token, self::createJti($alpdeskUser->getUsername()))
            ) {
                $alpdeskUser->setToken($sessionModel->token);
            }

            return $alpdeskUser;

        } catch (\Throwable $ex) {
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

    /**
     * @param ContaoUser $cUser
     * @return AlpdeskcoreUser
     * @throws \Exception
     */
    private function createUserInstance(ContaoUser $cUser): AlpdeskcoreUser
    {
        $start = (int)$cUser->start;
        $stop = (int)$cUser->stop;
        $notActiveYet = $start && $start > \time();
        $notActiveAnymore = $stop && $stop <= \time();

        if ($notActiveYet || $notActiveAnymore) {
            throw new \Exception('user account is not active');
        }

        if ($cUser->disable === true) {
            throw new \Exception('user account is disabled');
        }

        $mandantId = (int)($cUser->alpdeskcore_mandant ?? 0);
        $isAdmin = ((int)($cUser->alpdeskcore_admin ?? 0) === 1);

        if ($mandantId <= 0 && $isAdmin === false) {
            throw new \Exception("error auth - member has no mandant", AlpdeskCoreConstants::$ERROR_INVALID_MEMBER);
        }

        $alpdeskUser = new AlpdeskcoreUser();

        $alpdeskUser->setMemberId($cUser->id);
        $alpdeskUser->setUsername($cUser->username);
        $alpdeskUser->setPassword($cUser->password);
        $alpdeskUser->setFirstname($cUser->firstname);
        $alpdeskUser->setLastname($cUser->lastname);
        $alpdeskUser->setEmail($cUser->email);
        $alpdeskUser->setMandantPid($mandantId);
        $alpdeskUser->setFixToken($cUser->alpdeskcore_fixtoken ?? '');

        $alpdeskUser->setIsAdmin($isAdmin);

        if ($alpdeskUser->getIsAdmin()) {

            $mandantWhitelist = $cUser->alpdeskcore_mandantwhitelist ?? null;
            if ($mandantWhitelist !== null && $mandantWhitelist !== '') {

                $mandantWhitelistArray = StringUtil::deserialize($mandantWhitelist);
                if (\is_array($mandantWhitelistArray) && \count($mandantWhitelistArray) > 0) {

                    $finalMandantWhitelistArray = [];

                    $mandantenObject = AlpdeskcoreMandantModel::findAll();
                    if ($mandantenObject !== null) {
                        foreach ($mandantenObject as $mandant) {
                            if (\in_array((string)$mandant->id, $mandantWhitelistArray, true)) {
                                $finalMandantWhitelistArray[(int)$mandant->id] = $mandant->mandant;
                            }
                        }
                    }

                    $alpdeskUser->setMandantWhitelist($finalMandantWhitelistArray);
                }
            }
        }

        $invalidElements = $cUser->alpdeskcore_elements ?? null;
        if ($invalidElements !== null && $invalidElements !== '') {

            $invalidElementsArray = StringUtil::deserialize($invalidElements);
            if (\is_array($invalidElementsArray) && \count($invalidElementsArray) > 0) {
                $alpdeskUser->setInvalidElements($invalidElementsArray);
            }

        }

        if ($cUser->assignDir && $cUser->homeDir !== null) {
            $alpdeskUser->setHomeDir($cUser->homeDir);
        }

        if ((int)($cUser->alpdeskcore_download ?? 1) === 1) {
            $alpdeskUser->setAccessDownload(false);
        }

        if ((int)($cUser->alpdeskcore_upload ?? 1) === 1) {
            $alpdeskUser->setAccessUpload(false);
        }

        if ((int)($cUser->alpdeskcore_create ?? 1) === 1) {
            $alpdeskUser->setAccessCreate(false);
        }

        if ((int)($cUser->alpdeskcore_delete ?? 1) === 1) {
            $alpdeskUser->setAccessDelete(false);
        }

        if ((int)($cUser->alpdeskcore_rename ?? 1) === 1) {
            $alpdeskUser->setAccessRename(false);
        }

        if ((int)($cUser->alpdeskcore_move ?? 1) === 1) {
            $alpdeskUser->setAccessMove(false);
        }

        if ((int)($cUser->alpdeskcore_copy ?? 1) === 1) {
            $alpdeskUser->setAccessCopy(false);
        }

        if (($cUser->alpdeskcore_crudOperations ?? '') !== '') {

            $memberCrudOperations = StringUtil::deserialize($cUser->alpdeskcore_crudOperations ?? '');
            if (\is_array($memberCrudOperations) && \count($memberCrudOperations) > 0) {
                $alpdeskUser->setCrudOperations($memberCrudOperations);
            }

        }

        if (($cUser->alpdeskcore_crudTables ?? '') !== '') {

            $memberCrudTables = StringUtil::deserialize($cUser->alpdeskcore_crudTables ?? '');
            if (\is_array($memberCrudTables) && \count($memberCrudTables) > 0) {
                $alpdeskUser->setCrudTables($memberCrudTables);
            }

        }

        return $alpdeskUser;

    }

    /**
     * @param mixed $token
     * @return int
     */
    public function getExp(mixed $token): int
    {
        $exp = -1;
        if (\is_string($token) && $token !== '') {

            $expValue = $this->jwtToken->getClaim($token, 'exp');

            if ($expValue instanceof \DateTimeImmutable) {

                $exp = $expValue->getTimestamp() - time();
                if ($exp < 0) {
                    $exp = 0;
                }

            }

        }

        return $exp;
    }

}
