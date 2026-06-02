<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Infrastructure\Projection;

use CokidoPlanner\Community\Domain\Community\CommunityStartedByNewMember;
use Doctrine\DBAL\Connection;
use Patchlevel\EventSourcing\Attribute\Projector;
use Patchlevel\EventSourcing\Attribute\Setup;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Attribute\Teardown;

use function sprintf;

#[Projector(self::TABLE)]
final class CommunityProjector
{
    public const string TABLE = 'community_1';

    public function __construct(
        private readonly Connection $connection,
    ) {}

    #[Subscribe(CommunityStartedByNewMember::class)]
    public function onCommunityStarted(CommunityStartedByNewMember $communityStarted): void
    {
        $this->connection->insert(self::TABLE, [
            'id' => $communityStarted->id->toString(),
            'name' => $communityStarted->name->toString(),
            'slug' => $communityStarted->name->slug(),
        ]);
    }

    #[Setup]
    public function setup(): void
    {
        $this->connection->executeStatement(sprintf(<<<'SQL'
                CREATE TABLE IF NOT EXISTS %s (
                    id uuid, 
                    name varchar(255),
                    slug varchar(255),
                    PRIMARY KEY(id)
                )
            SQL, self::TABLE));
    }

    #[Teardown]
    public function teardown(): void
    {
        $this->connection->executeStatement(sprintf('DROP TABLE IF EXISTS %s;', self::TABLE));
    }
}
