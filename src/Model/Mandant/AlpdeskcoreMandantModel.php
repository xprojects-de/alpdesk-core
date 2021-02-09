<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Model\Mandant;

use Contao\Model;
use Contao\MemberModel;
use Alpdesk\AlpdeskCore\Library\Exceptions\AlpdeskCoreModelException;
use Alpdesk\AlpdeskCore\Security\AlpdeskcoreUser;

class AlpdeskcoreMandantModel extends Model {

  protected static $strTable = 'tl_alpdeskcore_mandant';

  public static function findByUsername($username): AlpdeskcoreUser {

    $memberObject = MemberModel::findBy(['tl_member.disable!=?', 'tl_member.login=?', 'tl_member.username=?'], [1, 1, $username]);
    if ($memberObject !== null) {
      $mandantObject = self::findAll();
      if ($mandantObject !== null) {
        $currentMandatObject = null;
        foreach ($mandantObject as $mandant) {
          if ($mandant->member_1 == $memberObject->id ||
                  $mandant->member_2 == $memberObject->id ||
                  $mandant->member_3 == $memberObject->id ||
                  $mandant->member_4 == $memberObject->id ||
                  $mandant->member_5 == $memberObject->id ||
                  $mandant->member_6 == $memberObject->id ||
                  $mandant->member_7 == $memberObject->id ||
                  $mandant->member_8 == $memberObject->id ||
                  $mandant->member_9 == $memberObject->id ||
                  $mandant->member_10 == $memberObject->id) {
            $currentMandatObject = $mandant;
            break;
          }
        }
        if ($currentMandatObject !== null) {
          $alpdeskUser = new AlpdeskcoreUser();
          $alpdeskUser->setUsername($memberObject->username);
          $alpdeskUser->setMandantPid(intval($currentMandatObject->id));
          $alpdeskUser->setFixToken($memberObject->fixtoken);
        } else {
          throw new AlpdeskCoreModelException("error auth - invalid no mandant found");
        }
      } else {
        throw new AlpdeskCoreModelException("error auth - invalid mandat object");
      }
      return $alpdeskUser;
    } else {
      throw new AlpdeskCoreModelException("error auth - invalid username");
    }
  }

}
