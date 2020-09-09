<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Auth;

use Alpdesk\AlpdeskCore\Library\Exceptions\AlpdeskCoreAuthException;
use Alpdesk\AlpdeskCore\Library\Exceptions\AlpdeskCoreModelException;
use Alpdesk\AlpdeskCore\Model\Mandant\AlpdeskcoreMandantModel;

class AlpdeskCoreMandantAuth {

  public function login(string $username, string $password) {
    try {
      $autResult = AlpdeskcoreMandantModel::findByAuthUsername($username);
      if ($autResult !== null) {
        if (!\Encryption::verify($password, $autResult->password)) {
          throw new AlpdeskCoreAuthException("error auth - invalid password for username:" . $username);
        }
        return $autResult;
      } else {
        throw new AlpdeskCoreAuthException("error auth - invalid password for username:" . $username);
      }
    } catch (AlpdeskCoreModelException $ex) {
      throw new AlpdeskCoreAuthException($ex->getMessage());
    }
  }

  public function loginByFixtoken(string $username, string $fixtoken) {
    try {
      $autResult = AlpdeskcoreMandantModel::findByAuthUsernameAndFixtoken($username, $fixtoken);
      if ($autResult !== null) {
        return $autResult;
      } else {
        throw new AlpdeskCoreAuthException("error auth - invalid username or Fixtoken for username:" . $username);
      }
    } catch (AlpdeskCoreModelException $ex) {
      throw new AlpdeskCoreAuthException($ex->getMessage());
    }
  }

  public function getMandantByUsername(string $username) {
    try {
      $autResult = AlpdeskcoreMandantModel::findByAuthUsername($username);
      if ($autResult !== null) {
        return $autResult;
      } else {
        throw new AlpdeskCoreAuthException("error get MandantInformation for username: " . $username);
      }
    } catch (AlpdeskCoreModelException $ex) {
      throw new AlpdeskCoreAuthException($ex->getMessage());
    }
  }

}
