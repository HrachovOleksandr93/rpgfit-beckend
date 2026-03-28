<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Inventory\Enum\ItemRarity;
use App\Domain\Mob\Entity\Mob;
use Doctrine\ORM\EntityManagerInterface;

class MobControllerTest extends AbstractFunctionalTest
{
    protected function setUp(): void
    {
        parent::setUp();

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();

        // Seed test mobs
        $this->createMob($em, 'Grey Wolf', 'grey-wolf-3', 3, 200, 15, ItemRarity::Common);
        $this->createMob($em, 'Black Wolf', 'black-wolf-3', 3, 230, 18, ItemRarity::Common);
        $this->createMob($em, 'Fire Dragon', 'fire-dragon-50', 50, 15000, 1200, ItemRarity::Epic);
        $this->createMob($em, 'Forest Spider', 'forest-spider-1', 1, 80, 5, ItemRarity::Common);
        $em->flush();
    }

    private function createMob(
        EntityManagerInterface $em,
        string $name,
        string $slug,
        int $level,
        int $hp,
        int $xpReward,
        ?ItemRarity $rarity = null,
    ): void {
        $mob = new Mob();
        $mob->setName($name)
            ->setSlug($slug)
            ->setLevel($level)
            ->setHp($hp)
            ->setXpReward($xpReward)
            ->setRarity($rarity);
        $em->persist($mob);
    }

    public function testListMobsReturnsAll(): void
    {
        $this->client->request('GET', '/api/mobs');

        $this->assertResponseStatusCodeSame(200);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(4, $response['count']);
        $this->assertCount(4, $response['mobs']);
    }

    public function testListMobsFilterByLevel(): void
    {
        $this->client->request('GET', '/api/mobs?level=3');

        $this->assertResponseStatusCodeSame(200);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(2, $response['count']);
    }

    public function testListMobsFilterByLevelRange(): void
    {
        $this->client->request('GET', '/api/mobs?level_min=3&level_max=50');

        $this->assertResponseStatusCodeSame(200);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(3, $response['count']);
    }

    public function testListMobsFilterByRarity(): void
    {
        $this->client->request('GET', '/api/mobs?rarity=epic');

        $this->assertResponseStatusCodeSame(200);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(1, $response['count']);
        $this->assertSame('Fire Dragon', $response['mobs'][0]['name']);
    }

    public function testShowMobBySlug(): void
    {
        $this->client->request('GET', '/api/mobs/grey-wolf-3');

        $this->assertResponseStatusCodeSame(200);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Grey Wolf', $response['name']);
        $this->assertSame(3, $response['level']);
        $this->assertSame(200, $response['hp']);
        $this->assertSame(15, $response['xpReward']);
        $this->assertSame('common', $response['rarity']);
    }

    public function testShowMobNotFound(): void
    {
        $this->client->request('GET', '/api/mobs/nonexistent');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testListMobsPagination(): void
    {
        $this->client->request('GET', '/api/mobs?limit=2&offset=0');

        $this->assertResponseStatusCodeSame(200);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(2, $response['count']);
    }

    public function testMobsArePublicNoAuthRequired(): void
    {
        // No auth token needed
        $this->client->request('GET', '/api/mobs');
        $this->assertResponseStatusCodeSame(200);

        $this->client->request('GET', '/api/mobs/grey-wolf-3');
        $this->assertResponseStatusCodeSame(200);
    }
}
