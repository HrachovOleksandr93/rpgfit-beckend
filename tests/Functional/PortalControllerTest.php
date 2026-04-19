<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Portal\Entity\Portal;
use App\Domain\Portal\Enum\PortalType;
use App\Domain\Shared\Enum\Realm;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Functional tests for PortalController.
 *
 * Covers:
 *  - GET /api/portals (geo query with radius clamping)
 *  - GET /api/portals/static (curated list)
 *  - GET /api/portals/{slug} (detail + 404)
 */
class PortalControllerTest extends AbstractFunctionalTest
{
    protected function setUp(): void
    {
        parent::setUp();

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();

        // Kyiv Pechersk Lavra (50.4354, 30.5570) — static
        $this->createPortal(
            $em,
            slug: 'kyiv-pecherska',
            name: 'Kyiv Pechersk',
            type: PortalType::Static,
            realm: Realm::Nav,
            lat: 50.4354,
            lng: 30.5570,
        );

        // Another close Kyiv portal, ~2km away — static
        $this->createPortal(
            $em,
            slug: 'kyiv-centre',
            name: 'Kyiv Centre',
            type: PortalType::Static,
            realm: Realm::Nav,
            lat: 50.4500,
            lng: 30.5300,
        );

        // Far portal — Berlin
        $this->createPortal(
            $em,
            slug: 'berlin-brandenburg',
            name: 'Berlin Brandenburg',
            type: PortalType::Static,
            realm: Realm::Asgard,
            lat: 52.5163,
            lng: 13.3777,
        );

        $em->flush();
    }

    private function createPortal(
        EntityManagerInterface $em,
        string $slug,
        string $name,
        PortalType $type,
        Realm $realm,
        float $lat,
        float $lng,
    ): void {
        $portal = new Portal();
        $portal->setSlug($slug)
            ->setName($name)
            ->setType($type)
            ->setRealm($realm)
            ->setLatitude($lat)
            ->setLongitude($lng)
            ->setRadiusM(200)
            ->setTier(1)
            ->setChallengeParams([]);
        $em->persist($portal);
    }

    public function testNearbyReturnsOnlyKyivPortalsForKyivCoordinates(): void
    {
        $this->client->request('GET', '/api/portals?lat=50.45&lng=30.52&radius_km=5');

        $this->assertResponseStatusCodeSame(200);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Only the two Kyiv portals are within 5 km; Berlin is excluded.
        $this->assertSame(2, $response['count']);
        $slugs = array_column($response['portals'], 'slug');
        $this->assertContains('kyiv-pecherska', $slugs);
        $this->assertContains('kyiv-centre', $slugs);
        $this->assertNotContains('berlin-brandenburg', $slugs);

        // Distances are present and sorted ascending.
        $distances = array_column($response['portals'], 'distance_km');
        $this->assertCount(2, $distances);
        $this->assertLessThanOrEqual($distances[1], $distances[0]);
    }

    public function testNearbyRequiresLatAndLng(): void
    {
        $this->client->request('GET', '/api/portals');
        $this->assertResponseStatusCodeSame(400);
    }

    public function testNearbyClampsRadius(): void
    {
        // Requesting 1000 km radius still caps at 25 km — Berlin remains excluded from Kyiv.
        $this->client->request('GET', '/api/portals?lat=50.45&lng=30.52&radius_km=1000');

        $this->assertResponseStatusCodeSame(200);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(2, $response['count']);
    }

    public function testStaticListReturnsAllStaticPortals(): void
    {
        $this->client->request('GET', '/api/portals/static');

        $this->assertResponseStatusCodeSame(200);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(3, $response['count']);
    }

    public function testShowBySlugReturnsPortal(): void
    {
        $this->client->request('GET', '/api/portals/kyiv-pecherska');

        $this->assertResponseStatusCodeSame(200);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('kyiv-pecherska', $response['slug']);
        $this->assertSame('nav', $response['realm']);
    }

    public function testShowBySlugReturns404(): void
    {
        $this->client->request('GET', '/api/portals/nonexistent');
        $this->assertResponseStatusCodeSame(404);
    }
}
