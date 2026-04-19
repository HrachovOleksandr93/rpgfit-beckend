<?php

declare(strict_types=1);

namespace App\Tests\Functional\Test;

use App\Domain\Inventory\Entity\ItemCatalog;
use App\Domain\Inventory\Enum\ItemRarity;
use App\Domain\Inventory\Enum\ItemType;
use App\Domain\User\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Exercises /api/test/inventory/* and /api/test/equipment/*.
 */
class InventoryTestControllerTest extends AbstractTestHarnessFunctionalTest
{
    private function seedItem(string $slug = 'iron-sword'): ItemCatalog
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();

        $item = new ItemCatalog();
        $item->setName('Iron Sword');
        $item->setSlug($slug);
        $item->setItemType(ItemType::Equipment);
        $item->setRarity(ItemRarity::Common);

        $em->persist($item);
        $em->flush();

        return $item;
    }

    public function testTesterCanGrantItemToSelf(): void
    {
        $this->seedItem();
        $this->createUserWithRole('tester@rpgfit.test', UserRole::TESTER);
        $token = $this->login('tester@rpgfit.test');

        $response = $this->jsonRequest(
            'POST',
            '/api/test/inventory/grant',
            $token,
            ['itemSlug' => 'iron-sword', 'quantity' => 3],
        );

        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('data', $response);
        $this->assertSame('iron-sword', $response['data']['itemSlug']);
        $this->assertSame(3, $response['data']['quantity']);
        $this->assertArrayHasKey('auditLogId', $response);
    }

    public function testTesterCanClearOwnInventory(): void
    {
        $this->seedItem();
        $this->createUserWithRole('tester@rpgfit.test', UserRole::TESTER);
        $token = $this->login('tester@rpgfit.test');

        // Seed two items first.
        $this->jsonRequest('POST', '/api/test/inventory/grant', $token, ['itemSlug' => 'iron-sword', 'quantity' => 1]);
        $this->jsonRequest('POST', '/api/test/inventory/grant', $token, ['itemSlug' => 'iron-sword', 'quantity' => 1]);

        $response = $this->jsonRequest('POST', '/api/test/inventory/clear', $token);
        $this->assertResponseIsSuccessful();
        $this->assertSame(2, $response['data']['clearedCount']);
    }

    public function testRegularUserIsForbidden(): void
    {
        $this->createUserWithRole('user@rpgfit.test', UserRole::USER);
        $token = $this->login('user@rpgfit.test');

        $this->jsonRequest('POST', '/api/test/inventory/clear', $token);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminCanGrantForAnotherUserWithReason(): void
    {
        $this->seedItem();
        $this->createUserWithRole('admin@rpgfit.test', UserRole::ADMIN);
        $tester = $this->createUserWithRole('tester@rpgfit.test', UserRole::TESTER);
        $token = $this->login('admin@rpgfit.test');

        $response = $this->jsonRequest(
            'POST',
            '/api/test/inventory/grant',
            $token,
            [
                'itemSlug' => 'iron-sword',
                'quantity' => 1,
                'asUserId' => $tester->getId()->toRfc4122(),
                'reason' => 'bug_repro',
            ],
        );

        $this->assertResponseIsSuccessful();
        $this->assertSame($tester->getId()->toRfc4122(), $response['target']['id']);
    }

    public function testAdminWithoutReasonIsRejected(): void
    {
        $this->seedItem();
        $this->createUserWithRole('admin@rpgfit.test', UserRole::ADMIN);
        $tester = $this->createUserWithRole('tester@rpgfit.test', UserRole::TESTER);
        $token = $this->login('admin@rpgfit.test');

        $this->jsonRequest(
            'POST',
            '/api/test/inventory/grant',
            $token,
            [
                'itemSlug' => 'iron-sword',
                'quantity' => 1,
                'asUserId' => $tester->getId()->toRfc4122(),
            ],
        );

        $this->assertResponseStatusCodeSame(422);
    }
}
