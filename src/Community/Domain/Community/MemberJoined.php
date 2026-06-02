<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Domain\Community;

use CokidoPlanner\Community\Domain\Member\MemberId;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event('community.member_joined')]
final readonly class MemberJoined
{
    public function __construct(
        public MemberId $memberId,
        public MemberRole $role,
    ) {}
}
