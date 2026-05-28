<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Domain\Community;

final readonly class Name
{
    public function __construct(
        private string $name,
    ) {
    }

    public function toString(): string
    {
        return $this->name;
    }
    
    public function slug(): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($this->name)), '-');
    }

    public function equals(self $slug): bool
    {
        return $this->slug() === $slug->slug();
    }
}
