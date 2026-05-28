<?php

declare(strict_types=1);

namespace Brammm\Smart\Psr7;

use InvalidArgumentException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

use function json_encode;

use const JSON_THROW_ON_ERROR;

final class DefaultResponses
{
    public static function empty(): ResponseInterface
    {
        return new Psr17Factory()->createResponse(204);
    }

    /**
     * @throws \JsonException
     * @throws \RuntimeException
     */
    public static function json(mixed $data, int $responseCode = 200): ResponseInterface
    {
        $responseFactory = new Psr17Factory();
        $response = $responseFactory->createResponse($responseCode);

        try {
            $response = $response->withHeader('Content-Type', 'application/json');
        } catch (InvalidArgumentException $e) {
            throw new RuntimeException('Unable to set Content-Type header to application/json', 0, $e);
        }

        $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR));

        return $response;
    }
}
