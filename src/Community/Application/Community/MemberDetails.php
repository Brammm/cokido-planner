<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Application\Community;

final readonly class MemberDetails
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
    ) {}
}
