<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Application\Member;

use CokidoPlanner\Community\Domain\Community\CommunityId;
use CokidoPlanner\Community\Domain\Community\MemberRole;
use CokidoPlanner\Community\Domain\Member\MemberId;

final readonly class RegisterMember
{
    public MemberId $memberId;

    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public CommunityId $communityId,
        public MemberRole $memberRole,
    ) {
        $this->memberId = MemberId::generate();
    }
}
