<?php

declare(strict_types=1);

namespace Tests\Unit\Community\Domain\Community;

use CokidoPlanner\Community\Domain\Community\Name;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Name::class)]
final class NameTest extends TestCase
{
    #[DataProvider('provideNames')]
    public function testItOutputsCorrectSlugForName(string $name, string $expects): void
    {
        $slug = new Name($name);
        
        self::assertSame($expects, $slug->slug());
    }

    public static function provideNames(): \Generator
    {
        yield ['Community', 'community'];
        yield [' Community ', 'community'];
        yield ['Community Group', 'community-group'];
        yield ['Community - Group', 'community-group'];
        yield ['Community - Group2', 'community-group2'];
        yield ['Community - Group 2', 'community-group-2'];
        yield ['Community!', 'community'];
    }
}
