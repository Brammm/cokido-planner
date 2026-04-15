<?php

declare(strict_types=1);

namespace Brammm\Smart;

use Slim\App;

interface Context
{
    public function routes(App $app): void;

    /**
     * @return array<string, mixed>
     */
    public function dependencies(): array;
}
