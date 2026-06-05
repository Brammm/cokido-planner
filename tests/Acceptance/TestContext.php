<?php

declare(strict_types=1);

namespace Tests\Acceptance;

use Brammm\Smart\Context;
use CokidoPlanner\Community\Infrastructure\Persistence\ConnectionRegistry;
use DateTimeImmutable;
use Doctrine\DBAL\DriverManager;
use Override;
use Patchlevel\EventSourcing\Clock\FrozenClock;
use Psr\Clock\ClockInterface;
use Slim\App;

final class TestContext implements Context
{
    #[Override]
    public function routes(App $app): void {}

    #[Override]
    public function dependencies(): array
    {
        return [
            ClockInterface::class => static fn() => new FrozenClock(new DateTimeImmutable('2026-04-25 10:00:00')),
            ConnectionRegistry::class => static fn() => new ConnectionRegistry(
                DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]),
                DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]),
            ),
        ];
    }
}
