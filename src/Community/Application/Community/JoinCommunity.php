<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Application\Community;

use CokidoPlanner\Community\Domain\Community\CommunityId;
use CokidoPlanner\Community\Domain\Community\MemberRole;
use CokidoPlanner\Community\Domain\Member\MemberId;
use Patchlevel\EventSourcing\Attribute\Id;

final readonly class JoinCommunity
{
    public function __construct(
        #[Id]
        public CommunityId $id,
        public MemberId $memberId,
        public MemberRole $role,
    ) {}
}
