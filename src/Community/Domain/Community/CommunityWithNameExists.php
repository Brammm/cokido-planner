<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Domain\Community;

interface CommunityWithNameExists
{
    public function __invoke(Name $name): bool;
}
