<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Infrastructure;

use Brammm\Smart\Context;
use Brammm\Smart\Psr7\DefaultResponses;
use Crell\EnvMapper\EnvMapper;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Override;
use Patchlevel\EventSourcing\Metadata\AggregateRoot\AggregateRootRegistry;
use Patchlevel\EventSourcing\Metadata\AggregateRoot\AttributeAggregateRootRegistryFactory;
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
use Slim\App;

use function DI\factory;
use function DI\get;

final class CommunityContext implements Context
{
    #[Override]
    public function routes(App $app): void
    {
        $app->get('/', static fn() => DefaultResponses::json(['message' => 'Hello World!']));
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
        ];
    }
}
