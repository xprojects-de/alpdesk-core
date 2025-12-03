<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Auth;

use Alpdesk\AlpdeskCore\Library\Constants\AlpdeskCoreConstants;
use Alpdesk\AlpdeskCore\Library\Exceptions\AlpdeskCoreAuthException;
use Alpdesk\AlpdeskCore\Model\Auth\AlpdeskcoreSessionsModel;
use Alpdesk\AlpdeskCore\Security\AlpdeskcoreInputSecurity;
use Alpdesk\AlpdeskCore\Security\AlpdeskcoreUser;
use Alpdesk\AlpdeskCore\Security\AlpdeskcoreUserProvider;

readonly class AlpdeskCoreAuthToken
{
    public function __construct(
        private AlpdeskcoreUserProvider $userProvider
    )
    {
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
            $sessionModel->token = $this->userProvider->createToken($username, 1);
            $sessionModel->refresh_token = $this->userProvider->createRefreshToken($username, 1);
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
            $alpdeskCoreUser = $this->userProvider->login($username, $password, $ttlToken);
        } catch (AlpdeskCoreAuthException $ex) {
            throw new AlpdeskCoreAuthException($ex->getMessage(), $ex->getCode());
        }

        $response = new AlpdeskCoreAuthResponse();
        $response->setUsername($alpdeskCoreUser->getUsername());
        $response->setInvalid(false);
        $response->setVerify(true);
        $response->setAlpdesk_token($alpdeskCoreUser->getToken());
        $response->setAlpdeskRefreshToken($alpdeskCoreUser->getRefreshToken());

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

        try {

            $refreshToken = (string)AlpdeskcoreInputSecurity::secureValue($refreshData['alpdesk_refresh_token']);
            $this->userProvider->refresh($user, $refreshToken, $ttlToken);

            $response = new AlpdeskCoreAuthResponse();
            $response->setUsername($user->getUsername());
            $response->setInvalid(false);
            $response->setVerify(true);
            $response->setAlpdesk_token($user->getToken());
            $response->setAlpdeskRefreshToken($user->getRefreshToken());

            return $response;

        } catch (\Exception $ex) {
            throw new AlpdeskCoreAuthException($ex->getMessage(), $ex->getCode());
        }

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
        } catch (AlpdeskCoreAuthException) {
            $response->setInvalid(false);
        }

        return $response;
    }
}
