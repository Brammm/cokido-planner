<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Application\Community;

use CokidoPlanner\Community\Domain\Community\CommunityId;
use CokidoPlanner\Community\Domain\Community\Name;

final class StartCommunityAsNewMember
{
    public CommunityId $communityId;

    public function __construct(
        public Name $communityName,
        public string $memberFirstName,
        public string $memberLastName,
        public string $memberEmail,
    ) {
        $this->communityId = CommunityId::generate();
    }
}
