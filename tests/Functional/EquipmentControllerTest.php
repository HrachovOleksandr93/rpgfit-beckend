<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Inventory\Entity\ItemCatalog;
use App\Domain\Inventory\Entity\UserInventory;
use App\Domain\Inventory\Enum\EquipmentSlot;
use App\Domain\Inventory\Enum\ItemRarity;
use App\Domain\Inventory\Enum\ItemType;
use App\Domain\User\Entity\User;
use App\Domain\User\Enum\ActivityLevel;
use App\Domain\User\Enum\CharacterRace;
use App\Domain\User\Enum\DesiredGoal;
use App\Domain\User\Enum\WorkoutType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Functional tests for EquipmentController endpoints.
 *
 * Tests equip/unequip/list operations via HTTP, including authentication checks,
 * validation errors, and two-handed weapon slot interactions.
 */
class EquipmentControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        /** @var EntityManagerInterface $em */
        $this->em = self::getContainer()->get('doctrine')->getManager();
        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    private function createTestUser(string $login = 'hero@rpgfit.com', string $password = 'SecurePass123'): User
    {
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setLogin($login);
        $user->setDisplayName('TestHero');
        $user->setHeight(180.0);
        $user->setWeight(75.5);
        $user->setWorkoutType(WorkoutType::Cardio);
        $user->setActivityLevel(ActivityLevel::Active);
        $user->setDesiredGoal(DesiredGoal::LoseWeight);
        $user->setCharacterRace(CharacterRace::Orc);
        $user->setPassword($hasher->hashPassword($user, $password));

        $this->em->persist($user);
        $this->em->flush();

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

    /**
     * Create an equipment catalog item and add it to a user's inventory.
     *
     * Uses a fresh EntityManager from the container to avoid detached-entity issues
     * across HTTP request boundaries in functional tests.
     */
    private function createInventoryItem(
        User $user,
        string $name,
        EquipmentSlot $slot,
        bool $twoHanded = false,
        ItemType $itemType = ItemType::Equipment,
    ): UserInventory {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();

        // Re-fetch user to ensure it is managed in the current EntityManager
        $managedUser = $em->find(User::class, $user->getId()->toRfc4122());

        $catalog = new ItemCatalog();
        $catalog->setName($name);
        $catalog->setSlug(strtolower(str_replace(' ', '-', $name)) . '-' . substr(uniqid(), -5));
        $catalog->setItemType($itemType);
        $catalog->setRarity(ItemRarity::Common);
        $catalog->setSlot($slot);
        $catalog->setTwoHanded($twoHanded);

        $em->persist($catalog);

        $inv = new UserInventory();
        $inv->setUser($managedUser);
        $inv->setItemCatalog($catalog);

        $em->persist($inv);
        $em->flush();

        return $inv;
    }

    public function testEquipItem(): void
    {
        $user = $this->createTestUser();
        $token = $this->getToken();

        $inv = $this->createInventoryItem($user, 'Iron Sword', EquipmentSlot::Weapon);

        $this->client->request(
            'POST',
            '/api/equipment/equip/' . $inv->getId()->toRfc4122(),
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->assertResponseStatusCodeSame(200);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($response['equipped']);
        $this->assertSame('weapon', $response['slot']);
    }

    public function testEquipWithoutAuthReturns401(): void
    {
        $this->client->request('POST', '/api/equipment/equip/some-uuid');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testEquipNonEquipmentItemReturns422(): void
    {
        $user = $this->createTestUser();
        $token = $this->getToken();

        // Create a potion (non-equipment)
        $catalog = new ItemCatalog();
        $catalog->setName('Health Potion');
        $catalog->setSlug('health-potion');
        $catalog->setItemType(ItemType::Potion);
        $catalog->setRarity(ItemRarity::Common);

        $this->em->persist($catalog);

        $inv = new UserInventory();
        $inv->setUser($user);
        $inv->setItemCatalog($catalog);
        $this->em->persist($inv);
        $this->em->flush();

        $this->client->request(
            'POST',
            '/api/equipment/equip/' . $inv->getId()->toRfc4122(),
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->assertResponseStatusCodeSame(422);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
    }

    public function testTwoHandedWeaponReplacesShield(): void
    {
        $user = $this->createTestUser();
        $token = $this->getToken();

        // Equip a shield first
        $shieldInv = $this->createInventoryItem($user, 'Iron Shield', EquipmentSlot::Shield);
        $this->client->request(
            'POST',
            '/api/equipment/equip/' . $shieldInv->getId()->toRfc4122(),
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );
        $this->assertResponseStatusCodeSame(200);

        // Equip a two-handed weapon
        $twoHandedInv = $this->createInventoryItem($user, 'Great Axe', EquipmentSlot::Weapon, true);
        $this->client->request(
            'POST',
            '/api/equipment/equip/' . $twoHandedInv->getId()->toRfc4122(),
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );
        $this->assertResponseStatusCodeSame(200);

        // Check equipped items: only two-handed weapon should remain
        $this->client->request(
            'GET',
            '/api/equipment',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );
        $this->assertResponseStatusCodeSame(200);
        $items = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(1, $items);
        $this->assertSame('Great Axe', $items[0]['item']);
        $this->assertSame('weapon', $items[0]['slot']);
    }

    public function testGetEquippedItems(): void
    {
        $user = $this->createTestUser();
        $token = $this->getToken();

        // Equip head and body items
        $helm = $this->createInventoryItem($user, 'Iron Helm', EquipmentSlot::Head);
        $armor = $this->createInventoryItem($user, 'Iron Armor', EquipmentSlot::Body);

        $this->client->request(
            'POST',
            '/api/equipment/equip/' . $helm->getId()->toRfc4122(),
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );
        $this->assertResponseStatusCodeSame(200);

        $this->client->request(
            'POST',
            '/api/equipment/equip/' . $armor->getId()->toRfc4122(),
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );
        $this->assertResponseStatusCodeSame(200);

        $this->client->request(
            'GET',
            '/api/equipment',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->assertResponseStatusCodeSame(200);
        $items = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(2, $items);
    }

    public function testUnequipItem(): void
    {
        $user = $this->createTestUser();
        $token = $this->getToken();

        $inv = $this->createInventoryItem($user, 'Iron Sword', EquipmentSlot::Weapon);

        // Equip
        $this->client->request(
            'POST',
            '/api/equipment/equip/' . $inv->getId()->toRfc4122(),
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );
        $this->assertResponseStatusCodeSame(200);

        // Unequip
        $this->client->request(
            'POST',
            '/api/equipment/unequip/' . $inv->getId()->toRfc4122(),
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );
        $this->assertResponseStatusCodeSame(200);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($response['equipped']);

        // Verify no equipped items
        $this->client->request(
            'GET',
            '/api/equipment',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );
        $items = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(0, $items);
    }
}
