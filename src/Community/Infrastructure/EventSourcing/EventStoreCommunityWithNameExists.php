<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Infrastructure\EventSourcing;

use CokidoPlanner\Community\Domain\Community\CommunityStarted;
use CokidoPlanner\Community\Domain\Community\CommunityWithNameExists;
use CokidoPlanner\Community\Domain\Community\Name;
use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Message\Reducer;
use Patchlevel\EventSourcing\Metadata\Event\EventRegistry;
use Patchlevel\EventSourcing\Store\Criteria\Criteria;
use Patchlevel\EventSourcing\Store\Criteria\EventsCriterion;
use Patchlevel\EventSourcing\Store\Store;

final readonly class EventStoreCommunityWithNameExists implements CommunityWithNameExists
{
    public function __construct(
        private Store $store,
        private EventRegistry $eventRegistry,
    ) {}

    public function __invoke(Name $name): bool
    {
        $stream = $this->store->load(new Criteria(new EventsCriterion([$this->eventRegistry->eventName(CommunityStarted::class)])));

        /** @var Reducer<array{has: bool}> $reducer */
        $reducer = new Reducer();
        $reducer->initState(['has' => false]);
        $reducer->when(CommunityStarted::class, static function (Message $message, array $prevState) use (
            $name,
        ): array {
            $existingName = $message->event()->name;
            if ($existingName->equals($name)) {
                return ['has' => true];
            }

            return $prevState;
        });

        return $reducer->reduce($stream)['has'];
    }
}
