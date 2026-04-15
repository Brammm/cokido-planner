<?php

declare(strict_types=1);

namespace Brammm\Smart\Psr7;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;

final class DefaultResponses
{
    public static function empty(): ResponseInterface
    {
        return new Psr17Factory()->createResponse(204);
    }
    
    public static function json($data, int $responseCode = 200): ResponseInterface
    {
        $responseFactory = new Psr17Factory();
        $response = $responseFactory->createResponse($responseCode);
        
        $response = $response->withHeader('Content-Type', 'application/json');
        
        $response->getBody()->write(json_encode($data));
        
        return $response;
    }
}
