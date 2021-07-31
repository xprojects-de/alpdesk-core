<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Library\Auth;

use Alpdesk\AlpdeskCore\Jwt\JwtToken;

class AlpdeskCoreAuthResponse
{
    private string $alpdesk_token = '';
    private string $alpdesk_refresh_token = '';
    private string $username = '';
    private bool $verify = false;
    private bool $invalid = true;

    public function getAlpdesk_token(): string
    {
        return $this->alpdesk_token;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getVerify(): bool
    {
        return $this->verify;
    }

    public function getInvalid(): bool
    {
        return $this->invalid;
    }

    public function setAlpdesk_token(string $alpdesk_token): void
    {
        $this->alpdesk_token = $alpdesk_token;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function setVerify(bool $verify): void
    {
        $this->verify = $verify;
    }

    public function setInvalid(bool $invalid): void
    {
        $this->invalid = $invalid;
    }

    public function getAlpdeskRefreshToken(): string
    {
        return $this->alpdesk_refresh_token;
    }

    public function setAlpdeskRefreshToken(string $alpdesk_refresh_token): void
    {
        $this->alpdesk_refresh_token = $alpdesk_refresh_token;
    }

    public function getExp(): int
    {
        $exp = -1;
        if ($this->alpdesk_token != '') {

            $expvalue = JwtToken::getClaim($this->alpdesk_token, 'exp');

            if ($expvalue instanceof \DateTimeImmutable) {

                $exp = $expvalue->getTimestamp() - time();
                if ($exp < 0) {
                    $exp = 0;
                }

            }

        }

        return $exp;
    }
}
