<?php

declare(strict_types=1);

namespace CokidoPlanner\Community\Application\Community;

use Brammm\Tactishun\CommandHandler\CommandHandler;
use CokidoPlanner\Community\Domain\Community\Community;
use CokidoPlanner\Community\Domain\Community\CommunityRepository;
use CokidoPlanner\Community\Domain\Member\Member;
use CokidoPlanner\Community\Domain\Member\MemberId;
use CokidoPlanner\Community\Domain\Member\MemberRepository;
use Override;

/** @implements CommandHandler<FoundCommunity> */
final readonly class FoundCommunityHandler implements CommandHandler
{
    public function __construct(
        private CommunityRepository $communityRepository,
        private MemberRepository $memberRepository,
    ) {}

    #[Override]
    public function handle(object $command): void
    {
        $memberId = MemberId::generate();
        $member = Member::register(
            $memberId,
            $command->memberDetails->firstName,
            $command->memberDetails->lastName,
            $command->memberDetails->email,
        );
        $this->memberRepository->save($member);

        $community = Community::found($command->communityId, $command->name, $memberId);

        $this->communityRepository->save($community);
    }
}
