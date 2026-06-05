<?php

declare(strict_types=1);

namespace Tests\Acceptance\Community;

use CokidoPlanner\Community\Domain\Community\CommunityId;
use CokidoPlanner\Community\Domain\Community\CommunityStartedByNewMember;
use CokidoPlanner\Community\Domain\Community\MemberJoined;
use CokidoPlanner\Community\Domain\Community\MemberRole;
use CokidoPlanner\Community\Domain\Community\Name;
use CokidoPlanner\Community\Domain\Member\MemberId;
use CokidoPlanner\Community\Domain\Member\MemberRegistered;
use CokidoPlanner\Community\Infrastructure\Http\StartCommunityRequestHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Acceptance\AcceptanceTestCase;

#[CoversClass(StartCommunityRequestHandler::class)]
final class StartCommunityTest extends AcceptanceTestCase
{
    public function testItStartsACommunityForANewMember(): void
    {
        $response = $this->post('/community.start', [
            'community_name' => 'Edugo Meerhout',
            'member_first_name' => 'Bram',
            'member_last_name' => 'Van der Sype',
            'member_email' => 'bram.vandersype@gmail.com',
        ]);

        self::assertResponseStatusCode(200, $response);
        $this->assertEvents([
            new CommunityStartedByNewMember(
                CommunityId::fromString('10000000-7000-0000-0000-000000000001'),
                new Name('Edugo Meerhout'),
                'Bram',
                'Van der Sype',
                'bram.vandersype@gmail.com',
            ),
            new MemberRegistered(
                MemberId::fromString('10000000-7000-0000-0000-000000000002'),
                'Bram',
                'Van der Sype',
                'bram.vandersype@gmail.com',
                CommunityId::fromString('10000000-7000-0000-0000-000000000001'),
                MemberRole::Admin,
            ),
            new MemberJoined(MemberId::fromString('10000000-7000-0000-0000-000000000002'), MemberRole::Admin),
        ]);
    }
}
