<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Model\Mandant;

use Contao\Model;
use Contao\MemberModel;
use Contao\StringUtil;
use Alpdesk\AlpdeskCore\Library\Exceptions\AlpdeskCoreModelException;
use Alpdesk\AlpdeskCore\Security\AlpdeskcoreUser;
use Alpdesk\AlpdeskCore\Library\Constants\AlpdeskCoreConstants;

/**
 * @method static Model|AlpdeskcoreMandantModel|null findById(int $getMandantPid)
 */
class AlpdeskcoreMandantModel extends Model
{
    protected static $strTable = 'tl_alpdeskcore_mandant';

    /**
     * @param string $username
     * @return AlpdeskcoreUser
     * @throws AlpdeskCoreModelException
     */
    public static function findByUsername(string $username): AlpdeskcoreUser
    {
        $memberObject = MemberModel::findBy(['tl_member.disable!=?', 'tl_member.login=?', 'tl_member.username=?'], [1, 1, $username]);

        if ($memberObject !== null) {

            $lockedSeconds = (int)$memberObject->locked - \time();
            if ($lockedSeconds > 0) {
                throw new AlpdeskCoreModelException('User is locked');
            }

            $start = (int)$memberObject->start;
            $stop = (int)$memberObject->stop;
            $notActiveYet = $start && $start > \time();
            $notActiveAnymore = $stop && $stop <= \time();

            if ($notActiveYet || $notActiveAnymore) {
                throw new AlpdeskCoreModelException('The account is not active');
            }

            $mandantId = (int)$memberObject->alpdeskcore_mandant;
            $isAdmin = ((int)$memberObject->alpdeskcore_admin === 1);

            if ($mandantId <= 0 && $isAdmin === false) {
                throw new AlpdeskCoreModelException("error auth - member has no mandant", AlpdeskCoreConstants::$ERROR_INVALID_MEMBER);
            }

            $alpdeskUser = new AlpdeskcoreUser();

            $alpdeskUser->setMemberId((int)$memberObject->id);
            $alpdeskUser->setUsername($memberObject->username);
            $alpdeskUser->setPassword($memberObject->password);
            $alpdeskUser->setFirstname($memberObject->firstname);
            $alpdeskUser->setLastname($memberObject->lastname);
            $alpdeskUser->setEmail($memberObject->email);
            $alpdeskUser->setMandantPid($mandantId);
            $alpdeskUser->setFixToken($memberObject->alpdeskcore_fixtoken);

            $alpdeskUser->setIsAdmin($isAdmin);

            if ($alpdeskUser->getIsAdmin()) {

                $mandantWhitelist = $memberObject->alpdeskcore_mandantwhitelist;
                if ($mandantWhitelist !== null && $mandantWhitelist !== '') {

                    $mandantWhitelistArray = StringUtil::deserialize($mandantWhitelist);
                    if (\is_array($mandantWhitelistArray) && \count($mandantWhitelistArray) > 0) {

                        $finalMandantWhitelistArray = [];

                        $mandantenObject = self::findAll();
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

            $invalidElements = $memberObject->alpdeskcore_elements;
            if ($invalidElements !== null && $invalidElements !== '') {
                $invalidElementsArray = StringUtil::deserialize($invalidElements);
                if (\is_array($invalidElementsArray) && \count($invalidElementsArray) > 0) {
                    $alpdeskUser->setInvalidElements($invalidElementsArray);
                }
            }

            if ($memberObject->assignDir && $memberObject->homeDir !== null) {
                $alpdeskUser->setHomeDir($memberObject->homeDir);
            }

            if ($memberObject->alpdeskcore_download !== null && (int)$memberObject->alpdeskcore_download === 1) {
                $alpdeskUser->setAccessDownload(false);
            }

            if ($memberObject->alpdeskcore_upload !== null && (int)$memberObject->alpdeskcore_upload === 1) {
                $alpdeskUser->setAccessUpload(false);
            }

            if ($memberObject->alpdeskcore_create !== null && (int)$memberObject->alpdeskcore_create === 1) {
                $alpdeskUser->setAccessCreate(false);
            }

            if ($memberObject->alpdeskcore_delete !== null && (int)$memberObject->alpdeskcore_delete === 1) {
                $alpdeskUser->setAccessDelete(false);
            }

            if ($memberObject->alpdeskcore_rename !== null && (int)$memberObject->alpdeskcore_rename === 1) {
                $alpdeskUser->setAccessRename(false);
            }

            if ($memberObject->alpdeskcore_move !== null && (int)$memberObject->alpdeskcore_move === 1) {
                $alpdeskUser->setAccessMove(false);
            }

            if ($memberObject->alpdeskcore_copy !== null && (int)$memberObject->alpdeskcore_copy === 1) {
                $alpdeskUser->setAccessCopy(false);
            }

            return $alpdeskUser;

        }

        throw new AlpdeskCoreModelException("invalid member: " . $username, AlpdeskCoreConstants::$ERROR_INVALID_MEMBER);

    }

}
