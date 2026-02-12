<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Security\Jwt;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\IdentifiedBy;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class JwtToken
{
    private ?Configuration $configuration = null;

    public function __construct(
        private readonly string $rootDir,
        private readonly string $issuedBy,
        private readonly string $permittedFor
    )
    {
    }

    /**
     * @return string
     */
    private function getSecret(): string
    {
        try {

            $secret = null;

            $filesystem = new Filesystem();
            $secretFile = Path::join($this->rootDir, 'var/alpdesk_jwt_secret');

            if ($filesystem->exists($secretFile)) {
                $secret = \file_get_contents($secretFile);
            }

            if (!\is_string($secret) || \strlen($secret) < 32) {

                $secret = \bin2hex(\random_bytes(32));
                $filesystem->dumpFile($secretFile, $secret);

            }

            return $secret;

        } catch (\Throwable) {
            return '';
        }

    }

    /**
     * @return void
     */
    private function setConfig(): void
    {
        if (!$this->configuration instanceof Configuration) {

            $this->configuration = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($this->getSecret()));
            $this->configuration->setValidationConstraints(new SignedWith($this->configuration->signer(), $this->configuration->signingKey()));

        }

    }

    /**
     * @param string $jti
     * @param int $nbf
     * @param array $claims
     * @return string
     */
    public function generate(string $jti, int $nbf = 3600, array $claims = array()): string
    {
        $this->setConfig();

        $builder = $this->configuration->builder();
        $builder = $builder->issuedBy($this->issuedBy);
        $builder = $builder->permittedFor($this->permittedFor);
        $builder = $builder->identifiedBy($jti);
        $builder = $builder->issuedAt(new \DateTimeImmutable());
        $builder = $builder->canOnlyBeUsedAfter(new \DateTimeImmutable());

        if ($nbf > 0) {
            $builder = $builder->expiresAt((new \DateTimeImmutable())->setTimestamp(\time() + $nbf));
        }

        if (\count($claims) > 0) {

            foreach ($claims as $keyClaim => $valueClaim) {
                $builder = $builder->withClaim($keyClaim, $valueClaim);
            }

        }

        return $builder->getToken($this->configuration->signer(), $this->configuration->signingKey())->toString();

    }

    /**
     * @param string $token
     * @return Token
     */
    public function parse(string $token): Token
    {
        $this->setConfig();

        return $this->configuration->parser()->parse($token);
    }

    /**
     * @param string $token
     * @param string $name
     * @return mixed|null
     */
    public function getClaim(string $token, string $name): mixed
    {
        try {

            $parsed = $this->parse($token);

            if (!$parsed instanceof UnencryptedToken) {
                return null;
            }

            return $parsed->claims()->get($name);

        } catch (\Throwable) {
        }

        return null;

    }

    /**
     * @param string $token
     * @return bool
     */
    public function validate(string $token): bool
    {
        $tokenObject = $this->parse($token);

        try {

            $value = $this->configuration->validator()->validate($tokenObject, ...$this->configuration->validationConstraints());
            if ($value === true) {
                $value = !$tokenObject->isExpired(new \DateTimeImmutable());
            }

        } catch (\Exception) {
            $value = false;
        }

        return $value;

    }

    /**
     * @param string $token
     * @param string $jti
     * @return bool
     */
    public function validateWithJti(string $token, string $jti): bool
    {
        try {

            $value = $this->validate($token);
            if ($value === true) {

                $tokenObject = $this->parse($token);

                $value = $this->configuration->validator()->validate(
                    $tokenObject,
                    new IssuedBy($this->issuedBy),
                    new PermittedFor($this->permittedFor),
                    new IdentifiedBy($jti)
                );

            }

        } catch (\Exception) {
            $value = false;
        }

        return $value;

    }

}
