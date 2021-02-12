<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Model\Mandant;

use Contao\Model;
use Contao\MemberModel;
use Contao\StringUtil;
use Alpdesk\AlpdeskCore\Library\Exceptions\AlpdeskCoreModelException;
use Alpdesk\AlpdeskCore\Security\AlpdeskcoreUser;

class AlpdeskcoreMandantModel extends Model {

  protected static $strTable = 'tl_alpdeskcore_mandant';

  public static function findByUsername($username): AlpdeskcoreUser {

    $memberObject = MemberModel::findBy(['tl_member.disable!=?', 'tl_member.login=?', 'tl_member.username=?', 'tl_member.alpdeskcore_mandant!=?'], [1, 1, $username, 0]);
    if ($memberObject !== null) {
      $alpdeskUser = new AlpdeskcoreUser();
      $alpdeskUser->setMemberId(\intval($memberObject->id));
      $alpdeskUser->setUsername($memberObject->username);
      $alpdeskUser->setPassword($memberObject->password);
      $alpdeskUser->setFirstname($memberObject->firstname);
      $alpdeskUser->setLastname($memberObject->lastname);
      $alpdeskUser->setEmail($memberObject->email);
      $alpdeskUser->setMandantPid(\intval($memberObject->alpdeskcore_mandant));
      $alpdeskUser->setFixToken($memberObject->alpdeskcore_fixtoken);
      $invalidElements = $memberObject->alpdeskcore_elements;
      if ($invalidElements !== null && $invalidElements != '') {
        $invalidElementsArray = StringUtil::deserialize($invalidElements);
        if (\is_array($invalidElementsArray) && \count($invalidElementsArray) > 0) {
          $alpdeskUser->setInvalidElements($invalidElementsArray);
        }
      }
      return $alpdeskUser;
    } else {
      throw new AlpdeskCoreModelException("error auth - invalid member");
    }
  }

}
