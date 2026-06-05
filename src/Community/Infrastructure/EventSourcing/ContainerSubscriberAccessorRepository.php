<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Infrastructure\EventSourcing;

use DI\Container;
use Override;
use Patchlevel\EventSourcing\Subscription\Subscriber\MetadataSubscriberAccessorRepository;
use Patchlevel\EventSourcing\Subscription\Subscriber\SubscriberAccessor;
use Patchlevel\EventSourcing\Subscription\Subscriber\SubscriberAccessorRepository;

final class ContainerSubscriberAccessorRepository implements SubscriberAccessorRepository
{
    private static ?SubscriberAccessorRepository $repository = null;

    public function __construct(
        private readonly Container $container,
    ) {}

    private function repository(): SubscriberAccessorRepository
    {
        if (self::$repository === null) {
            /** @var list<object> $subscribers */
            $subscribers = $this->container->get('subscribers');
            self::$repository = new MetadataSubscriberAccessorRepository($subscribers);
        }

        return self::$repository;
    }

    #[Override]
    public function all(): iterable
    {
        return $this->repository()->all();
    }

    #[Override]
    public function get(string $id): ?SubscriberAccessor
    {
        return $this->repository()->get($id);
    }
}
