<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\PsychProfile;

use App\Application\PsychProfile\Service\StatusAssignmentService;
use App\Domain\Config\Entity\GameSetting;
use App\Domain\PsychProfile\Enum\MoodQuadrant;
use App\Domain\PsychProfile\Enum\PsychStatus;
use App\Domain\PsychProfile\Enum\UserIntent;
use App\Infrastructure\Config\Repository\GameSettingRepository;
use PHPUnit\Framework\TestCase;

/**
 * Anchors the deterministic §3 rule table against the production JSON
 * config. Every case reflects one of the five rules or an edge around
 * energy clamping / no-match default.
 */
final class StatusAssignmentServiceTest extends TestCase
{
    private function service(?string $rulesJson = null): StatusAssignmentService
    {
        $repo = $this->createMock(GameSettingRepository::class);

        if ($rulesJson !== null) {
            $setting = (new GameSetting())
                ->setCategory('psych')
                ->setKey(StatusAssignmentService::SETTING_KEY)
                ->setValue($rulesJson);
            $repo->method('findByKey')->willReturn($setting);
        } else {
            $repo->method('findByKey')->willReturn(null);
        }

        return new StatusAssignmentService($repo);
    }

    /** @return string JSON encoding of the spec §3 rule order. */
    private function seededRulesJson(): string
    {
        return (string) json_encode([
            ['when' => ['mood' => ['ON_EDGE']], 'assign' => 'SCATTERED'],
            ['when' => ['mood' => ['DRAINED']], 'assign' => 'WEARY'],
            ['when' => ['mood' => ['AT_EASE', 'NEUTRAL'], 'intent' => ['REST']], 'assign' => 'DORMANT'],
            ['when' => ['mood' => ['ENERGIZED'], 'intent' => ['PUSH'], 'energy_min' => 4], 'assign' => 'CHARGED'],
            ['when' => [], 'assign' => 'STEADY'],
        ]);
    }

    public function testOnEdgeMapsToScatteredRegardlessOfOtherFields(): void
    {
        $service = $this->service($this->seededRulesJson());
        self::assertSame(
            PsychStatus::SCATTERED,
            $service->assign(MoodQuadrant::ON_EDGE, 5, UserIntent::PUSH),
        );
    }

    public function testDrainedMapsToWearyRegardlessOfOtherFields(): void
    {
        $service = $this->service($this->seededRulesJson());
        self::assertSame(
            PsychStatus::WEARY,
            $service->assign(MoodQuadrant::DRAINED, 1, UserIntent::REST),
        );
    }

    public function testAtEaseRestMapsToDormant(): void
    {
        $service = $this->service($this->seededRulesJson());
        self::assertSame(
            PsychStatus::DORMANT,
            $service->assign(MoodQuadrant::AT_EASE, 2, UserIntent::REST),
        );
    }

    public function testNeutralRestMapsToDormant(): void
    {
        $service = $this->service($this->seededRulesJson());
        self::assertSame(
            PsychStatus::DORMANT,
            $service->assign(MoodQuadrant::NEUTRAL, 3, UserIntent::REST),
        );
    }

    public function testEnergizedPushHighEnergyMapsToCharged(): void
    {
        $service = $this->service($this->seededRulesJson());
        self::assertSame(
            PsychStatus::CHARGED,
            $service->assign(MoodQuadrant::ENERGIZED, 4, UserIntent::PUSH),
        );
    }

    public function testEnergizedPushLowEnergyFallsThroughToSteady(): void
    {
        $service = $this->service($this->seededRulesJson());
        self::assertSame(
            PsychStatus::STEADY,
            $service->assign(MoodQuadrant::ENERGIZED, 3, UserIntent::PUSH),
        );
    }

    public function testNeutralMaintainFallsThroughToSteady(): void
    {
        $service = $this->service($this->seededRulesJson());
        self::assertSame(
            PsychStatus::STEADY,
            $service->assign(MoodQuadrant::NEUTRAL, 3, UserIntent::MAINTAIN),
        );
    }

    public function testEnergyClampBelowMinStillAppliesRule(): void
    {
        $service = $this->service($this->seededRulesJson());
        // Energy is clamped to 1 but DRAINED rule has no energy constraint.
        self::assertSame(
            PsychStatus::WEARY,
            $service->assign(MoodQuadrant::DRAINED, -10, UserIntent::PUSH),
        );
    }

    public function testEnergyClampAboveMaxStillAppliesRule(): void
    {
        $service = $this->service($this->seededRulesJson());
        // Energy is clamped to 5; ENERGIZED+PUSH rule matches.
        self::assertSame(
            PsychStatus::CHARGED,
            $service->assign(MoodQuadrant::ENERGIZED, 99, UserIntent::PUSH),
        );
    }

    public function testMissingSettingFallsBackToHardcodedRules(): void
    {
        $service = $this->service(null);
        self::assertSame(
            PsychStatus::SCATTERED,
            $service->assign(MoodQuadrant::ON_EDGE, 3, UserIntent::MAINTAIN),
        );
        self::assertSame(
            PsychStatus::STEADY,
            $service->assign(MoodQuadrant::AT_EASE, 3, UserIntent::MAINTAIN),
        );
    }

    public function testMalformedSettingFallsBackToHardcodedRules(): void
    {
        $service = $this->service('{"not":"an-array-of-rules"}');
        self::assertSame(
            PsychStatus::WEARY,
            $service->assign(MoodQuadrant::DRAINED, 3, UserIntent::MAINTAIN),
        );
    }

    public function testRuleOrderIsFirstMatchWins(): void
    {
        // Two rules that both match ENERGIZED; the first one should win.
        $json = (string) json_encode([
            ['when' => ['mood' => ['ENERGIZED']], 'assign' => 'CHARGED'],
            ['when' => ['mood' => ['ENERGIZED']], 'assign' => 'STEADY'],
        ]);
        $service = $this->service($json);
        self::assertSame(
            PsychStatus::CHARGED,
            $service->assign(MoodQuadrant::ENERGIZED, 3, UserIntent::MAINTAIN),
        );
    }
}
