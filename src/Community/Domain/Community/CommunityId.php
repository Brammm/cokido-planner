<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Domain\Community;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Patchlevel\EventSourcing\Aggregate\RamseyUuidV7Behaviour;

final class CommunityId implements AggregateRootId
{
    use RamseyUuidV7Behaviour;
}
