<?php

declare(strict_types=1);

use Brammm\Smart\App;
use Brammm\Smart\AppEnv;
use CokidoPlanner\Community\Infrastructure\CommunityContext;
use Crell\EnvMapper\EnvMapper;

require __DIR__ . '/../vendor/autoload.php';

$appEnv = new EnvMapper()->map(AppEnv::class);

$app = new App($appEnv);
$app->addcontext(new CommunityContext());

$app->run();
