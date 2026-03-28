<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Base class for functional tests that need a clean database schema.
 *
 * Handles MySQL FK constraint issues by disabling foreign key checks
 * before dropping the schema, then re-enabling after recreation.
 * This ensures test isolation when running all functional tests together.
 */
abstract class AbstractFunctionalTest extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->recreateSchema();
    }

    /**
     * Drop and recreate the full database schema.
     * Handles FK constraints for MySQL by temporarily disabling checks.
     */
    protected function recreateSchema(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();

        /** @var Connection $connection */
        $connection = $em->getConnection();

        // Disable FK checks to allow dropping tables in any order
        $platform = $connection->getDatabasePlatform();
        $isMysql = $platform instanceof \Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
        if ($isMysql) {
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        }

        $schemaTool = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);

        if ($isMysql) {
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
        }

        $schemaTool->createSchema($metadata);
    }
}
