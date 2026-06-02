<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Application\Member;

use CokidoPlanner\Community\Domain\Community\CommunityStartedByNewMember;
use CokidoPlanner\Community\Domain\Community\MemberRole;
use Patchlevel\EventSourcing\Attribute\Processor;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\CommandBus\CommandBus;

#[Processor('register_missing_members_1')]
final readonly class RegisterMissingMemberProcessor
{
    public function __construct(
        private CommandBus $commandBus,
    ) {}

    #[Subscribe(CommunityStartedByNewMember::class)]
    public function onNewMemberStartedCommunity(CommunityStartedByNewMember $event): void
    {
        $this->commandBus->dispatch(
            new RegisterMember(
                $event->memberFirstName,
                $event->memberLastName,
                $event->memberEmail,
                $event->id,
                MemberRole::Admin,
            ),
        );
    }
}
