<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Auth;

use Alpdesk\AlpdeskCore\Library\Exceptions\AlpdeskCoreAuthException;
use Alpdesk\AlpdeskCore\Library\Exceptions\AlpdeskCoreModelException;
use Alpdesk\AlpdeskCore\Model\Mandant\AlpdeskcoreMandantModel;
use Contao\System;
use Contao\User;
use Alpdesk\AlpdeskCore\Library\Constants\AlpdeskCoreConstants;

class AlpdeskCoreMandantAuth {

  public function login(string $username, string $password): void {
    try {
      $alpdeskUserInstance = AlpdeskcoreMandantModel::findByUsername($username);
      $encoder = System::getContainer()->get('security.encoder_factory')->getEncoder(User::class);
      if (!$encoder->isPasswordValid($alpdeskUserInstance->getPassword(), $password, null)) {
        throw new AlpdeskCoreAuthException("error auth - invalid password for username:" . $username, AlpdeskCoreConstants::$ERROR_INVALID_USERNAME_PASSWORD);
      }
    } catch (AlpdeskCoreModelException $ex) {
      throw new AlpdeskCoreAuthException($ex->getMessage(), $ex->getCode());
    }
  }

}
