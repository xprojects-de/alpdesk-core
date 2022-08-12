<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Auth;

use Alpdesk\AlpdeskCore\Jwt\JwtToken;
use Alpdesk\AlpdeskCore\Library\Exceptions\AlpdeskCoreAuthException;
use Alpdesk\AlpdeskCore\Model\Auth\AlpdeskcoreSessionsModel;
use Alpdesk\AlpdeskCore\Library\Constants\AlpdeskCoreConstants;
use Alpdesk\AlpdeskCore\Security\AlpdeskcoreInputSecurity;
use Alpdesk\AlpdeskCore\Security\AlpdeskcoreUser;
use Alpdesk\AlpdeskCore\Security\AlpdeskcoreUserProvider;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class AlpdeskCoreAuthToken
{
    private PasswordHasherFactoryInterface $passwordHasherFactory;

    /**
     * @param PasswordHasherFactoryInterface $passwordHasherFactory
     */
    public function __construct(PasswordHasherFactoryInterface $passwordHasherFactory)
    {
        $this->passwordHasherFactory = $passwordHasherFactory;
    }

    private function setAuthSession(string $username, int $ttl_token = 3600): mixed
    {
        $sessionModel = AlpdeskcoreSessionsModel::findByUsername($username);

        if ($sessionModel === null) {
            $sessionModel = new AlpdeskcoreSessionsModel();
        }

        $sessionModel->tstamp = time();
        $sessionModel->username = $username;
        $sessionModel->token = AlpdeskcoreUserProvider::createToken($username, $ttl_token);
        $sessionModel->refresh_token = AlpdeskcoreUserProvider::createRefreshToken($username, $ttl_token);
        $sessionModel->save();

        return $sessionModel;
    }

    /**
     * @param string $username
     * @param string $token
     * @throws AlpdeskCoreAuthException
     */
    private function invalidTokenData(string $username, string $token): void
    {
        $sessionModel = AlpdeskcoreSessionsModel::findBy(['tl_alpdeskcore_sessions.username=?', 'tl_alpdeskcore_sessions.token=?'], [$username, $token]);

        if ($sessionModel !== null) {
            $sessionModel->token = AlpdeskcoreUserProvider::createToken($username, 1);
            $sessionModel->refresh_token = AlpdeskcoreUserProvider::createRefreshToken($username, 1);
            $sessionModel->save();
        } else {
            $msg = 'Auth-Session not found for username:' . $username;
            throw new AlpdeskCoreAuthException($msg, AlpdeskCoreConstants::$ERROR_COMMON);
        }
    }

    /**
     * @param array $authdata
     * @return AlpdeskCoreAuthResponse
     * @throws \Exception
     */
    public function generateToken(array $authdata): AlpdeskCoreAuthResponse
    {
        if (!\array_key_exists('username', $authdata) || !\array_key_exists('password', $authdata)) {
            throw new AlpdeskCoreAuthException('invalid key-parameters for auth', AlpdeskCoreConstants::$ERROR_INVALID_KEYPARAMETERS);
        }

        $ttlToken = AlpdeskCoreConstants::$TOKENTTL;
        if (\array_key_exists('ttltoken', $authdata)) {
            $ttlToken = (int)AlpdeskcoreInputSecurity::secureValue($authdata['ttltoken']);
        }

        $username = (string)AlpdeskcoreInputSecurity::secureValue($authdata['username']);
        $password = (string)AlpdeskcoreInputSecurity::secureValue($authdata['password']);

        try {
            (new AlpdeskCoreMandantAuth($this->passwordHasherFactory))->login($username, $password);
        } catch (AlpdeskCoreAuthException $ex) {
            throw new AlpdeskCoreAuthException($ex->getMessage(), $ex->getCode());
        }

        $response = new AlpdeskCoreAuthResponse();
        $response->setUsername($username);
        $response->setInvalid(false);
        $response->setVerify(true);
        $tokenData = $this->setAuthSession($username, $ttlToken);
        $response->setAlpdesk_token($tokenData->token);
        $response->setAlpdeskRefreshToken($tokenData->refresh_token);

        return $response;
    }

    /**
     * @param array $refreshData
     * @param AlpdeskcoreUser $user
     * @return AlpdeskCoreAuthResponse
     * @throws \Exception
     */
    public function refreshToken(array $refreshData, AlpdeskcoreUser $user): AlpdeskCoreAuthResponse
    {
        if (!\array_key_exists('alpdesk_refresh_token', $refreshData)) {
            throw new AlpdeskCoreAuthException('invalid key-parameters for refresh', AlpdeskCoreConstants::$ERROR_INVALID_KEYPARAMETERS);
        }

        $ttlToken = AlpdeskCoreConstants::$TOKENTTL;
        if (\array_key_exists('ttltoken', $refreshData)) {
            $ttlToken = (int)AlpdeskcoreInputSecurity::secureValue($refreshData['ttltoken']);
        }

        $refreshToken = (string)AlpdeskcoreInputSecurity::secureValue($refreshData['alpdesk_refresh_token']);

        try {

            // Method also validate and verify the token
            $tokenUsername = AlpdeskcoreUserProvider::extractUsernameFromToken($refreshToken);
            if ($tokenUsername !== $user->getUsername()) {
                throw new AlpdeskCoreAuthException('refresh_token does not match with username', AlpdeskCoreConstants::$ERROR_INVALID_AUTH);
            }

            // Check if it´s a refresh-Token
            $isRefreshToken = JwtToken::getClaim($refreshToken, 'isRefreshToken');
            if (!$isRefreshToken) {
                throw new AlpdeskCoreAuthException('invalid refresh_token', AlpdeskCoreConstants::$ERROR_INVALID_AUTH);
            }

            // Get valid memberSession
            $sessionModel = AlpdeskcoreSessionsModel::findByUsername($user->getUsername());
            if ($sessionModel === null) {
                throw new AlpdeskCoreAuthException('invalid member session', AlpdeskCoreConstants::$ERROR_INVALID_AUTH);
            }

            $sessionRefreshToken = (string)$sessionModel->refresh_token;

            // Method also validate and verify the token
            $sessionRefreshTokenUsername = AlpdeskcoreUserProvider::extractUsernameFromToken($sessionRefreshToken);
            if ($sessionRefreshTokenUsername !== $tokenUsername) {
                throw new AlpdeskCoreAuthException('session_refresh_token does not match with username', AlpdeskCoreConstants::$ERROR_INVALID_AUTH);
            }

            // Check if it´s a refresh-Token
            $isSessionRefreshToken = JwtToken::getClaim($sessionRefreshToken, 'isRefreshToken');
            if (!$isSessionRefreshToken) {
                throw new AlpdeskCoreAuthException('invalid session_refresh_token', AlpdeskCoreConstants::$ERROR_INVALID_AUTH);
            }

            if ($refreshToken !== $sessionRefreshToken) {
                throw new AlpdeskCoreAuthException('refresh_token does not match with session_refresh_token', AlpdeskCoreConstants::$ERROR_INVALID_AUTH);
            }

        } catch (\Exception $ex) {
            throw new AlpdeskCoreAuthException($ex->getMessage(), $ex->getCode());
        }


        $response = new AlpdeskCoreAuthResponse();
        $response->setUsername($user->getUsername());
        $response->setInvalid(false);
        $response->setVerify(true);
        $tokenData = $this->setAuthSession($user->getUsername(), $ttlToken);
        $response->setAlpdesk_token($tokenData->token);
        $response->setAlpdeskRefreshToken($tokenData->refresh_token);

        return $response;
    }

    public function invalidToken(AlpdeskcoreUser $user): AlpdeskCoreAuthResponse
    {
        $response = new AlpdeskCoreAuthResponse();
        $response->setUsername($user->getUsername());
        $response->setAlpdesk_token($user->getToken());
        $response->setVerify(false);

        try {
            $this->invalidTokenData($response->getUsername(), $response->getAlpdesk_token());
            $response->setInvalid(true);
        } catch (AlpdeskCoreAuthException $ex) {
            $response->setInvalid(false);
        }

        return $response;
    }
}
