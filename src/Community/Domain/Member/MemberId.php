<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Domain\Member;

use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Patchlevel\EventSourcing\Aggregate\RamseyUuidV7Behaviour;

final class MemberId implements AggregateRootId
{
    use RamseyUuidV7Behaviour;
}
