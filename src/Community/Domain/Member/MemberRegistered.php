<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Domain\Member;

use CokidoPlanner\Community\Domain\Community\CommunityId;
use CokidoPlanner\Community\Domain\Community\MemberRole;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event('member.registered')]
final class MemberRegistered
{
    public function __construct(
        public MemberId $id,
        public string $firstName,
        public string $lastName,
        public string $email,
        public CommunityId $communityId,
        public MemberRole $role,
    ) {}
}
