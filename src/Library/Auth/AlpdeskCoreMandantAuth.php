<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Auth;

use Alpdesk\AlpdeskCore\Library\Exceptions\AlpdeskCoreAuthException;
use Alpdesk\AlpdeskCore\Library\Exceptions\AlpdeskCoreModelException;
use Alpdesk\AlpdeskCore\Model\Mandant\AlpdeskcoreMandantModel;
use Contao\System;
use Contao\User;

class AlpdeskCoreMandantAuth {

  public function login(string $username, string $password): void {
    try {
      $autResult = AlpdeskcoreMandantModel::findByUsername($username);
      if ($autResult !== null) {
        $encoder = System::getContainer()->get('security.encoder_factory')->getEncoder(User::class);
        if (!$encoder->isPasswordValid($password, $autResult->password, null)) {
          throw new AlpdeskCoreAuthException("error auth - invalid password for username:" . $username);
        }
      } else {
        throw new AlpdeskCoreAuthException("error auth - invalid password for username:" . $username);
      }
    } catch (AlpdeskCoreModelException $ex) {
      throw new AlpdeskCoreAuthException($ex->getMessage());
    }
  }

}
