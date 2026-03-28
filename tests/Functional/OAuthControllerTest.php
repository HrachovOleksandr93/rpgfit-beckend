<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\User\Entity\LinkedAccount;
use App\Domain\User\Entity\User;
use App\Domain\User\Enum\OAuthProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Functional tests for POST /api/auth/oauth and POST /api/auth/link-account.
 *
 * Tests OAuth login flow (new user, existing user, existing linked account)
 * and the account linking endpoint.
 */
class OAuthControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();
        $schemaTool = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    private function createTestUser(string $login = 'hero@rpgfit.com', string $displayName = 'TestHero'): User
    {
        $container = self::getContainer();

        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get(UserPasswordHasherInterface::class);

        /** @var EntityManagerInterface $em */
        $em = $container->get('doctrine')->getManager();

        $user = new User();
        $user->setLogin($login);
        $user->setDisplayName($displayName);
        $user->setPassword($hasher->hashPassword($user, 'SecurePass123'));
        $user->setOnboardingCompleted(true);

        $em->persist($user);
        $em->flush();

        return $user;
    }

    private function getJwtToken(User $user): string
    {
        /** @var JWTTokenManagerInterface $jwtManager */
        $jwtManager = self::getContainer()->get(JWTTokenManagerInterface::class);

        return $jwtManager->create($user);
    }

    public function testNewUserOAuthLogin(): void
    {
        // OAuth login for a completely new user (no existing user with this email)
        $this->client->request(
            'POST',
            '/api/auth/oauth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'provider' => 'google',
                'providerUserId' => 'google-new-123',
                'email' => 'newuser@gmail.com',
                'token' => 'valid-token-placeholder',
            ]),
        );

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $response);
        $this->assertNotEmpty($response['token']);
        $this->assertFalse($response['onboardingCompleted']);
        $this->assertTrue($response['isNewUser']);
    }

    public function testExistingUserOAuthLogin(): void
    {
        // Create a user, then create a linked account for them
        $user = $this->createTestUser('existing@gmail.com');

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();

        $linkedAccount = new LinkedAccount();
        $linkedAccount->setUser($user);
        $linkedAccount->setProvider(OAuthProvider::Google);
        $linkedAccount->setProviderUserId('google-existing-456');
        $linkedAccount->setEmail('existing@gmail.com');
        $em->persist($linkedAccount);
        $em->flush();

        // Now log in via OAuth with the same provider credentials
        $this->client->request(
            'POST',
            '/api/auth/oauth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'provider' => 'google',
                'providerUserId' => 'google-existing-456',
                'email' => 'existing@gmail.com',
                'token' => 'valid-token-placeholder',
            ]),
        );

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $response);
        $this->assertTrue($response['onboardingCompleted']);
        $this->assertFalse($response['isNewUser']);
    }

    public function testLinkAccount(): void
    {
        $user = $this->createTestUser();
        $token = $this->getJwtToken($user);

        $this->client->request(
            'POST',
            '/api/auth/link-account',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            json_encode([
                'provider' => 'apple',
                'providerUserId' => 'apple-link-789',
                'email' => 'hero@icloud.com',
                'token' => 'valid-apple-token',
            ]),
        );

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $response);
    }

    public function testLinkAccountAlreadyLinkedToAnotherUser(): void
    {
        // Create two users with distinct display names
        $user1 = $this->createTestUser('user1@rpgfit.com', 'HeroOne');
        $user2 = $this->createTestUser('user2@rpgfit.com', 'HeroTwo');

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();

        // Link the provider account to user1
        $linkedAccount = new LinkedAccount();
        $linkedAccount->setUser($user1);
        $linkedAccount->setProvider(OAuthProvider::Facebook);
        $linkedAccount->setProviderUserId('fb-shared-000');
        $linkedAccount->setEmail('shared@facebook.com');
        $em->persist($linkedAccount);
        $em->flush();

        // Try to link the same provider account from user2 -- should get 409
        $token2 = $this->getJwtToken($user2);

        $this->client->request(
            'POST',
            '/api/auth/link-account',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token2],
            json_encode([
                'provider' => 'facebook',
                'providerUserId' => 'fb-shared-000',
                'email' => 'shared@facebook.com',
                'token' => 'valid-fb-token',
            ]),
        );

        $this->assertResponseStatusCodeSame(409);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
        $this->assertStringContainsString('already linked', $response['error']);
    }

    public function testOAuthAutoLinksExistingUserByEmail(): void
    {
        // Create a user with email registration, then OAuth login with same email
        $this->createTestUser('existing@gmail.com');

        $this->client->request(
            'POST',
            '/api/auth/oauth',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'provider' => 'google',
                'providerUserId' => 'google-auto-link-789',
                'email' => 'existing@gmail.com',
                'token' => 'valid-token',
            ]),
        );

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $response);
        // User already existed, so it is NOT a new user
        $this->assertFalse($response['isNewUser']);
        $this->assertTrue($response['onboardingCompleted']);
    }
}
