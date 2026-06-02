<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Domain\Community;

use InvalidArgumentException;
use RuntimeException;

use function is_string;
use function preg_replace;
use function strlen;
use function strtolower;
use function trim;

final readonly class Name
{
    public function __construct(
        private string $name,
    ) {
        if (strlen($name) === 0) {
            throw new InvalidArgumentException('Name must not be empty');
        }
    }

    public function toString(): string
    {
        return $this->name;
    }

    public function slug(): string
    {
        $cleaned = preg_replace(pattern: '/[^a-z0-9]+/', replacement: '-', subject: strtolower($this->name));

        if (!is_string($cleaned)) {
            throw new RuntimeException('Unexpected return value from preg_replace');
        }

        return trim($cleaned, characters: '-');
    }

    public function equals(self $slug): bool
    {
        return $this->slug() === $slug->slug();
    }
}
