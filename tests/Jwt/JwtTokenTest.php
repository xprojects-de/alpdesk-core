<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCoreTest\Jwt;

use Alpdesk\AlpdeskCore\Jwt\JwtToken;
use Contao\System;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class StubContainerInterface implements ContainerInterface
{
    private array $params = [];

    public function set(string $id, ?object $service)
    {
        // TODO: Implement set() method for stub.
    }

    public function get($id, int $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE)
    {
        // TODO: Implement get() method.
    }

    public function has($id)
    {
        // TODO: Implement has() method.
    }

    public function initialized(string $id)
    {
        // TODO: Implement initialized() method.
    }

    public function getParameter(string $name)
    {
        return $this->params[$name];
    }

    public function hasParameter(string $name)
    {
        return \array_key_exists($name, $this->params);
    }

    public function setParameter(string $name, $value)
    {
        $this->params[$name] = $value;
    }
}

class JwtTokenTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        System::setContainer(new StubContainerInterface());
        System::getContainer()->setParameter('kernel.secret', '000adc04469d7c761f1407279738f4268e8cf58310e6ff2b3b317df0c61d3fc2');

    }

    protected function assertPreConditions(): void
    {
        parent::assertPreConditions();

        $this->assertNotNull(System::getContainer());
        $this->assertNotEmpty(System::getContainer()->getParameter('kernel.secret'));
    }

    private function nullParameter(\DateTimeImmutable $d): void
    {

    }

    public function testNullCheck()
    {
        // InstanceCheck

        $z = null;
        $t = ($z instanceof \DateTimeImmutable);
        $this->assertSame(false, $t);

        $z = new \DateTimeImmutable();
        $u = ($z instanceof \DateTimeImmutable);
        $this->assertSame(true, $u);

        // null-Parameter-test

        try {

            $this->nullParameter(null);
            $this->assertSame(true, false);

        } catch (\Throwable $ex) {

            echo($ex->getMessage());
            $this->assertSame(true, true);

        }

        try {

            $this->nullParameter(new \DateTimeImmutable());
            $this->assertSame(true, true);

        } catch (\Throwable $ex) {

            echo($ex->getMessage());
            $this->assertSame(false, true);

        }
    }

    public function testJwt()
    {
        $username = 'test';
        $jti = \base64_encode('alpdesk_' . $username);
        $nbf = 3600;
        $token = JwtToken::generate($jti, $nbf, array('username' => $username));

        $this->assertSame($username, JwtToken::getClaim($token, 'username'));
        $this->assertTrue(JwtToken::validateAndVerify($token, $jti));

        $expValue = JwtToken::getClaim($token, 'exp');
        if ($expValue !== null) {
            if ($expValue instanceof \DateTimeImmutable) {
                $exp = $expValue->getTimestamp() - time();
                echo('exp: ' . $exp . PHP_EOL);
                $this->assertTrue(($exp > 0));
            } else {
                echo('invalid Type' . PHP_EOL);
                $this->assertTrue(false);
            }
        } else {
            echo('exp == null' . PHP_EOL);
            $this->assertTrue(false);
        }

    }

}
