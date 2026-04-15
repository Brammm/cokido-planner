<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Infrastructure;

use Brammm\Smart\Context;
use Brammm\Smart\Psr7\DefaultResponses;
use Slim\App;

final class CommunityContext implements Context
{
    public function routes(App $app): void
    {
        $app->get('/', static function () {
            return DefaultResponses::json(['message' => 'Hello World!']);
        });
    }

    public function dependencies(): array
    {
        return [];
    }
}
