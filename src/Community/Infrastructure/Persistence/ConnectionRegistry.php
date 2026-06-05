<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Infrastructure\Persistence;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ConnectionRegistry as PersistenceConnectionRegistry;
use InvalidArgumentException;
use Override;

final readonly class ConnectionRegistry implements PersistenceConnectionRegistry
{
    public function __construct(
        public Connection $eventsConnection,
        public Connection $projectionsConnection,
    ) {}

    #[Override]
    public function getDefaultConnectionName(): string
    {
        return 'events';
    }

    #[Override]
    public function getConnection(?string $name = null): object
    {
        if ($name === null || $name === 'events') {
            return $this->eventsConnection;
        }

        if ($name === 'projections') {
            return $this->projectionsConnection;
        }

        throw new InvalidArgumentException(sprintf('Connection named "%s" does not exist.', $name));
    }

    #[Override]
    public function getConnections(): array
    {
        return [
            'events' => $this->eventsConnection,
            'projections' => $this->projectionsConnection,
        ];
    }

    #[Override]
    public function getConnectionNames(): array
    {
        return ['events' => 'events', 'projections' => 'projections'];
    }
}
