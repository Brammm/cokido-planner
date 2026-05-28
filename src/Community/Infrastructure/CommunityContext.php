<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Infrastructure;

use Brammm\Smart\Context;
use Brammm\Smart\Psr7\DefaultResponses;
use Override;
use Slim\App;

final class CommunityContext implements Context
{
    #[Override]
    public function routes(App $app): void
    {
        $app->get('/', static fn() => DefaultResponses::json(['message' => 'Hello World!']));
    }

    #[Override]
    public function dependencies(): array
    {
        return [];
    }
}
