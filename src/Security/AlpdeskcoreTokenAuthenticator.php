<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Security;

use Alpdesk\AlpdeskCore\Library\Constants\AlpdeskCoreConstants;
use Alpdesk\AlpdeskCore\Logging\AlpdeskcoreLogger;
use Alpdesk\AlpdeskCore\Security\Jwt\JwtToken;
use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class AlpdeskcoreTokenAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    private static string $prefix = 'Bearer ';
    private static string $name = 'Authorization';

    /**
     * @param ContaoFramework $framework
     * @param AlpdeskcoreLogger $logger
     * @param UserProviderInterface<AlpdeskcoreUser> $userProvider
     * @param JwtToken $jwtToken
     */
    public function __construct(
        private readonly ContaoFramework       $framework,
        private readonly AlpdeskcoreLogger     $logger,
        /**
         * @var UserProviderInterface<AlpdeskcoreUser>
         */
        private readonly UserProviderInterface $userProvider,
        private readonly JwtToken              $jwtToken
    )
    {
    }

    public function supports(Request $request): ?bool
    {
        // !!! the scope has to be checked here and must return true. Otherwise, the contao frontend firewall will be used.
        // normally, this is done in a RequestMatcher, but in this case, it must be done here because of multi firewalls in Contao
        return ('alpdeskapi' === $request->attributes->get('_scope'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['message' => \strtr($exception->getMessage(), $exception->getMessageData())], Response::HTTP_FORBIDDEN);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function authenticate(Request $request): Passport
    {
        try {

            $this->framework->initialize();

            $authorizationHeader = $request->headers->get(self::$name);
            if (!\is_string($authorizationHeader) || $authorizationHeader === '') {
                throw new CustomUserMessageAuthenticationException(self::$name . ' empty / not found in Header');
            }

            $token = \substr($authorizationHeader, \strlen(self::$prefix));
            if ($token === '') {
                throw new CustomUserMessageAuthenticationException('no valid value for ' . self::$name . ' in Header');
            }

            if (!$this->jwtToken->validate($token)) {
                throw new CustomUserMessageAuthenticationException('invalid token');
            }

            $username = $this->jwtToken->getClaim($token, 'username');
            if (!\is_string($username) || $username === '') {
                throw new CustomUserMessageAuthenticationException('invalid token: no username claim found');
            }

            return new Passport(
                new UserBadge($username, $this->userProvider->loadUserByIdentifier(...)), new CustomCredentials(
                    function ($credentials, UserInterface $userObject) {

                        if (!$userObject instanceof AlpdeskcoreUser) {
                            return false;
                        }

                        if ($userObject->getFixToken() === $credentials) {

                            $userObject->setFixTokenAuth(true);
                            return ($userObject->getFixToken() === $credentials);

                        }

                        if ($userObject->getToken() !== '') {
                            return ($userObject->getToken() === $credentials);
                        }

                        return false;

                    },
                    $token
                )
            );

        } catch (\Throwable $e) {

            $this->logger->error('error at authenticate - ' . $e->getMessage(), __METHOD__);
            throw new CustomUserMessageAuthenticationException($e->getMessage());

        }

    }

    public function start(Request $request, AuthenticationException $authException = null): JsonResponse
    {
        $data = ['type' => AlpdeskCoreConstants::$ERROR_INVALID_AUTH, 'message' => 'Auth required'];
        $this->logger->info('Auth required', __METHOD__);

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

}
