<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Domain\Member;

use Patchlevel\EventSourcing\Attribute\Event;

#[Event('member.registered')]
final class MemberRegistered
{
    public function __construct(
        public MemberId $id,
        public string $firstName,
        public string $lastName,
        public string $email,
    ) {}
}
