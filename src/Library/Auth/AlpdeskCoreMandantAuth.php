<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Auth;

use Alpdesk\AlpdeskCore\Library\Exceptions\AlpdeskCoreAuthException;
use Alpdesk\AlpdeskCore\Library\Exceptions\AlpdeskCoreModelException;
use Alpdesk\AlpdeskCore\Model\Mandant\AlpdeskcoreMandantModel;
use Alpdesk\AlpdeskCore\Security\AlpdeskcoreUser;
use Contao\User;
use Alpdesk\AlpdeskCore\Library\Constants\AlpdeskCoreConstants;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class AlpdeskCoreMandantAuth
{
    private PasswordHasherFactoryInterface $passwordHasherFactory;

    /**
     * @param PasswordHasherFactoryInterface $passwordHasherFactory
     */
    public function __construct(PasswordHasherFactoryInterface $passwordHasherFactory)
    {
        $this->passwordHasherFactory = $passwordHasherFactory;
    }

    /**
     * @param string $username
     * @param string $password
     * @return AlpdeskcoreUser
     * @throws AlpdeskCoreAuthException
     */
    public function login(string $username, string $password): AlpdeskcoreUser
    {
        try {

            $alpdeskUserInstance = AlpdeskcoreMandantModel::findByUsername($username);

            if (!$this->passwordHasherFactory->getPasswordHasher(User::class)->verify($alpdeskUserInstance->getPassword(), $password)) {
                throw new AlpdeskCoreAuthException("error auth - invalid password for username:" . $username, AlpdeskCoreConstants::$ERROR_INVALID_USERNAME_PASSWORD);
            }

            return $alpdeskUserInstance;

        } catch (AlpdeskCoreModelException $ex) {
            throw new AlpdeskCoreAuthException($ex->getMessage(), $ex->getCode());
        }

    }

}
