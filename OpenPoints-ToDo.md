# TODO

- check Contao deprecations see @TODO and https://github.com/contao/contao/blob/4.x/DEPRECATED.md
- check Symfony deprecations
  - (V6.0) AbstractGuardAuthenticator: see https://symfony.com/doc/current/security/guard_authentication.html and https://symfony.com/doc/current/security/authenticator_manager.html

## Bugs 4.13

- System::getContainer()->get('request_stack') in PDF has to be checked
- System::getContainer()->getParameter('kernel.secret') has to be checked
- System::getContainer()->getParameter('kernel.project_dir') has to be checked