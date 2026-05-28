<?php

declare(strict_types=1);

namespace Brammm\Smart;

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
            $reflection = new \ReflectionClass(\Composer\Autoload\ClassLoader::class);
        } catch (\ReflectionException $e) {
            throw new \RuntimeException('Could not find composer autoloader');
        }
        $filename = $reflection->getFileName();
        assert(is_string($filename));
        $projectRoot = dirname($filename, 3);

        return $projectRoot . '/' . $this->cacheDir;
    }
}
