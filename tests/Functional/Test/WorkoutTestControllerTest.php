<?php

declare(strict_types=1);

namespace App\Tests\Functional\Test;

use App\Domain\User\Enum\UserRole;

class WorkoutTestControllerTest extends AbstractTestHarnessFunctionalTest
{
    public function testLogWorkoutReturnsIdentifier(): void
    {
        $this->createUserWithRole('tester@rpgfit.test', UserRole::TESTER);
        $token = $this->login('tester@rpgfit.test');

        $response = $this->jsonRequest('POST', '/api/test/workout/log', $token, [
            'workoutType' => 'running',
            'durationMinutes' => 30,
            'calories' => 200,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertNotEmpty($response['data']['workoutLogId']);
    }

    public function testSimulateStreamEmitsPointsAndLog(): void
    {
        $this->createUserWithRole('tester@rpgfit.test', UserRole::TESTER);
        $token = $this->login('tester@rpgfit.test');

        $response = $this->jsonRequest('POST', '/api/test/workout/simulate-stream', $token, [
            'samples' => 6,
            'durationSeconds' => 60,
            'heartRate' => 140,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame(6, $response['data']['samplesEmitted']);
        $this->assertGreaterThan(0, $response['data']['insertedCount']);
        $this->assertNotEmpty($response['data']['workoutLogId']);
    }
}
