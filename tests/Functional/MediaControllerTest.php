<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\User\Entity\User;
use App\Domain\User\Enum\ActivityLevel;
use App\Domain\User\Enum\DesiredGoal;
use App\Domain\User\Enum\WorkoutType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Functional tests for MediaController upload and metadata endpoints.
 *
 * Tests image upload flow, authentication requirements, MIME type validation,
 * entity type validation, and LiipImagine thumbnail URL generation.
 *
 * Media pipeline:
 * - Upload stores file in public/uploads/{entityType}/{uuid}.{ext}
 * - Response includes original URL and LiipImagine thumbnail URLs
 * - Allowed entity types: items, skills, characters, mobs
 */
class MediaControllerTest extends AbstractFunctionalTest
{

    protected function tearDown(): void
    {
        // Clean up any uploaded test files from all entity type directories
        $projectDir = self::getContainer()->getParameter('kernel.project_dir');
        $entityTypes = ['items', 'skills', 'characters', 'mobs'];

        foreach ($entityTypes as $type) {
            $uploadDir = $projectDir . '/public/uploads/' . $type;
            if (is_dir($uploadDir)) {
                $files = glob($uploadDir . '/*');
                foreach ($files as $file) {
                    if (is_file($file) && basename($file) !== '.gitkeep') {
                        unlink($file);
                    }
                }
            }
        }

        parent::tearDown();
    }

    private function createTestUser(string $login = 'hero@rpgfit.com', string $password = 'SecurePass123'): User
    {
        $container = self::getContainer();

        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get(UserPasswordHasherInterface::class);

        /** @var EntityManagerInterface $em */
        $em = $container->get('doctrine')->getManager();

        $user = new User();
        $user->setLogin($login);
        $user->setDisplayName('TestHero');
        $user->setHeight(180.0);
        $user->setWeight(75.5);
        $user->setWorkoutType(WorkoutType::Cardio);
        $user->setActivityLevel(ActivityLevel::Active);
        $user->setDesiredGoal(DesiredGoal::LoseWeight);
        $user->setPassword($hasher->hashPassword($user, $password));

        $em->persist($user);
        $em->flush();

        return $user;
    }

    private function getToken(string $login = 'hero@rpgfit.com', string $password = 'SecurePass123'): string
    {
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['login' => $login, 'password' => $password]),
        );

        $response = json_decode($this->client->getResponse()->getContent(), true);

        return $response['token'];
    }

    /** Create a temporary PNG image file for upload testing. */
    private function createTestImage(): UploadedFile
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_img_') . '.png';

        // Minimal valid 1x1 PNG file (binary)
        $pngBinary = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAC0lEQVQI12NgAAIABQAB'
            . 'Nl7BcQAAAABJRU5ErkJggg=='
        );
        file_put_contents($tmpFile, $pngBinary);

        return new UploadedFile(
            $tmpFile,
            'test-image.png',
            'image/png',
            null,
            true, // test mode
        );
    }

    public function testUploadImage(): void
    {
        $this->createTestUser();
        $token = $this->getToken();

        $file = $this->createTestImage();

        $this->client->request(
            'POST',
            '/api/media/upload',
            ['entityType' => 'items'],
            ['file' => $file],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->assertResponseStatusCodeSame(201);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('url', $response);
        $this->assertArrayHasKey('originalFilename', $response);
        $this->assertArrayHasKey('filename', $response);
        $this->assertArrayHasKey('thumbnails', $response);
        $this->assertArrayHasKey('entityType', $response);
        $this->assertArrayHasKey('mimeType', $response);
        $this->assertArrayHasKey('fileSize', $response);
        // Original URL should point to the items subdirectory
        $this->assertStringStartsWith('/uploads/items/', $response['url']);
        $this->assertSame('items', $response['entityType']);
    }

    /** Verify the response includes LiipImagine thumbnail URLs for all filter sets */
    public function testUploadResponseIncludesThumbnailUrls(): void
    {
        $this->createTestUser();
        $token = $this->getToken();

        $file = $this->createTestImage();

        $this->client->request(
            'POST',
            '/api/media/upload',
            ['entityType' => 'items'],
            ['file' => $file],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->assertResponseStatusCodeSame(201);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $thumbnails = $response['thumbnails'];

        // Each thumbnail URL should reference the correct LiipImagine filter and entity type
        $this->assertArrayHasKey('icon', $thumbnails);
        $this->assertArrayHasKey('thumbnail', $thumbnails);
        $this->assertArrayHasKey('medium', $thumbnails);

        $this->assertStringStartsWith('/media/cache/resolve/icon/items/', $thumbnails['icon']);
        $this->assertStringStartsWith('/media/cache/resolve/thumbnail/items/', $thumbnails['thumbnail']);
        $this->assertStringStartsWith('/media/cache/resolve/medium/items/', $thumbnails['medium']);
    }

    /** Verify that entityType maps to the correct directory in the URL */
    public function testEntityTypeMapsToCorrectDirectory(): void
    {
        $this->createTestUser();
        $token = $this->getToken();

        $file = $this->createTestImage();

        $this->client->request(
            'POST',
            '/api/media/upload',
            ['entityType' => 'skills'],
            ['file' => $file],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->assertResponseStatusCodeSame(201);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringStartsWith('/uploads/skills/', $response['url']);
        $this->assertSame('skills', $response['entityType']);
        $this->assertStringContainsString('skills/', $response['thumbnails']['icon']);
    }

    /** Verify invalid entity type returns 422 */
    public function testUploadInvalidEntityTypeReturns422(): void
    {
        $this->createTestUser();
        $token = $this->getToken();

        $file = $this->createTestImage();

        $this->client->request(
            'POST',
            '/api/media/upload',
            ['entityType' => 'weapons'],
            ['file' => $file],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->assertResponseStatusCodeSame(422);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
        $this->assertStringContainsString('Invalid entity type', $response['error']);
    }

    public function testUploadWithoutAuthReturns401(): void
    {
        $file = $this->createTestImage();

        $this->client->request(
            'POST',
            '/api/media/upload',
            ['entityType' => 'items'],
            ['file' => $file],
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testUploadInvalidMimeTypeReturns422(): void
    {
        $this->createTestUser();
        $token = $this->getToken();

        // Create a text file (not an image)
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_txt_');
        file_put_contents($tmpFile, 'This is not an image');

        $file = new UploadedFile(
            $tmpFile,
            'document.txt',
            'text/plain',
            null,
            true,
        );

        $this->client->request(
            'POST',
            '/api/media/upload',
            ['entityType' => 'items'],
            ['file' => $file],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->assertResponseStatusCodeSame(422);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
    }
}
