<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Domain\Community;

use CokidoPlanner\Community\Domain\Member\MemberId;
use CokidoPlanner\Community\Infrastructure\EventSourcing\NameNormalizer;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event('community.founded')]
final readonly class CommunityFounded
{
    public function __construct(
        public CommunityId $id,
        #[NameNormalizer]
        public Name $name,
        public MemberId $foundingMember,
    ) {}
}
