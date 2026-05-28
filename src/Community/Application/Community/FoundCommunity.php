<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Application\Community;

use Brammm\Tactishun\HandledBy;
use CokidoPlanner\Community\Domain\Community\CommunityId;
use CokidoPlanner\Community\Domain\Community\Name;

#[HandledBy(FoundCommunityHandler::class)]
final readonly class FoundCommunity
{
    public CommunityId $communityId;

    public function __construct(
        public Name $name,
        public MemberDetails $memberDetails,
    ) {
        $this->communityId = CommunityId::generate();
    }
}
