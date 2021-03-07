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
use Alpdesk\AlpdeskCore\Model\Mandant\AlpdeskcoreMandantModel;
use Contao\MemberModel;

class AlpdeskCoreAuthToken {

  private function setAuthSession(string $username, int $ttl_token = 3600) {
    $sessionModel = AlpdeskcoreSessionsModel::findByUsername($username);
    if ($sessionModel === null) {
      $sessionModel = new AlpdeskcoreSessionsModel();
    }
    $sessionModel->tstamp = time();
    $sessionModel->username = $username;
    $sessionModel->token = AlpdeskcoreUserProvider::createToken($username, $ttl_token);
    $sessionModel->save();
    return $sessionModel;
  }

  private function invalidTokenData(string $username, string $token): void {
    $sessionModel = AlpdeskcoreSessionsModel::findBy(['tl_alpdeskcore_sessions.username=?', 'tl_alpdeskcore_sessions.token=?'], [$username, $token]);
    if ($sessionModel !== null) {
      $sessionModel->token = AlpdeskcoreUserProvider::createToken($username, 1);
      $sessionModel->save();
    } else {
      $msg = 'Auth-Session not found for username:' . $username;
      throw new AlpdeskCoreAuthException($msg);
    }
  }

  private function generateAdminResponse(AlpdeskcoreUser $user, int $ttlToken): AlpdeskCoreAuthResponse {

    $mandantData = [];

    $mandantenObject = AlpdeskcoreMandantModel::findAll();
    if ($mandantenObject !== null) {
      foreach ($mandantenObject as $mandant) {
        $mandantData[$mandant->id] = $mandant->mandant;
      }
    }

    $response = new AlpdeskCoreAuthResponse();
    $response->setUsername($user->getUsername());
    $response->setInvalid(false);
    $response->setVerify(false);

    $memberObject = MemberModel::findByPk($user->getMemberId());
    $memberObject->alpdeskcore_tmpmandant = $user->getMandantPid();
    $memberObject->save();

    if ($user->getMandantPid() !== AlpdeskcoreUser::$ADMIN_MANDANT_ID) {
      $tokenData = $this->setAuthSession($user->getUsername(), $ttlToken);
      $response->setAlpdesk_token($tokenData->token);
       $response->setVerify(true);
    } else {
      $response->setAlpdesk_token('');
    }

    $response->setAdditionalData($mandantData);

    return $response;
  }

  public function generateToken(array $authdata): AlpdeskCoreAuthResponse {
    if (!\array_key_exists('username', $authdata) || !\array_key_exists('password', $authdata)) {
      throw new AlpdeskCoreAuthException('invalid key-parameters for auth');
    }
    $ttlToken = AlpdeskCoreConstants::$TOKENTTL;
    if (\array_key_exists('ttltoken', $authdata)) {
      $ttlToken = (int) AlpdeskcoreInputSecurity::secureValue($authdata['ttltoken']);
    }
    $username = (string) AlpdeskcoreInputSecurity::secureValue($authdata['username']);
    $password = (string) AlpdeskcoreInputSecurity::secureValue($authdata['password']);

    $alpdeskUser = (new AlpdeskCoreMandantAuth())->login($username, $password);

    if ($alpdeskUser->getIsAdmin() === true) {

      if (!\array_key_exists('mandant', $authdata)) {
        throw new AlpdeskCoreAuthException('invalid key-parameters mandant for Adminlogin');
      }

      $alpdeskUser->setMandantPid(AlpdeskcoreUser::$ADMIN_MANDANT_ID);
      $mandantId = (string) AlpdeskcoreInputSecurity::secureValue($authdata['mandant']);
      if ($mandantId != '' && $mandantId != '0') {
        $alpdeskUser->setMandantPid(\intval($mandantId));
      }

      return $this->generateAdminResponse($alpdeskUser, $ttlToken);
    }

    $response = new AlpdeskCoreAuthResponse();
    $response->setUsername($username);
    $response->setInvalid(false);
    $response->setVerify(true);
    $tokenData = $this->setAuthSession($username, $ttlToken);
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
