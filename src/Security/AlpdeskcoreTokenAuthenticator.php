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

// @TODO deprecated see https://symfony.com/doc/current/security/guard_authentication.html use https://symfony.com/doc/current/security/authenticator_manager.html instead

class AlpdeskcoreTokenAuthenticator extends AbstractGuardAuthenticator
{
    private static string $prefix = 'Bearer';
    private static string $name = 'Authorization';

    protected ContaoFramework $framework;
    protected AlpdeskcoreLogger $logger;

    public function __construct(ContaoFramework $framework, AlpdeskcoreLogger $logger)
    {
        $this->framework = $framework;
        $this->logger = $logger;
    }

    public function supports(Request $request): bool
    {
        if ('alpdeskapi' === $request->attributes->get('_scope')) {
            return true;
        }

        return false;
    }

    public function start(Request $request, AuthenticationException $authException = null): JsonResponse
    {
        $this->framework->initialize();

        $data = ['type' => AlpdeskCoreConstants::$ERROR_INVALID_AUTH, 'message' => 'Auth required'];
        $this->logger->info('Auth required', __METHOD__);

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        $this->framework->initialize();

        $data = ['type' => AlpdeskCoreConstants::$ERROR_INVALID_AUTH, 'message' => strtr($exception->getMessage(), $exception->getMessageData())];
        $this->logger->error(strtr($exception->getMessage(), $exception->getMessageData()), __METHOD__);

        return new JsonResponse($data, Response::HTTP_FORBIDDEN);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return null;
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }

    /**
     * @param Request $request
     * @return array
     */
    public function getCredentials(Request $request): array
    {
        $this->framework->initialize();

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

    public function getUser($credentials, UserProviderInterface $userProvider): ?UserInterface
    {
        $this->framework->initialize();

        try {
            $username = $userProvider->getValidatedUsernameFromToken($credentials['token']);
        } catch (\Exception $e) {
            throw new AuthenticationException($e->getMessage(), $e->getCode());
        }

        return $userProvider->loadUserByUsername($username);
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        if ($user->getFixToken() === $credentials['token']) {
            $user->setFixTokenAuth(true);
            return ($user->getFixToken() === $credentials['token']);
        }

        if ($user->getToken() != '') {
            return ($user->getToken() === $credentials['token']);
        }

        return false;
    }

}
