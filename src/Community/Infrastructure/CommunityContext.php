<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Infrastructure;

use Brammm\Smart\Context;
use Brammm\Smart\Psr7\DefaultResponses;
use Brammm\Tactishun\CommandBus;
use CokidoPlanner\Community\Domain\Community\CommunityRepository;
use CokidoPlanner\Community\Domain\Community\CommunityWithNameExists;
use CokidoPlanner\Community\Domain\Member\MemberRepository;
use CokidoPlanner\Community\Infrastructure\Persistence\EventSourcingCommunityRepository;
use CokidoPlanner\Community\Infrastructure\Persistence\EventSourcingMemberRepository;
use CokidoPlanner\Community\Infrastructure\EventSourcing\EventStoreCommunityWithNameExists;
use CokidoPlanner\Community\Infrastructure\Http\StartCommunityRequestHandler;
use CokidoPlanner\Community\Infrastructure\Projection\CommunityProjector;
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
use Patchlevel\EventSourcing\Clock\SystemClock;
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
use Patchlevel\EventSourcing\Subscription\Engine\GapResolverStoreMessageLoader;
use Patchlevel\EventSourcing\Subscription\Engine\MessageLoader;
use Patchlevel\EventSourcing\Subscription\Engine\SubscriptionEngine;
use Patchlevel\EventSourcing\Subscription\Store\DoctrineSubscriptionStore;
use Patchlevel\EventSourcing\Subscription\Store\SubscriptionStore;
use Patchlevel\EventSourcing\Subscription\Subscriber\MetadataSubscriberAccessorRepository;
use Patchlevel\EventSourcing\Subscription\Subscriber\SubscriberAccessorRepository;
use Psr\Clock\ClockInterface;
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
        $app->post('/community.start', StartCommunityRequestHandler::class);
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
            'subscribers' => [
                get(CommunityProjector::class)
            ],
            CommunityProjector::class => factory(static fn(Connection $connection) => new CommunityProjector($connection))->parameter('connection', get('connection.projections')),
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
            SubscriberAccessorRepository::class => factory(static fn(array $subscribers) => new MetadataSubscriberAccessorRepository($subscribers))->parameter('subscribers', get('subscribers')),
            SubscriptionStore::class => factory(
                static fn(Connection $connection) => new DoctrineSubscriptionStore($connection),
            )
                ->parameter('connection', get('connection.events')),
            SubscriptionEngine::class => static fn(
                MessageLoader $messageLoader,
                SubscriptionStore $subscriptionStore,
                SubscriberAccessorRepository $subscriberAccessorRepository,
            ) => new DefaultSubscriptionEngine($messageLoader, $subscriptionStore, $subscriberAccessorRepository),
            RepositoryManager::class => static fn(
                AggregateRootRegistry $aggregateRootRegistry,
                Store $eventStore,
            ) => new DefaultRepositoryManager($aggregateRootRegistry, $eventStore),
            ClockInterface::class => static fn() => new SystemClock(),
            MessageLoader::class => static fn(Store $store, ClockInterface $clock) => new GapResolverStoreMessageLoader(
                $store,
                $clock,
                [0, 5, 50, 500], // default: retries in milliseconds (0 means immediate)
                new \DateInterval('PT5M'), // default: detection window when to retry (5 minutes)
            ),

            CommandBus::class => static fn(Container $container) => new CommandBus($container),

            TreeMapper::class => static fn() => new MapperBuilder()
                ->configureWith(new RestrictKeysToSnakeCase(), new ConvertKeysToCamelCase())
                ->registerConverter(self::convertServerRequestToNext(...))
                ->allowScalarValueCasting()
                ->allowSuperfluousKeys()
                ->mapper(),

            CommunityWithNameExists::class => get(EventStoreCommunityWithNameExists::class),
            CommunityRepository::class => get(EventSourcingCommunityRepository::class),
            MemberRepository::class => get(EventSourcingMemberRepository::class),
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
