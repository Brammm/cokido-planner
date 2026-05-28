<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Domain\Member;

use Override;
use Patchlevel\EventSourcing\Aggregate\AggregateRoot;
use Patchlevel\EventSourcing\Aggregate\AggregateRootId;
use Patchlevel\EventSourcing\Repository\Repository;
use Patchlevel\EventSourcing\Repository\RepositoryManager;

/**
 * @implements Repository<Member>
 */
final class MemberRepository implements Repository
{
    /** @var Repository<Member> */
    private Repository $repository;

    public function __construct(RepositoryManager $repositoryManager)
    {
        $this->repository = $repositoryManager->get(Member::class);
    }

    #[Override]
    public function load(AggregateRootId $id): Member
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
