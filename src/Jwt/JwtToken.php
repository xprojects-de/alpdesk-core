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

class JwtToken
{
    private static string $issuedBy = 'Alpdesk';
    private static string $permittedFor = 'https://alpdesk.de';

    /**
     * @return string
     */
    private static function getDefaultKeyString(): string
    {
        $keyString = System::getContainer()->getParameter('kernel.secret');
        return \substr($keyString, 10, 32);
    }

    /**
     * @return Configuration
     */
    private static function getConfig(): Configuration
    {
        return Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText(self::getDefaultKeyString()));
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
        $builder->issuedBy(self::$issuedBy); // iss claim
        $builder->permittedFor(self::$permittedFor); // iss claim
        $builder->identifiedBy($jti); // jti claim
        $builder->issuedAt($issuesAt); // iat claim

        $builder->canOnlyBeUsedAfter($usedAfter); // Configures the time that the token can be used (nbf claim)
        if ($nbf > 0) {
            $builder->expiresAt($expiresAt); // Configures the expiration time of the token (exp claim)
        }

        if (\count($claims) > 0) {
            foreach ($claims as $keyClaim => $valueClaim) {
                $builder->withClaim($keyClaim, $valueClaim);
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
            
            if (!$tokenObject instanceof UnencryptedToken) {
                throw new \Exception('invalid token instance');
            }

            $value = $tokenObject->claims()->get($name);

        } catch (\Exception $ex) {
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

        $validator = $config->validator();

        try {

            $signer = new SignedWith($config->signer(), InMemory::plainText(self::getDefaultKeyString()));
            $validator->assert($tokenObject, $signer);

            $value = $validator->validate($tokenObject, $issuedByConstraints, $permittedForConstraints, $identifiedByConstraints);

            if ($value === true) {
                $now = (new \DateTimeImmutable())->setTimestamp(\time());
                $value = !$tokenObject->isExpired($now);
            }

        } catch (\Exception $ex) {
            $value = false;
        }

        return $value;
    }

}
