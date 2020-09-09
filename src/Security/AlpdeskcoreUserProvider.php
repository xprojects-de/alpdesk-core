<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Security;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Alpdesk\AlpdeskCore\Jwt\JwtToken;
use Alpdesk\AlpdeskCore\Security\AlpdeskcoreInputSecurity;
use Alpdesk\AlpdeskCore\Model\Auth\AlpdeskcoreSessionsModel;
use Alpdesk\AlpdeskCore\Model\Mandant\AlpdeskcoreMandantModel;
use Alpdesk\AlpdeskCore\Security\AlpdeskcoreUser;
use Alpdesk\AlpdeskCore\Logging\AlpdeskcoreLogger;

class AlpdeskcoreUserProvider implements UserProviderInterface {

  private $framework;
  protected AlpdeskcoreLogger $logger;

  public function __construct(ContaoFrameworkInterface $framework, AlpdeskcoreLogger $logger) {
    $this->framework = $framework;
    $this->framework->initialize();
    $this->logger = $logger;
  }

  public static function createJti($username) {
    return base64_encode('alpdesk_' . $username);
  }

  public static function createToken(string $username, int $ttl): string {
    return JwtToken::generate(self::createJti($username), $ttl, array('username' => $username));
  }

  public static function validateAndVerifyToken(string $jwtToken, string $username): bool {
    return JwtToken::validateAndVerify($jwtToken, self::createJti($username));
  }

  public static function extractUsernameFromToken(string $jwtToken): string {
    $username = JwtToken::parse($jwtToken)->getClaim('username');
    if ($username == null || $username == '') {
      throw new AuthenticationException('invalid username');
    }
    $validateAndVerify = self::validateAndVerifyToken($jwtToken, $username);
    if ($validateAndVerify == false) {
      $msg = 'invalid JWT-Token for username:' . $username . ' at verification and validation';
      throw new AuthenticationException($msg);
    }
    return AlpdeskcoreInputSecurity::secureValue($username);
  }

  public function getValidatedUsernameFromToken(string $token): string {
    return self::extractUsernameFromToken($token);
  }

  /**
   * Override from UserProviderInterface
   * @param string $username
   * @return type
   * @throws AuthenticationException
   */
  public function loadUserByUsername($username) {
    $alpdeskUser = new AlpdeskcoreUser();
    $alpdeskUser->setUsername($username);
    $sessionModel = AlpdeskcoreSessionsModel::findByUsername($username);
    if ($sessionModel !== null) {
      if (self::validateAndVerifyToken($sessionModel->token, $username)) {
        $alpdeskUser->setToken($sessionModel->token);
      }
    }
    $userData = AlpdeskcoreMandantModel::findByAuthUsername($username);
    if ($userData !== null) {
      $alpdeskUser->setMandantid(intval($userData->id));
      $alpdeskUser->setMandantPid(intval($userData->pid));
      $alpdeskUser->setFixToken($userData->fixtoken);
    }
    return $alpdeskUser;
  }

  public function refreshUser(\Symfony\Component\Security\Core\User\UserInterface $user) {
    throw new UnsupportedUserException('Refresh not possible');
  }

  public function supportsClass($class) {
    return $class === AlpdeskcoreUser::class;
  }

}
