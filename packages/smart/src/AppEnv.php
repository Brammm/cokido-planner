<?php

declare(strict_types=1);

namespace Brammm\Smart;

use Composer\Autoload\ClassLoader;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

use function assert;
use function dirname;
use function is_string;

final readonly class AppEnv
{
    public bool $debug;
    public string $cacheDir;

    /**
     * @throws \RuntimeException
     */
    public function cacheDirFromProjectRoot(): string
    {
        try {
            $reflection = new ReflectionClass(ClassLoader::class);
        } catch (ReflectionException $e) {
            throw new RuntimeException('Could not find composer autoloader');
        }
        $filename = $reflection->getFileName();
        assert(is_string($filename), description: 'Filename must be as string');
        $projectRoot = dirname($filename, levels: 3);

        return $projectRoot . '/' . $this->cacheDir;
    }
}
