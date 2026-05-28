<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Domain\Community;

use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Patchlevel\EventSourcing\Repository\Repository;
use Patchlevel\EventSourcing\Repository\RepositoryManager;

/**
 * @implements Repository<Community>
 */
final class CommunityRepository implements Repository
{
    /** @var Repository<Community> */
    private Repository $repository;
    
    public function __construct(RepositoryManager $repositoryManager) 
    {
        $this->repository = $repositoryManager->get(Community::class);
    }

    public function load(AggregateRootId $id): Community
    {
        return $this->repository->load($id);
    }

    public function has(AggregateRootId $id): bool
    {
        return $this->repository->has($id);
    }

    public function save(AggregateRoot $aggregate): void
    {
        $this->repository->save($aggregate);
    }
}
