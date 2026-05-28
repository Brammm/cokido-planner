<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Domain\Community;

use CokidoPlanner\Community\Domain\Member\MemberId;

final class Members
{
    /** @var array<string, MemberRole> */
    private array $members;

    public static function create(MemberId $memberId): self
    {
        $self = new self();
        $self->members = [$memberId->toString() => MemberRole::Admin];

        return $self;
    }
}
