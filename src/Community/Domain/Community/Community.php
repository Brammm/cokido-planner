<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Domain\Community;

use CokidoPlanner\Community\Application\Community\JoinCommunity;
use CokidoPlanner\Community\Application\Community\StartCommunityAsNewMember;
use Patchlevel\EventSourcing\Aggregate\BasicAggregateRoot;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Handle;
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

    #[Handle]
    public static function startAsNewMember(
        StartCommunityAsNewMember $command,
        CommunityWithNameExists $communityWithNameExists,
    ): static {
        if ($communityWithNameExists($command->communityName)) {
            throw new CommunityWithNameAlreadyExists($command->communityName);
        }

        $self = new static();
        $self->recordThat(
            new CommunityStartedByNewMember(
                $command->communityId,
                $command->communityName,
                $command->memberFirstName,
                $command->memberLastName,
                $command->memberEmail,
            ),
        );

        return $self;
    }

    #[Apply]
    public function applyCommunityStartedByNewMember(CommunityStartedByNewMember $event): void
    {
        $this->id = $event->id;
        $this->name = $event->name;
        $this->members = Members::create();
    }

    #[Handle]
    public function join(JoinCommunity $command): void
    {
        $this->recordThat(new MemberJoined($command->memberId, $command->role));
    }

    #[Apply]
    public function onMemberJoined(MemberJoined $event): void
    {
        $this->members->add($event->memberId, $event->role);
    }
}
