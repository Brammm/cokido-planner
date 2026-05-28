<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Infrastructure;

use Brammm\Smart\Context;
use Brammm\Smart\Psr7\DefaultResponses;
use Brammm\Tactishun\CommandBus;
use CokidoPlanner\Community\Infrastructure\Http\FoundCommunityRequestHandler;
use Crell\EnvMapper\EnvMapper;
use CuyZ\Valinor\Mapper\Configurator\ConvertKeysToCamelCase;
use CuyZ\Valinor\Mapper\Configurator\RestrictKeysToSnakeCase;
use CuyZ\Valinor\Mapper\Http\HttpRequest;
use CuyZ\Valinor\Mapper\TreeMapper;
use CuyZ\Valinor\MapperBuilder;
use DI\Container;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Override;
use Patchlevel\EventSourcing\Metadata\AggregateRoot\AggregateRootRegistry;
use Patchlevel\EventSourcing\Metadata\AggregateRoot\AttributeAggregateRootRegistryFactory;
use Patchlevel\EventSourcing\Metadata\Event\AttributeEventRegistryFactory;
use Patchlevel\EventSourcing\Metadata\Event\EventRegistry;
use Patchlevel\EventSourcing\Repository\DefaultRepositoryManager;
use Patchlevel\EventSourcing\Repository\RepositoryManager;
use Patchlevel\EventSourcing\Serializer\DefaultEventSerializer;
use Patchlevel\EventSourcing\Serializer\EventSerializer;
use Patchlevel\EventSourcing\Store\DoctrineDbalStore;
use Patchlevel\EventSourcing\Store\Store;
use Patchlevel\EventSourcing\Subscription\Engine\DefaultSubscriptionEngine;
use Patchlevel\EventSourcing\Subscription\Engine\SubscriptionEngine;
use Patchlevel\EventSourcing\Subscription\Repository\RunSubscriptionEngineRepositoryManager;
use Patchlevel\EventSourcing\Subscription\Store\DoctrineSubscriptionStore;
use Patchlevel\EventSourcing\Subscription\Store\SubscriptionStore;
use Patchlevel\EventSourcing\Subscription\Subscriber\MetadataSubscriberAccessorRepository;
use Patchlevel\EventSourcing\Subscription\Subscriber\SubscriberAccessorRepository;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;

use function DI\factory;
use function DI\get;

final class CommunityContext implements Context
{
    #[Override]
    public function routes(App $app): void
    {
        $app->get('/', static fn() => DefaultResponses::json(['message' => 'Hello World!']));
        $app->post('/community.found', FoundCommunityRequestHandler::class);
    }

    #[Override]
    public function dependencies(): array
    {
        return [
            ConnectionsEnv::class => static fn() => new EnvMapper()->map(ConnectionsEnv::class),
            'connection.events' =>
                static fn(ConnectionsEnv $env) => DriverManager::getConnection(new DsnParser()->parse($env->eventsDsn)),
            'connection.projections' =>
                static fn(ConnectionsEnv $env) => DriverManager::getConnection(new DsnParser()->parse($env->projectionsDsn)),
            EventRegistry::class => static fn() => new AttributeEventRegistryFactory()->create([
                __DIR__ . '/../Domain',
            ]),
            EventSerializer::class => DefaultEventSerializer::createFromPaths([
                __DIR__ . '/../Domain',
            ]),
            AggregateRootRegistry::class => static fn() => new AttributeAggregateRootRegistryFactory()->create([
                __DIR__ . '/../Domain',
            ]),
            Store::class => factory(static fn(
                Connection $connection,
                EventSerializer $serializer,
            ) => new DoctrineDbalStore($connection, $serializer))
                ->parameter('connection', get('connection.events')),
            SubscriberAccessorRepository::class => static fn() => new MetadataSubscriberAccessorRepository([]),
            SubscriptionStore::class => factory(
                static fn(Connection $connection) => new DoctrineSubscriptionStore($connection),
            )
                ->parameter('connection', get('connection.events')),
            SubscriptionEngine::class => static fn(
                Store $eventStore,
                SubscriptionStore $subscriptionStore,
                SubscriberAccessorRepository $subscriberAccessorRepository,
            ) => new DefaultSubscriptionEngine($eventStore, $subscriptionStore, $subscriberAccessorRepository),
            RepositoryManager::class => static fn(
                AggregateRootRegistry $aggregateRootRegistry,
                Store $eventStore,
                SubscriptionEngine $engine,
            ) => new RunSubscriptionEngineRepositoryManager(
                new DefaultRepositoryManager($aggregateRootRegistry, $eventStore),
                $engine,
            ),

            CommandBus::class => static fn(Container $container) => new CommandBus($container),

            TreeMapper::class => static fn() => new MapperBuilder()
                ->configureWith(new RestrictKeysToSnakeCase(), new ConvertKeysToCamelCase())
                ->registerConverter(self::convertServerRequestToNext(...))
                ->allowScalarValueCasting()
                ->allowSuperfluousKeys()
                ->mapper(),
        ];
    }

    /**
     * @template T
     * @param pure-callable(HttpRequest): T $next
     * @return T
     * @pure
     */
    private static function convertServerRequestToNext(ServerRequestInterface $request, callable $next): mixed
    {
        return $next(HttpRequest::fromPsr($request));
    }
}
