<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Infrastructure\EventSourcing;

use CokidoPlanner\Community\Domain\Community\Name;

interface CommunityWithNameExists
{
    public function __invoke(Name $name): bool;
}
