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
    public function __construct(
        public bool $debug,
        public string $cacheDir,
    )
    {
    }

    /**
     * @throws \RuntimeException
     */
    public function cacheDirFromProjectRoot(): string
    {
        // If the configured cache dir is readable, return that immediately
        if (is_dir($this->cacheDir) && is_writable($this->cacheDir)) {
            return $this->cacheDir;
        }
        
        try {
            $reflection = new ReflectionClass(ClassLoader::class);
        } catch (ReflectionException $e) {
            throw new RuntimeException('Could not find composer autoloader');
        }
        $filename = $reflection->getFileName();
        assert(is_string($filename), description: 'Filename must be as string');
        $projectRoot = dirname($filename, levels: 3);

        $cacheDirFromRoot = $projectRoot . '/' . $this->cacheDir;

        if (is_dir($cacheDirFromRoot) && is_writable($cacheDirFromRoot)) {
            return $cacheDirFromRoot;
        }
        
        throw new RuntimeException(sprintf('Cache dir %s does not exist or is not writable', $cacheDirFromRoot));
    }
}
