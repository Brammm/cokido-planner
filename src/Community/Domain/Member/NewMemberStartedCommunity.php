<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Domain\Member;

use CokidoPlanner\Community\Domain\Community\CommunityId;
use CokidoPlanner\Community\Domain\Community\Name;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event('member.registered')]
final class NewMemberStartedCommunity
{
    public function __construct(
        public MemberId $id,
        public string $firstName,
        public string $lastName,
        public string $email,
        public CommunityId $communityId,
        public Name $communityName,
    ) {}
}
