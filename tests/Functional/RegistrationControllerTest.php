<?php

declare(strict_types=1);

namespace App\Tests\Functional;

class RegistrationControllerTest extends AbstractFunctionalTest
{
    private const string REGISTRATION_URL = '/api/registration';

    private function getValidPayload(array $overrides = []): array
    {
        return array_merge([
            'login' => 'hero@rpgfit.com',
            'password' => 'SecurePass123',
            'displayName' => 'DragonSlayer',
            'height' => 180.0,
            'weight' => 75.5,
            'workoutType' => 'cardio',
            'activityLevel' => 'active',
            'desiredGoal' => 'lose_weight',
        ], $overrides);
    }

    public function testSuccessfulRegistration(): void
    {
        $payload = $this->getValidPayload();

        $this->client->request(
            'POST',
            self::REGISTRATION_URL,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload),
        );

        $this->assertResponseStatusCodeSame(201);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $response);
        $this->assertSame('hero@rpgfit.com', $response['login']);
        $this->assertSame('DragonSlayer', $response['displayName']);
        $this->assertEquals(180.0, $response['height']);
        $this->assertEquals(75.5, $response['weight']);
        $this->assertSame('cardio', $response['workoutType']);
        $this->assertSame('active', $response['activityLevel']);
        $this->assertSame('lose_weight', $response['desiredGoal']);
        $this->assertArrayHasKey('createdAt', $response);
        $this->assertArrayHasKey('updatedAt', $response);

        // Password must NEVER be returned
        $this->assertArrayNotHasKey('password', $response);
    }

    public function testDuplicateLoginReturns409(): void
    {
        $payload = $this->getValidPayload();

        // First registration
        $this->client->request(
            'POST',
            self::REGISTRATION_URL,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload),
        );
        $this->assertResponseStatusCodeSame(201);

        // Second registration with same login
        $this->client->request(
            'POST',
            self::REGISTRATION_URL,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(array_merge($payload, ['displayName' => 'OtherName'])),
        );
        $this->assertResponseStatusCodeSame(409);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
        $this->assertStringContainsString('login', $response['error']);
    }

    public function testDuplicateDisplayNameReturns409(): void
    {
        $payload = $this->getValidPayload();

        // First registration
        $this->client->request(
            'POST',
            self::REGISTRATION_URL,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload),
        );
        $this->assertResponseStatusCodeSame(201);

        // Second registration with same displayName but different login
        $this->client->request(
            'POST',
            self::REGISTRATION_URL,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(array_merge($payload, ['login' => 'other@rpgfit.com'])),
        );
        $this->assertResponseStatusCodeSame(409);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
        $this->assertStringContainsString('display name', $response['error']);
    }

    public function testInvalidDataReturns422(): void
    {
        $this->client->request(
            'POST',
            self::REGISTRATION_URL,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'login' => 'not-an-email',
                'password' => 'short',
                'displayName' => 'ab',
                'height' => -10,
                'weight' => -5,
                'workoutType' => 'invalid',
                'activityLevel' => 'invalid',
                'desiredGoal' => 'invalid',
            ]),
        );

        $this->assertResponseStatusCodeSame(422);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $response);
    }

    public function testMissingFieldsReturns422(): void
    {
        $this->client->request(
            'POST',
            self::REGISTRATION_URL,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([]),
        );

        $this->assertResponseStatusCodeSame(422);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $response);
    }

    public function testInvalidJsonBodyReturns422(): void
    {
        $this->client->request(
            'POST',
            self::REGISTRATION_URL,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'not-valid-json',
        );

        $this->assertResponseStatusCodeSame(422);
    }
}
