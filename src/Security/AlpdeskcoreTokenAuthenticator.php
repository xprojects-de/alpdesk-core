<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Security;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Alpdesk\AlpdeskCore\Logging\AlpdeskcoreLogger;
use Alpdesk\AlpdeskCore\Library\Constants\AlpdeskCoreConstants;

class AlpdeskcoreTokenAuthenticator extends AbstractGuardAuthenticator
{
    private static string $prefix = 'Bearer';
    private static string $name = 'Authorization';
    protected ContaoFramework $framework;
    protected AlpdeskcoreLogger $logger;

    private bool $initialized;

    public function __construct(ContaoFramework $framework, AlpdeskcoreLogger $logger)
    {
        $this->framework = $framework;

        $this->logger = $logger;
        $this->initialized = false;
    }

    private function initialize(): void
    {
        if ($this->initialized === false) {

            $this->initialized = true;
            $this->framework->initialize();

        }

    }

    public function start(Request $request, AuthenticationException $authException = null): JsonResponse
    {
        $this->initialize();

        $data = ['type' => AlpdeskCoreConstants::$ERROR_INVALID_AUTH, 'message' => 'Auth required'];
        $this->logger->info('Auth required', __METHOD__);

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        $this->initialize();

        $data = ['type' => AlpdeskCoreConstants::$ERROR_INVALID_AUTH, 'message' => strtr($exception->getMessage(), $exception->getMessageData())];
        $this->logger->error(strtr($exception->getMessage(), $exception->getMessageData()), __METHOD__);

        return new JsonResponse($data, Response::HTTP_FORBIDDEN);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $this->initialize();

        return null;
    }

    public function supportsRememberMe(): bool
    {
        $this->initialize();

        return false;
    }

    public function getCredentials(Request $request)
    {
        $this->initialize();

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

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $this->initialize();

        try {
            $username = $userProvider->getValidatedUsernameFromToken($credentials['token']);
        } catch (\Exception $e) {
            //$this->logger->error($e->getMessage(), __METHOD__);
            throw new AuthenticationException($e->getMessage(), $e->getCode());
        }

        return $userProvider->loadUserByUsername($username);
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        $this->initialize();

        if ($user->getFixToken() === $credentials['token']) {
            $user->setFixTokenAuth(true);
            return ($user->getFixToken() === $credentials['token']);
        }

        if ($user->getToken() != '') {
            return ($user->getToken() === $credentials['token']);
        }

        return false;
    }

    public function supports(Request $request): bool
    {
        $this->initialize();

        if ('alpdeskapi' === $request->attributes->get('_scope')) {
            return true;
        }

        return false;
    }
}
