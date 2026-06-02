<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Domain\Community;

use CokidoPlanner\Community\Infrastructure\EventSourcing\NameNormalizer;
use Patchlevel\EventSourcing\Attribute\Event;

#[Event('community.started.by_new_member')]
final readonly class CommunityStartedByNewMember
{
    public function __construct(
        public CommunityId $id,
        #[NameNormalizer]
        public Name $name,
        public string $memberFirstName,
        public string $memberLastName,
        public string $memberEmail,
    ) {}
}
