<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Infrastructure;

use Brammm\Smart\AppEnv;
use Brammm\Smart\Context;
use Brammm\Smart\Psr7\DefaultResponses;
use CokidoPlanner\Community\Application\Community\JoinCommunityProcessor;
use CokidoPlanner\Community\Application\Member\RegisterMissingMemberProcessor;
use CokidoPlanner\Community\Domain\Community\CommunityRepository;
use CokidoPlanner\Community\Domain\Community\CommunityWithNameExists;
use CokidoPlanner\Community\Domain\Member\MemberRepository;
use CokidoPlanner\Community\Infrastructure\EventSourcing\ContainerSubscriberAccessorRepository;
use CokidoPlanner\Community\Infrastructure\EventSourcing\EventStoreCommunityWithNameExists;
use CokidoPlanner\Community\Infrastructure\Http\StartCommunityRequestHandler;
use CokidoPlanner\Community\Infrastructure\Persistence\EventSourcingCommunityRepository;
use CokidoPlanner\Community\Infrastructure\Persistence\EventSourcingMemberRepository;
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
use Patchlevel\EventSourcing\CommandBus\AggregateHandlerProvider;
use Patchlevel\EventSourcing\CommandBus\CommandBus;
use Patchlevel\EventSourcing\CommandBus\SyncCommandBus;
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
use Patchlevel\EventSourcing\Subscription\Engine\CatchUpSubscriptionEngine;
use Patchlevel\EventSourcing\Subscription\Engine\DefaultSubscriptionEngine;
use Patchlevel\EventSourcing\Subscription\Engine\GapResolverStoreMessageLoader;
use Patchlevel\EventSourcing\Subscription\Engine\MessageLoader;
use Patchlevel\EventSourcing\Subscription\Engine\SubscriptionEngine;
use Patchlevel\EventSourcing\Subscription\Engine\ThrowOnErrorSubscriptionEngine;
use Patchlevel\EventSourcing\Subscription\Repository\RunSubscriptionEngineRepositoryManager;
use Patchlevel\EventSourcing\Subscription\Store\DoctrineSubscriptionStore;
use Patchlevel\EventSourcing\Subscription\Store\SubscriptionStore;
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
                get(CommunityProjector::class),
                get(JoinCommunityProcessor::class),
                get(RegisterMissingMemberProcessor::class),
            ],
            CommunityProjector::class => factory(
                static fn(Connection $connection) => new CommunityProjector($connection),
            )
                ->parameter('connection', get('connection.projections')),
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
            SubscriberAccessorRepository::class =>
                static fn(Container $container) => new ContainerSubscriberAccessorRepository($container),
            SubscriptionStore::class => factory(
                static fn(Connection $connection) => new DoctrineSubscriptionStore($connection),
            )
                ->parameter('connection', get('connection.events')),
            ClockInterface::class => static fn() => new SystemClock(),
            MessageLoader::class => static fn(Store $store, ClockInterface $clock) => new GapResolverStoreMessageLoader(
                $store,
                $clock,
            ),
            SubscriptionEngine::class => static function (
                AppEnv $appEnv,
                Store $eventStore,
                MessageLoader $messageLoader,
                SubscriptionStore $subscriptionStore,
                SubscriberAccessorRepository $subscriberAccessorRepository,
            ) {
                $storeOrLoader = $messageLoader;
                if ($appEnv->debug) {
                    $storeOrLoader = $eventStore;
                }

                $subscriptionEngine = new DefaultSubscriptionEngine(
                    $storeOrLoader,
                    $subscriptionStore,
                    $subscriberAccessorRepository,
                );

                if ($appEnv->debug) {
                    $subscriptionEngine = new ThrowOnErrorSubscriptionEngine(
                        new CatchUpSubscriptionEngine($subscriptionEngine),
                    );
                }

                return $subscriptionEngine;
            },
            RepositoryManager::class => static function (
                AppEnv $appEnv,
                AggregateRootRegistry $aggregateRootRegistry,
                SubscriptionEngine $subscriptionEngine,
                Store $eventStore,
                ClockInterface $clock,
            ) {
                $repositoryManager = new DefaultRepositoryManager($aggregateRootRegistry, $eventStore, clock: $clock);

                if ($appEnv->debug) {
                    $repositoryManager = new RunSubscriptionEngineRepositoryManager(
                        $repositoryManager,
                        $subscriptionEngine,
                    );
                }

                return $repositoryManager;
            },

            CommandBus::class => static fn(
                AggregateRootRegistry $aggregateRootRegistry,
                RepositoryManager $repositoryManager,
                Container $container,
            ) => new SyncCommandBus(new AggregateHandlerProvider(
                $aggregateRootRegistry,
                $repositoryManager,
                $container,
            )),

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
