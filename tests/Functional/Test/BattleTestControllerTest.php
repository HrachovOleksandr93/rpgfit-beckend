<?php

declare(strict_types=1);

namespace App\Tests\Functional\Test;

use App\Domain\Config\Entity\GameSetting;
use App\Domain\Mob\Entity\Mob;
use App\Domain\User\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;

class BattleTestControllerTest extends AbstractTestHarnessFunctionalTest
{
    protected function setUp(): void
    {
        parent::setUp();

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();

        // Battle calculator reads many extra settings; add the minimum set.
        foreach ([
            ['battle', 'base_damage_per_tick', '5'],
            ['battle', 'tick_seconds', '30'],
            ['battle', 'completion_threshold_pct', '80'],
        ] as [$category, $key, $value]) {
            $s = new GameSetting();
            $s->setCategory($category);
            $s->setKey($key);
            $s->setValue($value);
            $em->persist($s);
        }

        // Seed a mob so BattleMobService always has something to pick.
        $mob = new Mob();
        $mob->setName('Target dummy');
        $mob->setSlug('target-dummy');
        $mob->setLevel(1);
        $mob->setHp(50);
        $mob->setXpReward(25);
        $em->persist($mob);

        $em->flush();
    }

    public function testSimulateBattleReturnsResult(): void
    {
        $this->createUserWithRole('tester@rpgfit.test', UserRole::TESTER);
        $token = $this->login('tester@rpgfit.test');

        $response = $this->jsonRequest('POST', '/api/test/battle/simulate', $token, [
            'mobSlug' => 'target-dummy',
            'mode' => 'recommended',
            'damageMultiplier' => 2.0,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('performanceTier', $response['data']);
        $this->assertArrayHasKey('xpAwarded', $response['data']);
    }

    public function testGateOffReturns404(): void
    {
        // Flip the env off at runtime for this test only.
        $originalEnv = $_SERVER['APP_TESTING_ENABLED'] ?? null;
        $_SERVER['APP_TESTING_ENABLED'] = 'false';
        $_ENV['APP_TESTING_ENABLED'] = 'false';

        try {
            $this->createUserWithRole('tester@rpgfit.test', UserRole::TESTER);
            $token = $this->login('tester@rpgfit.test');

            $this->jsonRequest('POST', '/api/test/battle/simulate', $token, [
                'mobSlug' => 'target-dummy',
                'mode' => 'recommended',
            ]);

            $this->assertResponseStatusCodeSame(404);
        } finally {
            if ($originalEnv === null) {
                unset($_SERVER['APP_TESTING_ENABLED'], $_ENV['APP_TESTING_ENABLED']);
            } else {
                $_SERVER['APP_TESTING_ENABLED'] = $originalEnv;
                $_ENV['APP_TESTING_ENABLED'] = $originalEnv;
            }
        }
    }
}
