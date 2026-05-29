<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Domain\Member;

use Patchlevel\EventSourcing\Repository\Repository;

/**
 * @extends Repository<Member>
 */
interface MemberRepository extends Repository
{
}
