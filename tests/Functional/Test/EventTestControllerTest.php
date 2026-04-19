<?php

declare(strict_types=1);

namespace App\Tests\Functional\Test;

use App\Domain\User\Enum\UserRole;

class EventTestControllerTest extends AbstractTestHarnessFunctionalTest
{
    public function testAdminCanTriggerEventStub(): void
    {
        $this->createUserWithRole('admin@rpgfit.test', UserRole::ADMIN);
        $token = $this->login('admin@rpgfit.test');

        $response = $this->jsonRequest('POST', '/api/test/event/trigger', $token, [
            'eventSlug' => 'day_of_rozkol',
            'durationMin' => 30,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame('day_of_rozkol', $response['data']['eventSlug']);
        // Stubbed until D5 lands — triggered flag reports false + explanatory note.
        $this->assertFalse($response['data']['triggered']);
    }

    public function testTesterIsBlockedFromEventTrigger(): void
    {
        $this->createUserWithRole('tester@rpgfit.test', UserRole::TESTER);
        $token = $this->login('tester@rpgfit.test');

        $this->jsonRequest('POST', '/api/test/event/trigger', $token, [
            'eventSlug' => 'day_of_rozkol',
        ]);

        $this->assertResponseStatusCodeSame(403);
    }
}
