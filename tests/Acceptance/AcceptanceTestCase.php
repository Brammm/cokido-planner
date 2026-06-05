<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use Brammm\Smart\App;
use Brammm\Smart\AppEnv;
use CokidoPlanner\Community\Infrastructure\CommunityContext;
use CokidoPlanner\Community\Infrastructure\Persistence\ConnectionRegistry;
use JsonException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Override;
use Patchlevel\EventSourcing\Schema\ChainDoctrineSchemaConfigurator;
use Patchlevel\EventSourcing\Schema\DoctrineSchemaDirector;
use Patchlevel\EventSourcing\Store\Criteria\Criteria;
use Patchlevel\EventSourcing\Store\Criteria\FromIndexCriterion;
use Patchlevel\EventSourcing\Store\DoctrineDbalStore;
use Patchlevel\EventSourcing\Store\Store;
use Patchlevel\EventSourcing\Subscription\Engine\SubscriptionEngine;
use Patchlevel\EventSourcing\Subscription\Store\DoctrineSubscriptionStore;
use Patchlevel\EventSourcing\Subscription\Store\SubscriptionStore;
use Patchlevel\EventSourcing\Test\IncrementalRamseyUuidFactory;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;
use Ramsey\Uuid\Uuid;

abstract class AcceptanceTestCase extends TestCase
{
    private static ?App $app = null;

    private int $startIndex = 0;

    #[Override]
    public function setUp(): void
    {
        $container = $this->app()->container();

        $registry = $container->get(ConnectionRegistry::class);
        $registry->eventsConnection->close();
        $registry->projectionsConnection->close();

        /** @var DoctrineDbalStore $eventStore */
        $eventStore = $container->get(Store::class);
        /** @var DoctrineSubscriptionStore $subscriptionStore */
        $subscriptionStore = $container->get(SubscriptionStore::class);
        $schemaDirector = new DoctrineSchemaDirector(
            $registry->eventsConnection,
            new ChainDoctrineSchemaConfigurator([
                $eventStore,
                $subscriptionStore,
            ]),
        );
        $schemaDirector->create();

        $engine = $container->get(SubscriptionEngine::class);
        $engine->setup();
        $engine->boot();

        Uuid::setFactory(new IncrementalRamseyUuidFactory());

        $stream = $eventStore->load(limit: 1, backwards: true);
        $this->startIndex = $stream->index() ?? 0;
        $stream->close();
    }

    /** @param array<string,string> $headers */
    public function get(string $endpoint, array $headers = []): ResponseInterface
    {
        return $this->request('GET', $endpoint, headers: $headers);
    }

    /**
     * @param array<array-key, mixed>                $data
     * @param array<string,string>                   $headers
     * @param array<string, UploadedFileInterface[]> $files
     */
    public function post(string $endpoint, array $data = [], array $headers = [], array $files = []): ResponseInterface
    {
        $headers['Content-Type'] = 'application/json';

        return $this->request('POST', $endpoint, $data, $headers, $files);
    }

    /**
     * @param array<array-key, mixed> $data
     * @param array<string,string>    $headers
     */
    public function patch(string $endpoint, array $data = [], array $headers = []): ResponseInterface
    {
        $headers['Content-Type'] = 'application/json';

        return $this->request('PATCH', $endpoint, $data, $headers);
    }

    /** @param array<string,string> $headers */
    public function delete(string $endpoint, array $headers = []): ResponseInterface
    {
        return $this->request('DELETE', $endpoint, headers: $headers);
    }

    /**
     * @param array<array-key, mixed>                $data
     * @param array<string,string>                   $headers
     * @param array<string, UploadedFileInterface[]> $files
     */
    private function request(
        string $method,
        string $endpoint,
        array $data = [],
        array $headers = [],
        array $files = [],
    ): ResponseInterface {
        $factory = new Psr17Factory();
        $request = $factory->createServerRequest($method, $endpoint);
        if ($data !== []) {
            $request = $request->withParsedBody($data);
        }

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($files !== []) {
            $request = $request->withUploadedFiles($files);
        }

        return $this->app()->handle($request);
    }

    public function app(): App
    {
        if (self::$app === null) {
            $appEnv = new AppEnv(true, __DIR__ . '/../../var/cache');
            $app = new App($appEnv);
            $app->addcontext(new CommunityContext());
            $app->addcontext(new TestContext());

            self::$app = $app;
        }

        return self::$app;
    }

    public static function assertResponseJson(ResponseInterface $response, mixed $expected): void
    {
        try {
            // @mago-expect analysis:mixed-assignment
            $data = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            self::fail($exception->getMessage());
        }

        self::assertEquals($expected, $data);
    }

    public static function assertResponseStatusCode(int $expectedCode, ResponseInterface $response): void
    {
        try {
            self::assertEquals($expectedCode, $response->getStatusCode());
        } catch (ExpectationFailedException) {
            self::fail(sprintf(
                'Expected status code %d, got %d. Response content: %s',
                $expectedCode,
                $response->getStatusCode(),
                (string) $response->getBody(),
            ));
        }
    }

    /** @param list<object> $expectedEvents */
    public function assertEvents(array $expectedEvents): void
    {
        $eventStore = $this->app()->container()->get(Store::class);

        $messages = $eventStore->load(new Criteria(new FromIndexCriterion($this->startIndex)));
        $events = [];
        foreach ($messages as $message) {
            $events[] = $message->event();
        }
        self::assertEquals($expectedEvents, $events);
    }
}
