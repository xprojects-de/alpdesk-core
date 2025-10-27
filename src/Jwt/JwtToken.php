<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Jwt;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\IdentifiedBy;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Contao\System;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class JwtToken
{
    private static string $issuedBy = 'Alpdesk';
    private static string $permittedFor = 'https://alpdesk.de';

    private static ?Configuration $configuration = null;

    /**
     * @return string
     */
    private static function getDefaultKeyString(): string
    {
        // if secret becomes '' it is caught by InMemory::plainText because checked for empty

        try {

            $secret = null;
            $projectDir = System::getContainer()->getParameter('kernel.project_dir');

            $filesystem = new Filesystem();
            $secretFile = Path::join($projectDir, 'var/alpdesk_jwt_secret');

            if ($filesystem->exists($secretFile)) {
                $secret = \file_get_contents($secretFile);
            }

            if (!\is_string($secret) || \strlen($secret) < 32) {

                // legacySupport - Remove in future and do not use kernel.secret
                $keyString = System::getContainer()->getParameter('kernel.secret');
                if (\is_string($keyString) && $keyString !== '' && \strlen($keyString) >= 42) {
                    $secret = \substr($keyString, 10, 32);
                } else {
                    $secret = \bin2hex(\random_bytes(32));
                }

                $filesystem->dumpFile($secretFile, $secret);

            }

            return $secret;

        } catch (\Throwable) {
            return '';
        }

    }

    /**
     * @return Configuration
     */
    private static function getConfig(): Configuration
    {
        if (self::$configuration instanceof Configuration) {
            return self::$configuration;
        }

        // Empty key is caught
        self::$configuration = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText(self::getDefaultKeyString()));
        self::$configuration->setValidationConstraints(new SignedWith(self::$configuration->signer(), self::$configuration->signingKey()));

        return self::$configuration;
    }

    /**
     * @param string $jti
     * @param int $nbf
     * @param array $claims
     * @return string
     */
    public static function generate(string $jti, int $nbf = 3600, array $claims = array()): string
    {
        $config = self::getConfig();

        $builder = $config->builder();
        $builder = $builder->issuedBy(self::$issuedBy);
        $builder = $builder->permittedFor(self::$permittedFor);
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

        return $builder->getToken($config->signer(), $config->signingKey())->toString();

    }

    /**
     * @param string $token
     * @return Token
     */
    public static function parse(string $token): Token
    {
        return self::getConfig()->parser()->parse($token);
    }

    /**
     * @param string $token
     * @param string $name
     * @return mixed|null
     */
    public static function getClaim(string $token, string $name): mixed
    {
        try {

            $parsed = self::parse($token);

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
     * @param string $jti
     * @return bool
     */
    public static function validateAndVerify(string $token, string $jti): bool
    {
        $tokenObject = self::parse($token);

        $config = self::getConfig();

        try {

            $value = $config->validator()->validate($tokenObject, ...$config->validationConstraints());
            if ($value === true) {

                $value = $config->validator()->validate($tokenObject, new IssuedBy(self::$issuedBy), new PermittedFor(self::$permittedFor), new IdentifiedBy($jti));

                if ($value === true) {
                    $value = !$tokenObject->isExpired(new \DateTimeImmutable());
                }

            }

        } catch (\Exception) {
            $value = false;
        }

        return $value;

    }

}
