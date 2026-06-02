<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Domain\Community;

use CokidoPlanner\Community\Domain\Member\MemberId;

final class Members
{
    /** @var array<string, MemberRole> */
    private array $members;

    public static function create(): self
    {
        $self = new self();
        $self->members = [];

        return $self;
    }

    public function add(MemberId $memberId, MemberRole $role): void
    {
        $this->members[$memberId->toString()] = $role;
    }

    public function roleFor(MemberId $id): ?MemberRole
    {
        return $this->members[$id->toString()] ?? null;
    }
}
