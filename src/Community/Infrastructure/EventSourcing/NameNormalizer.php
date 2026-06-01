<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Infrastructure\EventSourcing;

use Attribute;
use CokidoPlanner\Community\Domain\Community\Name;
use Patchlevel\Hydrator\Normalizer\InvalidArgument;
use Patchlevel\Hydrator\Normalizer\Normalizer;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class NameNormalizer implements Normalizer
{
    public function normalize(mixed $value): string
    {
        if (!$value instanceof Name) {
            throw InvalidArgument::withWrongType(Name::class, $value);
        }

        return $value->toString();
    }

    public function denormalize(mixed $value): Name
    {
        if (!is_string($value)) {
            throw InvalidArgument::withWrongType('string', $value);
        }

        return new Name($value);
    }
}
