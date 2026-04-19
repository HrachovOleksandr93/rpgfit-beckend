<?php

declare(strict_types=1);

namespace App\Tests\Functional\Test;

use App\Domain\User\Enum\UserRole;

/**
 * Covers /api/test/xp/grant, /api/test/level/set, /api/test/stats/set.
 */
class XpTestControllerTest extends AbstractTestHarnessFunctionalTest
{
    public function testTesterGrantsXpToSelf(): void
    {
        $this->createUserWithRole('tester@rpgfit.test', UserRole::TESTER);
        $token = $this->login('tester@rpgfit.test');

        $response = $this->jsonRequest('POST', '/api/test/xp/grant', $token, ['amount' => 500]);
        $this->assertResponseIsSuccessful();
        $this->assertSame(500, $response['data']['xpAdded']);
        $this->assertSame(500, $response['data']['totalXp']);
    }

    public function testForceFlagBypassesDailyCap(): void
    {
        $this->createUserWithRole('tester@rpgfit.test', UserRole::TESTER);
        $token = $this->login('tester@rpgfit.test');

        // First push to cap to occupy today's headroom.
        $this->jsonRequest('POST', '/api/test/xp/grant', $token, ['amount' => 3000]);

        // Without force, further grants should be clamped to 0.
        $noForce = $this->jsonRequest('POST', '/api/test/xp/grant', $token, ['amount' => 500]);
        $this->assertSame(0, $noForce['data']['xpAdded']);

        // With force, full amount applies.
        $force = $this->jsonRequest('POST', '/api/test/xp/grant?force=1', $token, ['amount' => 500]);
        $this->assertSame(500, $force['data']['xpAdded']);
    }

    public function testRegularUserIsForbidden(): void
    {
        $this->createUserWithRole('user@rpgfit.test', UserRole::USER);
        $token = $this->login('user@rpgfit.test');

        $this->jsonRequest('POST', '/api/test/xp/grant', $token, ['amount' => 100]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminCanSetLevelForAnotherUser(): void
    {
        $this->createUserWithRole('admin@rpgfit.test', UserRole::ADMIN);
        $tester = $this->createUserWithRole('tester@rpgfit.test', UserRole::TESTER);
        $token = $this->login('admin@rpgfit.test');

        $response = $this->jsonRequest(
            'POST',
            '/api/test/level/set',
            $token,
            [
                'level' => 5,
                'asUserId' => $tester->getId()->toRfc4122(),
                'reason' => 'playtest',
            ],
        );

        $this->assertResponseIsSuccessful();
        $this->assertSame(5, $response['data']['newLevel']);
    }
}
