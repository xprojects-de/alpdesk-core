<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Jwt;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Hmac\Sha256;
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
        // Empty key is caught
        $config = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText(self::getDefaultKeyString()));
        $config->setValidationConstraints(new SignedWith($config->signer(), $config->signingKey()));

        return $config;
    }

    /**
     * @param string $jti
     * @param int $nbf
     * @param array $claims
     * @return string
     */
    public static function generate(string $jti, int $nbf = 3600, array $claims = array()): string
    {
        $time = \time();

        $config = self::getConfig();

        $issuesAt = (new \DateTimeImmutable())->setTimestamp($time);
        $usedAfter = (new \DateTimeImmutable())->setTimestamp($time);
        $expiresAt = (new \DateTimeImmutable())->setTimestamp($time + $nbf);

        $builder = $config->builder();
        $builder = $builder->issuedBy(self::$issuedBy); // iss claim
        $builder = $builder->permittedFor(self::$permittedFor); // iss claim
        $builder = $builder->identifiedBy($jti); // jti claim
        $builder = $builder->issuedAt($issuesAt); // iat claim

        $builder = $builder->canOnlyBeUsedAfter($usedAfter); // Configures the time that the token can be used (nbf claim)
        if ($nbf > 0) {
            $builder = $builder->expiresAt($expiresAt); // Configures the expiration time of the token (exp claim)
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
        $config = self::getConfig();
        return $config->parser()->parse($token);
    }

    /**
     * @param string $token
     * @param string $name
     * @return mixed|null
     */
    public static function getClaim(string $token, string $name): mixed
    {
        try {

            $tokenObject = self::parse($token);
            $value = $tokenObject->claims()->get($name);

        } catch (\Exception) {
            $value = null;
        }

        return $value;
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

        $issuedByConstraints = new IssuedBy(self::$issuedBy);
        $permittedForConstraints = new PermittedFor(self::$permittedFor);
        $identifiedByConstraints = new IdentifiedBy($jti);

        try {

            $value = $config->validator()->validate($tokenObject, ...$config->validationConstraints());
            if ($value === true) {

                $value = $config->validator()->validate($tokenObject, $issuedByConstraints, $permittedForConstraints, $identifiedByConstraints);

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
