<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Application\Community;

use CokidoPlanner\Community\Domain\Member\MemberRegistered;
use Patchlevel\EventSourcing\Attribute\Processor;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\CommandBus\CommandBus;

#[Processor('join_community_1')]
final readonly class JoinCommunityProcessor
{
    public function __construct(
        private CommandBus $commandBus,
    ) {}

    #[Subscribe(MemberRegistered::class)]
    public function onMemberRegistered(MemberRegistered $event): void
    {
        $this->commandBus->dispatch(new JoinCommunity($event->communityId, $event->id, $event->role));
    }
}
