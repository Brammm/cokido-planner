<?php

declare(strict_types=1);

namespace Tests\Unit\Community\Infrastructure\EventSourcing;

use CokidoPlanner\Community\Domain\Community\CommunityId;
use CokidoPlanner\Community\Domain\Community\CommunityStartedByNewMember;
use CokidoPlanner\Community\Domain\Community\Name;
use CokidoPlanner\Community\Infrastructure\EventSourcing\EventStoreCommunityWithNameExists;
use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Metadata\Event\AttributeEventRegistryFactory;
use Patchlevel\EventSourcing\Store\InMemoryStore;
use Patchlevel\EventSourcing\Store\Store;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EventStoreCommunityWithNameExists::class)]
final class EventStoreCommunityWithNameExistsTest extends TestCase
{
    private Store $eventStore;

    private EventStoreCommunityWithNameExists $sut;

    public function setUp(): void
    {
        $eventRegistry    = new AttributeEventRegistryFactory()->create([
            __DIR__ . '/../../../../../src/Community/Domain',
        ]);
        $this->eventStore = new InMemoryStore(eventRegistry: $eventRegistry);
        $this->sut        = new EventStoreCommunityWithNameExists($this->eventStore, $eventRegistry);
    }

    public function testReturnsFalseWhenNoCommunityWithNameExists(): void
    {
        self::assertFalse(($this->sut)(new Name('Community')));
    }

    public function testReturnsTrueWhenCommunityWithNameExists(): void
    {
        $this->eventStore->save(Message::create(new CommunityStartedByNewMember(CommunityId::generate(), new Name('Community'), 'John', 'Doe', 'john@example.com')));
        
        self::assertTrue(($this->sut)(new Name('Community')));
    }
}
