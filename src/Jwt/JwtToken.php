<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Jwt;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Contao\System;

class JwtToken {

  private static string $issuedBy = 'Alpdesk';
  private static string $permittedFor = 'https://alpdesk.de';

  private static function getDefaultKeyString(): string {
    $keyString = System::getContainer()->getParameter('contao.encryption_key');
    return substr($keyString, 10, 32);
  }

  public static function generate(string $jti, int $nbf = 3600, array $claims = array(), string $keyString = ''): string {
    $time = time();
    $signer = new Sha256();
    if ($keyString == '') {
      $keyString = self::getDefaultKeyString();
    }
    $key = new Key($keyString);
    $tokenBuilder = new Builder();
    $tokenBuilder->issuedBy(self::$issuedBy); // iss claim
    $tokenBuilder->permittedFor(self::$permittedFor); // iss claim
    $tokenBuilder->identifiedBy($jti, true); // jti claim
    $tokenBuilder->issuedAt($time); // iat claim
    $tokenBuilder->canOnlyBeUsedAfter($time + 0); // Configures the time that the token can be used (nbf claim)
    if ($nbf > 0) {
      $tokenBuilder->expiresAt($time + $nbf); // Configures the expiration time of the token (exp claim)
    }
    if (count($claims) > 0) {
      foreach ($claims as $keyClaim => $valueClaim) {
        $tokenBuilder->withClaim($keyClaim, $valueClaim);
      }
    }
    $token = $tokenBuilder->getToken($signer, $key);
    return (string) $token;
  }

  public static function parse(string $token): Token {
    return (new Parser())->parse((string) $token);
  }

  public static function validate(string $token, string $jti): bool {
    $tokenObject = self::parse($token);
    $validation = new ValidationData();
    $validation->setIssuer(self::$issuedBy);
    $validation->setAudience(self::$permittedFor);
    $validation->setId($jti);
    return $tokenObject->validate($validation);
  }

  public static function verify(string $token, string $keyString = ''): bool {
    $tokenObject = self::parse($token);
    $signer = new Sha256();
    if ($keyString == '') {
      $keyString = self::getDefaultKeyString();
    }
    return $tokenObject->verify($signer, $keyString);
  }

  public static function validateAndVerify(string $token, string $jti): bool {
    $validate = self::validate($token, $jti);
    $verify = self::verify($token);
    return ($validate == true && $verify == true);
  }

}
