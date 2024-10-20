<?php

declare(strict_types=1);

namespace Jwt;

use Alpdesk\AlpdeskCore\Jwt\JwtToken;
use Contao\System;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class StubContainerInterface implements ContainerInterface
{
    private array $params = [];

    public function set(string $id, ?object $service): void
    {
        // TODO: Implement set() method for stub.
    }

    /**
     * @param string $id
     * @param int $invalidBehavior
     * @return object|null
     */
    public function get(string $id, int $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE): ?object
    {
        return null;
    }

    /**
     * @param $id
     * @return bool
     */
    public function has($id): bool
    {
        return true;
    }

    /**
     * @param string $id
     * @return bool
     */
    public function initialized(string $id): bool
    {
        return true;
    }

    /**
     * @param string $name
     * @return array|bool|string|int|float|\UnitEnum|null
     */
    public function getParameter(string $name): array|bool|string|int|float|\UnitEnum|null
    {
        return $this->params[$name];
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasParameter(string $name): bool
    {
        return \array_key_exists($name, $this->params);
    }

    /**
     * @param string $name
     * @param $value
     * @return void
     */
    public function setParameter(string $name, $value): void
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
        System::getContainer()->setParameter('kernel.project_dir', '.');

    }

    protected function assertPreConditions(): void
    {
        parent::assertPreConditions();

        $this->assertNotNull(System::getContainer());
        $this->assertNotEmpty(System::getContainer()->getParameter('kernel.secret'));
    }

    private function nullParameter(?\DateTimeImmutable $d): void
    {

    }

    /**
     * @return void
     */
    public function testNullCheck(): void
    {
        // InstanceCheck

        $z = null;
        $t = ($z instanceof \DateTimeImmutable);
        $this->assertFalse($t);

        $z = new \DateTimeImmutable();
        $u = ($z instanceof \DateTimeImmutable);
        $this->assertTrue($u);

        // null-Parameter-test

        try {

            $this->nullParameter(null);
            $this->fail();

        } catch (\Throwable $ex) {

            echo($ex->getMessage());
            $this->assertTrue(true);

        }

        try {

            $this->nullParameter(new \DateTimeImmutable());
            $this->assertTrue(true);

        } catch (\Throwable $ex) {

            echo($ex->getMessage());
            $this->fail();

        }
    }

    /**
     * @return void
     */
    public function testJwt(): void
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
                $this->fail();
            }
        } else {
            echo('exp == null' . PHP_EOL);
            $this->fail();
        }

    }

    public function testSecretLengthTest(): void
    {

        $keyStringGiven = System::getContainer()->getParameter('kernel.secret');
        $keyStringPrepared = \substr($keyStringGiven, 10, 32);

        $this->assertSame($keyStringPrepared, '9d7c761f1407279738f4268e8cf58310');

        $length = strlen($keyStringPrepared);
        $this->assertSame($length, 32);

        $keyBytes = [];

        for ($i = 0; $i < $length; $i++) {
            $keyBytes[] = ord($keyStringPrepared[$i]);
        }

        $this->assertSame(\count($keyBytes), 32);

    }

}
