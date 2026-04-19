<?php

declare(strict_types=1);

namespace App\Tests\Functional\Test;

use App\Domain\User\Enum\UserRole;

class UserTestControllerTest extends AbstractTestHarnessFunctionalTest
{
    public function testDumpState(): void
    {
        $this->createUserWithRole('tester@rpgfit.test', UserRole::TESTER);
        $token = $this->login('tester@rpgfit.test');

        $response = $this->jsonRequest('GET', '/api/test/user/state', $token);
        $this->assertResponseIsSuccessful();
        $this->assertSame('tester@rpgfit.test', $response['data']['user']['login']);
    }

    public function testSoftResetClearsTransientState(): void
    {
        $this->createUserWithRole('tester@rpgfit.test', UserRole::TESTER);
        $token = $this->login('tester@rpgfit.test');

        // Seed some XP first.
        $this->jsonRequest('POST', '/api/test/xp/grant', $token, ['amount' => 500]);

        $response = $this->jsonRequest('POST', '/api/test/user/reset', $token);
        $this->assertResponseIsSuccessful();
        $this->assertTrue($response['data']['reset']);
        $this->assertFalse($response['data']['hard']);
    }

    public function testHardResetFlipsOnboarding(): void
    {
        $user = $this->createUserWithRole('tester@rpgfit.test', UserRole::TESTER);
        $user->setOnboardingCompleted(true);

        $em = self::getContainer()->get('doctrine')->getManager();
        $em->flush();

        $token = $this->login('tester@rpgfit.test');
        $response = $this->jsonRequest('POST', '/api/test/user/reset?hard=1', $token);

        $this->assertResponseIsSuccessful();
        $this->assertTrue($response['data']['hard']);
    }
}
