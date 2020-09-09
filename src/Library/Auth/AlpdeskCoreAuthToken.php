<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Auth;

use Alpdesk\AlpdeskCore\Library\Exceptions\AlpdeskCoreAuthException;
use Alpdesk\AlpdeskCore\Model\Auth\AlpdeskcoreSessionsModel;
use Alpdesk\AlpdeskCore\Library\Constants\AlpdeskCoreConstants;
use Alpdesk\AlpdeskCore\Library\Auth\AlpdeskCoreMandantAuth;
use Alpdesk\AlpdeskCore\Library\Auth\AlpdeskCoreAuthResponse;
use Alpdesk\AlpdeskCore\Security\AlpdeskcoreInputSecurity;
use Alpdesk\AlpdeskCore\Security\AlpdeskcoreUser;
use Alpdesk\AlpdeskCore\Security\AlpdeskcoreUserProvider;

class AlpdeskCoreAuthToken {

  private function setAuthSession(string $username) {
    $sessionModel = AlpdeskcoreSessionsModel::findByUsername($username);
    if ($sessionModel === null) {
      $sessionModel = new AlpdeskcoreSessionsModel();
    }
    $sessionModel->tstamp = time();
    $sessionModel->username = $username;
    $sessionModel->token = AlpdeskcoreUserProvider::createToken($username, AlpdeskCoreConstants::$TOKENTTL);
    $sessionModel->save();
    return $sessionModel;
  }

  private function invalidTokenData(string $username, string $token): void {
    $sessionModel = AlpdeskcoreSessionsModel::findBy(array('username=?', 'token=?'), array($username, $token));
    if ($sessionModel !== null) {
      // Create new Token with 1 sec validity as workaround
      // @ToDo mabe create other invalid JWT
      $sessionModel->token = AlpdeskcoreUserProvider::createToken($username, 1);
      $sessionModel->save();
    } else {
      $msg = 'Auth-Session not found for username:' . $username;
      throw new AlpdeskCoreAuthException($msg);
    }
  }

  public function generateToken(array $authdata): AlpdeskCoreAuthResponse {
    if (!\array_key_exists('username', $authdata) || !\array_key_exists('password', $authdata)) {
      throw new AlpdeskCoreAuthException('invalid key-parameters for auth');
    }
    $username = (string) AlpdeskcoreInputSecurity::secureValue($authdata['username']);
    $password = (string) AlpdeskcoreInputSecurity::secureValue($authdata['password']);
    try {
      (new AlpdeskCoreMandantAuth())->login($username, $password);
    } catch (AlpdeskCoreAuthException $ex) {
      throw new AlpdeskCoreAuthException($ex->getMessage());
    }
    $response = new AlpdeskCoreAuthResponse();
    $response->setUsername($username);
    $response->setInvalid(false);
    $response->setVerify(true);
    $tokenData = $this->setAuthSession($username);
    $response->setAlpdesk_token($tokenData->token);
    return $response;
  }

  public function invalidToken(AlpdeskcoreUser $user): AlpdeskCoreAuthResponse {
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
