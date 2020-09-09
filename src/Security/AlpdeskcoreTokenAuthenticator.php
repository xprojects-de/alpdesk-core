<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Security;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Alpdesk\AlpdeskCore\Logging\AlpdeskcoreLogger;

class AlpdeskcoreTokenAuthenticator extends AbstractGuardAuthenticator {

  private static string $prefix = 'Bearer';
  private static string $name = 'Authorization';
  protected $framework;
  protected AlpdeskcoreLogger $logger;

  public function __construct(ContaoFrameworkInterface $framework, AlpdeskcoreLogger $logger) {
    $this->framework = $framework;
    $this->framework->initialize();
    $this->logger = $logger;
  }

  public function start(Request $request, AuthenticationException $authException = null) {
    $data = ['message' => 'Auth required'];
    $this->logger->info('Auth required', __METHOD__);
    return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
  }

  public function onAuthenticationFailure(Request $request, AuthenticationException $exception) {
    $data = ['message' => strtr($exception->getMessage(), $exception->getMessageData())];
    $this->logger->error(strtr($exception->getMessage(), $exception->getMessageData()), __METHOD__);
    return new JsonResponse($data, Response::HTTP_FORBIDDEN);
  }

  public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey) {
    return null;
  }

  public function supportsRememberMe() {
    return false;
  }

  public function getCredentials(Request $request) {
    if (!$request->headers->has(self::$name)) {
      $this->logger->error(self::$name . ' not found in Header', __METHOD__);
      throw new AuthenticationException(self::$name . ' not found in Header');
    }
    $authorizationHeader = $request->headers->get(self::$name);
    if (empty($authorizationHeader)) {
      $this->logger->error(self::$name . ' empty in Header', __METHOD__);
      throw new AuthenticationException(self::$name . ' empty in Header');
    }
    $headerParts = explode(' ', $authorizationHeader);
    if (!(2 === count($headerParts) && 0 === strcasecmp($headerParts[0], self::$prefix))) {
      $this->logger->error('no valid value for ' . self::$name . ' in Header', __METHOD__);
      throw new AuthenticationException('no valid value for ' . self::$name . ' in Header');
    }
    return ['token' => $headerParts[1]];
  }

  public function getUser($credentials, UserProviderInterface $userProvider) {
    try {
      $username = $userProvider->getValidatedUsernameFromToken($credentials['token']);
    } catch (\Exception $e) {
      $this->logger->error($e->getMessage(), __METHOD__);
      throw new AuthenticationException($e->getMessage());
    }
    return $userProvider->loadUserByUsername($username);
  }

  public function checkCredentials($credentials, UserInterface $user) {
    if ($user->getFixToken() === $credentials['token']) {
      $user->setFixTokenAuth(true);
      return ($user->getFixToken() === $credentials['token']);
    }
    if ($user->getToken() != '') {
      return ($user->getToken() === $credentials['token']);
    }
    return false;
  }

  public function supports(Request $request) {
    if ('alpdeskapi' === $request->attributes->get('_scope')) {
      return true;
    }
    return false;
  }

}
