<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Security;

use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Alpdesk\AlpdeskCore\Logging\AlpdeskcoreLogger;
use Alpdesk\AlpdeskCore\Library\Constants\AlpdeskCoreConstants;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

// @TODO deprecated see https://symfony.com/doc/current/security/guard_authentication.html use https://symfony.com/doc/current/security/custom_authenticator.html instead

class AlpdeskcoreTokenAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface, InteractiveAuthenticatorInterface
{
    private static string $prefix = 'Bearer';
    private static string $name = 'Authorization';

    private ContaoFramework $framework;
    private AlpdeskcoreLogger $logger;
    private UserProviderInterface $userProvider;

    public function __construct(
        ContaoFramework       $framework,
        AlpdeskcoreLogger     $logger,
        UserProviderInterface $userProvider
    )
    {
        $this->framework = $framework;
        $this->logger = $logger;
        $this->userProvider = $userProvider;
    }

    public function supports(Request $request): ?bool
    {
        return ('alpdeskapi' === $request->attributes->get('_scope'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $this->framework->initialize();

        $data = ['type' => AlpdeskCoreConstants::$ERROR_INVALID_AUTH, 'message' => strtr($exception->getMessage(), $exception->getMessageData())];
        $this->logger->error(strtr($exception->getMessage(), $exception->getMessageData()), __METHOD__);

        return new JsonResponse($data, Response::HTTP_FORBIDDEN);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function authenticate(Request $request): Passport
    {
        $this->framework->initialize();

        if (!$request->headers->has(self::$name)) {

            $this->logger->error(self::$name . ' not found in Header', __METHOD__);
            throw new CustomUserMessageAuthenticationException(self::$name . ' not found in Header');

        }

        $authorizationHeader = $request->headers->get(self::$name);
        if (empty($authorizationHeader)) {

            $this->logger->error(self::$name . ' empty in Header', __METHOD__);
            throw new CustomUserMessageAuthenticationException(self::$name . ' empty in Header');

        }

        $headerParts = \explode(' ', $authorizationHeader);
        if (!(2 === \count($headerParts) && 0 === \strcasecmp($headerParts[0], self::$prefix))) {

            $this->logger->error('no valid value for ' . self::$name . ' in Header', __METHOD__);
            throw new CustomUserMessageAuthenticationException('no valid value for ' . self::$name . ' in Header');

        }

        $apiToken = $headerParts[1];

        try {

            $username = AlpdeskcoreUserProvider::extractUsernameFromToken($apiToken);

            return new Passport(
                new UserBadge($username, [$this->userProvider, 'loadUserByIdentifier']), new CustomCredentials(
                    function ($credentials, AlpdeskcoreUser $userObject) {

                        if ($userObject->getFixToken() === $credentials) {

                            $userObject->setFixTokenAuth(true);
                            return ($userObject->getFixToken() === $credentials);

                        }

                        if ($userObject->getToken() !== '') {
                            return ($userObject->getToken() === $credentials);
                        }

                        return false;

                    },
                    $apiToken
                )
            );

        } catch (\Throwable $e) {
            throw new CustomUserMessageAuthenticationException($e->getMessage());
        }

    }

    public function start(Request $request, AuthenticationException $authException = null): JsonResponse
    {
        $this->framework->initialize();

        $data = ['type' => AlpdeskCoreConstants::$ERROR_INVALID_AUTH, 'message' => 'Auth required'];
        $this->logger->info('Auth required', __METHOD__);

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public function isInteractive(): bool
    {
        return false;
    }
}
