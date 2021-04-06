<?php

namespace Library\Cryption;

use Alpdesk\AlpdeskCore\Library\Cryption\Cryption;
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

class CryptionTest extends TestCase
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

    public function testCryptionZeroKey()
    {
        $message = 'Hello AlpdeskCore';

        try {

            $encrypt = (new Cryption(true))->safeEncrypt($message);
            echo('encrypt: ' . $encrypt . PHP_EOL);

            $decrypt = (new Cryption(true))->safeDecrypt($encrypt);
            echo('decrypt: ' . $decrypt . PHP_EOL);

            $this->assertSame($message, $decrypt);

        } catch (\Exception $e) {
            echo($e . PHP_EOL);
            $this->assertTrue(false);
        }

    }

    public function testCryptionNoneZeroKey()
    {
        $message = 'Hello AlpdeskCore';

        try {

            $cryption = new Cryption(true);

            $encrypt = $cryption->safeEncrypt($message, false);
            echo('encrypt: ' . $encrypt . PHP_EOL);

            $decrypt = $cryption->safeDecrypt($encrypt, false);
            echo('decrypt: ' . $decrypt . PHP_EOL);

            $this->assertSame($message, $decrypt);

        } catch (\Exception $e) {
            echo($e . PHP_EOL);
            $this->assertTrue(false);
        }

    }

    public function testCryptionNoneZeroKeyInvavlid()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid Decryption');

        $message = 'Hello AlpdeskCore';

        $cryption = new Cryption(true);

        $encrypt = $cryption->safeEncrypt($message, false);
        echo('encrypt: ' . $encrypt . PHP_EOL);

        $cryption->setKey('111adc04469d7c761f1407279738f426');

        $cryption->safeDecrypt($encrypt, false);

    }

}
