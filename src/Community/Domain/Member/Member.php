<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Domain\Member;

use CokidoPlanner\Community\Application\Member\RegisterMember;
use Patchlevel\EventSourcing\Aggregate\BasicAggregateRoot;
use Patchlevel\EventSourcing\Attribute\Aggregate;
use Patchlevel\EventSourcing\Attribute\Apply;
use Patchlevel\EventSourcing\Attribute\Handle;
use Patchlevel\EventSourcing\Attribute\Id;

#[Aggregate('member')]
final class Member extends BasicAggregateRoot
{
    #[Id]
    private MemberId $id;

    private string $firstName;

    private string $lastName;

    private string $email;

    public function id(): MemberId
    {
        return $this->id;
    }

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

    #[Handle]
    public static function register(RegisterMember $command): static
    {
        $self = new static();
        $self->recordThat(
            new MemberRegistered(
                $command->memberId,
                $command->firstName,
                $command->lastName,
                $command->email,
                $command->communityId,
                $command->memberRole,
            ),
        );

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
