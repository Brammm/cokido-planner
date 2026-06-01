<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Domain\Community;

use RuntimeException;

final class CommunityWithNameAlreadyExists extends RuntimeException
{
    public function __construct(Name $name)
    {
        parent::__construct(sprintf('A community with the name "%s" already exists', $name->toString()));
    }
}
