<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Security;

use Symfony\Component\Security\Core\User\UserInterface;

class AlpdeskcoreUser implements UserInterface
{
    private int $memberId = 0;
    private int $mandantPid = 0;
    private bool $isAdmin = false;
    private string $username = '';
    private string $password = '';
    private string $firstname = '';
    private string $lastname = '';
    private string $email = '';
    private string $token = '';
    private string $fixToken = '';
    private bool $fixTokenAuth = false;
    private array $invalidElements = [];
    private mixed $homeDir = null;
    private array $mandantWhitelist = [];
    private bool $accessDownload = true;
    private bool $accessUpload = true;
    private bool $accessCreate = true;
    private bool $accessDelete = true;
    private bool $accessRename = true;
    private bool $accessMove = true;
    private bool $accessCopy = true;
    private ?array $crudOperations = null;
    private ?array $crudTables = null;

    public function getMemberId(): int
    {
        return $this->memberId;
    }

    public function setMemberId(int $memberId): void
    {
        $this->memberId = $memberId;
    }

    public function getMandantPid(): int
    {
        return $this->mandantPid;
    }

    public function setMandantPid(int $mandantPid): void
    {
        $this->mandantPid = $mandantPid;
    }

    public function getIsAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function setIsAdmin(bool $isAdmin): void
    {
        $this->isAdmin = $isAdmin;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setFirstname(string $firstname): void
    {
        $this->firstname = $firstname;
    }

    public function setLastname(string $lastname): void
    {
        $this->lastname = $lastname;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getFixToken(): string
    {
        return $this->fixToken;
    }

    public function setFixToken(string $fixToken): void
    {
        $this->fixToken = $fixToken;
    }

    public function getFixTokenAuth(): bool
    {
        return $this->fixTokenAuth;
    }

    public function setFixTokenAuth(bool $fixTokenAuth): void
    {
        $this->fixTokenAuth = $fixTokenAuth;
    }

    public function getUsedToken(): string
    {
        if ($this->getFixTokenAuth() === true) {
            return $this->getFixToken();
        }
        return $this->getToken();
    }

    public function getRoles(): array
    {
        return array('ROLE_USER');
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getInvalidElements(): array
    {
        return $this->invalidElements;
    }

    public function setInvalidElements(array $invalidElements): void
    {
        $this->invalidElements = $invalidElements;
    }

    public function getHomeDir(): mixed
    {
        return $this->homeDir;
    }

    public function setHomeDir(mixed $homeDir): void
    {
        $this->homeDir = $homeDir;
    }

    public function getMandantWhitelist(): array
    {
        return $this->mandantWhitelist;
    }

    public function setMandantWhitelist(array $mandantWhitelist): void
    {
        $this->mandantWhitelist = $mandantWhitelist;
    }

    public function getAccessDownload(): bool
    {
        return $this->accessDownload;
    }

    public function getAccessUpload(): bool
    {
        return $this->accessUpload;
    }

    public function getAccessCreate(): bool
    {
        return $this->accessCreate;
    }

    public function getAccessDelete(): bool
    {
        return $this->accessDelete;
    }

    public function getAccessRename(): bool
    {
        return $this->accessRename;
    }

    public function getAccessMove(): bool
    {
        return $this->accessMove;
    }

    public function getAccessCopy(): bool
    {
        return $this->accessCopy;
    }

    public function setAccessDownload(bool $accessDownload): void
    {
        $this->accessDownload = $accessDownload;
    }

    public function setAccessUpload(bool $accessUpload): void
    {
        $this->accessUpload = $accessUpload;
    }

    public function setAccessCreate(bool $accessCreate): void
    {
        $this->accessCreate = $accessCreate;
    }

    public function setAccessDelete(bool $accessDelete): void
    {
        $this->accessDelete = $accessDelete;
    }

    public function setAccessRename(bool $accessRename): void
    {
        $this->accessRename = $accessRename;
    }

    public function setAccessMove(bool $accessMove): void
    {
        $this->accessMove = $accessMove;
    }

    public function setAccessCopy(bool $accessCopy): void
    {
        $this->accessCopy = $accessCopy;
    }

    /**
     * @return array|null
     */
    public function getCrudOperations(): ?array
    {
        return $this->crudOperations;
    }

    /**
     * @param array|null $crudOperations
     */
    public function setCrudOperations(?array $crudOperations): void
    {
        $this->crudOperations = $crudOperations;
    }

    /**
     * @return array|null
     */
    public function getCrudTables(): ?array
    {
        return $this->crudTables;
    }

    /**
     * @param array|null $crudTables
     */
    public function setCrudTables(?array $crudTables): void
    {
        $this->crudTables = $crudTables;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials(): void
    {

    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function getUserIdentifier(): string
    {
        return $this->username;
    }

}
