<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Domain\Community;

use CokidoPlanner\Community\Domain\Member\MemberId;

final class Members
{
    /** @var array<string, MemberRole> */
    private array $members;

    public static function create(MemberId $id): self
    {
        $self = new self();
        $self->members = [$id->toString() => MemberRole::Admin];

        return $self;
    }

    public function role(MemberId $id): ?MemberRole
    {
        return $this->members[$id->toString()] ?? null;
    }
}
