<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Infrastructure\Http;

use Brammm\Smart\Psr7\DefaultResponses;
use Brammm\Tactishun\CommandBus;
use CokidoPlanner\Community\Application\Community\StartCommunity;
use CokidoPlanner\Community\Domain\Community\CommunityWithNameAlreadyExists;
use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\Mapper\Tree\Message\NodeMessage;
use CuyZ\Valinor\Mapper\TreeMapper;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function array_map;

final readonly class StartCommunityRequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private TreeMapper $mapper,
        private CommandBus $commandBus,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $command = $this->mapper->map(StartCommunity::class, $request);
        } catch (MappingError $error) {
            return DefaultResponses::json(['errors' => array_map(
                static fn(NodeMessage $message) => $message->path() . ': ' . $message->toString(),
                $error->messages()->toArray(),
            )], 400);
        }

        try {
            $this->commandBus->handle($command);
        } catch (CommunityWithNameAlreadyExists $e) {
            return DefaultResponses::json(['errors' => [$e->getMessage()]], 400);
        }

        return DefaultResponses::json(['id' => $command->communityId->toString()]);
    }
}
