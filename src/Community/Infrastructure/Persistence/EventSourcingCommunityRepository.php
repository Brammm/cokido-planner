<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Infrastructure\Persistence;

use CokidoPlanner\Community\Domain\Community\Community;
use CokidoPlanner\Community\Domain\Community\CommunityRepository;
use Override;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Patchlevel\EventSourcing\Repository\Repository;
use Patchlevel\EventSourcing\Repository\RepositoryManager;

final class EventSourcingCommunityRepository implements CommunityRepository
{
    /** @var Repository<Community> */
    private Repository $repository;

    public function __construct(RepositoryManager $repositoryManager)
    {
        $this->repository = $repositoryManager->get(Community::class);
    }

    #[Override]
    public function load(AggregateRootId $id): Community
    {
        return $this->repository->load($id);
    }

    #[Override]
    public function has(AggregateRootId $id): bool
    {
        return $this->repository->has($id);
    }

    #[Override]
    public function save(AggregateRoot $aggregate): void
    {
        $this->repository->save($aggregate);
    }
}
