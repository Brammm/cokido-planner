<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Domain\Community;

use CokidoPlanner\Community\Domain\Member\MemberId;
use Patchlevel\EventSourcing\Aggregate\BasicAggregateRoot;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;

#[Aggregate('community')]
final class Community extends BasicAggregateRoot
{
    #[Id]
    private CommunityId $id;

    private string $name;

    private Members $members;

    public function id(): CommunityId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function members(): Members
    {
        return $this->members;
    }

    public static function found(CommunityId $id, string $name, MemberId $foundingMemberId): static
    {
        $self = new static();
        $self->recordThat(new CommunityFounded($id, $name, $foundingMemberId));

        return $self;
    }

    #[Apply]
    public function applyCommunityFounded(CommunityFounded $event): void
    {
        $this->id = $event->id;
        $this->name = $event->name;
        $this->members = Members::create($event->foundingMember);
    }
}
