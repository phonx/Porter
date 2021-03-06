<?php
namespace ScriptFUSIONTest\Functional\Porter\Net\Http;

use ScriptFUSION\Porter\Connector\Connector;
use ScriptFUSION\Porter\Net\Http\HttpConnectionException;
use ScriptFUSION\Porter\Net\Http\HttpConnector;
use ScriptFUSION\Porter\Net\Http\HttpOptions;
use ScriptFUSION\Porter\Net\Http\HttpServerException;
use ScriptFUSION\Retry\FailingTooHardException;
use Symfony\Component\Process\Process;

final class HttpConnectorTest extends \PHPUnit_Framework_TestCase
{
    const HOST = '[::1]:12345';
    const URI = '/test?baz=qux';

    private static $dir;

    /** @var HttpConnector */
    private $connector;

    public static function setUpBeforeClass()
    {
        self::$dir = __DIR__ . '/servers';
    }

    protected function setUp()
    {
        $this->connector = new HttpConnector;
    }

    public function testConnectionToLocalWebserver()
    {
        $server = $this->startServer('feedback');
        $response = $this->fetch(new HttpConnector((new HttpOptions)->addHeader($header = 'Foo: Bar')));
        $this->stopServer($server);

        self::assertRegExp('[\AGET \Q' . self::HOST . self::URI . '\E HTTP/\d+\.\d+$]m', $response);
        self::assertRegExp("[^$header$]m", $response);
    }

    public function testConnectionTimeout()
    {
        try {
            $this->fetch($this->connector->setTries(1));
        } catch (FailingTooHardException $exception) {
            self::assertInstanceOf(HttpConnectionException::class, $exception->getPrevious());

            return;
        }

        self::fail('Expected exception was not thrown.');
    }

    public function testErrorResponse()
    {
        $server = $this->startServer('404');
        try {
            $this->fetch();
        } catch (FailingTooHardException $exception) {
            self::assertInstanceOf(HttpServerException::class, $exception->getPrevious(), $exception->getMessage());
            self::assertStringEndsWith('foo', $exception->getPrevious()->getMessage());

            return;
        } finally {
            $this->stopServer($server);
        }

        self::fail('Expected exception was not thrown.');
    }

    public function testOneTry()
    {
        $this->setExpectedException(FailingTooHardException::class, '1');

        $this->fetch($this->connector->setTries(1));
    }

    public function testDefaultTries()
    {
        $this->setExpectedException(FailingTooHardException::class, (string)HttpConnector::DEFAULT_TRIES);

        $this->fetch();
    }

    /**
     * @param string $script
     *
     * @return Process Server.
     */
    private function startServer($script)
    {
        $server = (
            // Prevent forking on some Unix systems.
            new Process(sprintf('%sphp -S %s %s.php', file_exists('/bin/sh') ? 'exec ' : '', self::HOST, $script))
        )->setWorkingDirectory(self::$dir);
        $server->start();

        return $server;
    }

    private function stopServer(Process $server)
    {
        $server->stop();
    }

    private function fetch(Connector $connector = null)
    {
        $connector = $connector ?: $this->connector;

        return $connector->fetch('http://' . self::HOST . self::URI);
    }
}
