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

    private Name $name;

    private Members $members;

    public function id(): CommunityId
    {
        return $this->id;
    }

    public function name(): Name
    {
        return $this->name;
    }

    public function members(): Members
    {
        return $this->members;
    }

    public static function start(CommunityWithNameExists $communityWithNameExists, CommunityId $id, Name $name, MemberId $startedBy): static
    {
        if ($communityWithNameExists($name)) {
            throw new CommunityWithNameAlreadyExists($name);
        }
        
        $self = new static();
        $self->recordThat(new CommunityStarted($id, $name, $startedBy));

        return $self;
    }

    #[Apply]
    public function applyCommunityStarted(CommunityStarted $event): void
    {
        $this->id = $event->id;
        $this->name = $event->name;
        $this->members = Members::create($event->startedBy);
    }
}
