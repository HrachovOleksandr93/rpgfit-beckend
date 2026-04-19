<?php

declare(strict_types=1);

namespace App\Tests\Functional\Test;

use App\Domain\Inventory\Entity\ItemCatalog;
use App\Domain\Inventory\Enum\ItemRarity;
use App\Domain\Inventory\Enum\ItemType;
use App\Domain\User\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;

class AuditTestControllerTest extends AbstractTestHarnessFunctionalTest
{
    private function seedItem(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();
        $item = new ItemCatalog();
        $item->setName('Iron Sword');
        $item->setSlug('iron-sword');
        $item->setItemType(ItemType::Equipment);
        $item->setRarity(ItemRarity::Common);
        $em->persist($item);
        $em->flush();
    }

    public function testAdminSeesRecentActions(): void
    {
        $this->seedItem();

        $this->createUserWithRole('admin@rpgfit.test', UserRole::ADMIN);
        $this->createUserWithRole('tester@rpgfit.test', UserRole::TESTER);

        $testerToken = $this->login('tester@rpgfit.test');
        $this->jsonRequest('POST', '/api/test/inventory/grant', $testerToken, [
            'itemSlug' => 'iron-sword',
            'quantity' => 1,
        ]);

        $adminToken = $this->login('admin@rpgfit.test');
        $response = $this->jsonRequest('GET', '/api/test/audit/recent?limit=10', $adminToken);

        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('items', $response);
        $this->assertGreaterThan(0, $response['count']);
        $this->assertSame('inventory.grant', $response['items'][0]['action']);
    }

    public function testTesterIsForbidden(): void
    {
        $this->createUserWithRole('tester@rpgfit.test', UserRole::TESTER);
        $token = $this->login('tester@rpgfit.test');

        $this->jsonRequest('GET', '/api/test/audit/recent', $token);
        $this->assertResponseStatusCodeSame(403);
    }
}
