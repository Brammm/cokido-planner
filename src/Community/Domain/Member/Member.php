<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Domain\Member;

use Patchlevel\EventSourcing\Aggregate\BasicAggregateRoot;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Id;

#[Aggregate('member')]
final class Member extends BasicAggregateRoot
{
    #[Id]
    private MemberId $id;

    private string $firstName;

    private string $lastName;

    private string $email;

    public function firstName(): string
    {
        return $this->firstName;
    }

    public function lastName(): string
    {
        return $this->lastName;
    }

    public function email(): string
    {
        return $this->email;
    }

    public static function register(MemberId $id, string $firstName, string $lastName, string $email): static
    {
        $self = new static();
        $self->recordThat(new MemberRegistered($id, $firstName, $lastName, $email));

        return $self;
    }

    #[Apply]
    public function applyMemberRegistered(MemberRegistered $event): void
    {
        $this->id = $event->id;
        $this->firstName = $event->firstName;
        $this->lastName = $event->lastName;
        $this->email = $event->email;
    }
}
