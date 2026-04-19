<?php

declare(strict_types=1);

namespace App\Tests\Functional\Test;

use App\Domain\User\Enum\UserRole;

class PortalTestControllerTest extends AbstractTestHarnessFunctionalTest
{
    public function testSpawnPortalNearUser(): void
    {
        $this->createUserWithRole('tester@rpgfit.test', UserRole::TESTER);
        $token = $this->login('tester@rpgfit.test');

        $response = $this->jsonRequest('POST', '/api/test/portal/spawn-near-me', $token, [
            'lat' => 50.45,
            'lng' => 30.52,
            'radiusMeters' => 100,
            'realm' => 'olympus',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame('olympus', $response['data']['realm']);
        $this->assertSame(50.45, $response['data']['lat']);
    }
}
