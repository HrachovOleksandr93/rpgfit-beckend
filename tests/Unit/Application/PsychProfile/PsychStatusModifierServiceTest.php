<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\PsychProfile;

use App\Application\PsychProfile\Service\PsychStatusModifierService;
use App\Domain\Config\Entity\GameSetting;
use App\Domain\PsychProfile\Entity\PsychUserProfile;
use App\Domain\PsychProfile\Enum\PsychStatus;
use App\Domain\User\Entity\User;
use App\Infrastructure\Config\Repository\GameSettingRepository;
use App\Infrastructure\PsychProfile\Repository\PsychUserProfileRepository;
use PHPUnit\Framework\TestCase;

/**
 * One test per status × activity slice. Confirms:
 *   - CHARGED ×1.15 ONLY for CONTEXT_NEW_CHALLENGE.
 *   - DORMANT ×1.20 ONLY for CONTEXT_REST.
 *   - STEADY / WEARY / SCATTERED → 1.0 everywhere (no penalty).
 *   - Opted-out profile → 1.0 baseline.
 */
final class PsychStatusModifierServiceTest extends TestCase
{
    private function makeUser(): User
    {
        $user = new User();
        $user->setLogin('hero@example.com');
        $user->setPassword('hash');

        return $user;
    }

    private function makeProfile(PsychStatus $status, bool $optedIn = true): PsychUserProfile
    {
        $profile = new PsychUserProfile();
        $profile->setUser($this->makeUser());
        $profile->setCurrentStatus($status);
        $profile->setFeatureOptedIn($optedIn);

        return $profile;
    }

    private function service(?PsychUserProfile $profile): PsychStatusModifierService
    {
        $settings = $this->createMock(GameSettingRepository::class);
        $setting = (new GameSetting())
            ->setCategory('psych')
            ->setKey(PsychStatusModifierService::SETTING_KEY)
            ->setValue((string) json_encode([
                'CHARGED' => 1.15,
                'STEADY' => 1.0,
                'DORMANT' => 1.20,
                'WEARY' => 1.0,
                'SCATTERED' => 1.0,
            ]));
        $settings->method('findByKey')->willReturn($setting);

        $profiles = $this->createMock(PsychUserProfileRepository::class);
        $profiles->method('findByUser')->willReturn($profile);

        return new PsychStatusModifierService($settings, $profiles);
    }

    public function testChargedGetsBuffOnlyForNewChallenge(): void
    {
        $user = $this->makeUser();
        $service = $this->service($this->makeProfile(PsychStatus::CHARGED));

        self::assertSame(1.15, $service->getXpMultiplier($user, PsychStatusModifierService::CONTEXT_NEW_CHALLENGE));
        self::assertSame(1.0, $service->getXpMultiplier($user, PsychStatusModifierService::CONTEXT_WORKOUT));
        self::assertSame(1.0, $service->getXpMultiplier($user, PsychStatusModifierService::CONTEXT_BATTLE));
    }

    public function testDormantGetsBuffOnlyForRest(): void
    {
        $user = $this->makeUser();
        $service = $this->service($this->makeProfile(PsychStatus::DORMANT));

        self::assertSame(1.20, $service->getXpMultiplier($user, PsychStatusModifierService::CONTEXT_REST));
        self::assertSame(1.0, $service->getXpMultiplier($user, PsychStatusModifierService::CONTEXT_WORKOUT));
    }

    public function testSteadyAlwaysBaseline(): void
    {
        $user = $this->makeUser();
        $service = $this->service($this->makeProfile(PsychStatus::STEADY));

        self::assertSame(1.0, $service->getXpMultiplier($user, PsychStatusModifierService::CONTEXT_BATTLE));
        self::assertSame(1.0, $service->getXpMultiplier($user, PsychStatusModifierService::CONTEXT_REST));
    }

    public function testWearyNeverPenalises(): void
    {
        $user = $this->makeUser();
        $service = $this->service($this->makeProfile(PsychStatus::WEARY));

        self::assertSame(1.0, $service->getXpMultiplier($user, PsychStatusModifierService::CONTEXT_BATTLE));
        self::assertSame(1.0, $service->getXpMultiplier($user, PsychStatusModifierService::CONTEXT_WORKOUT));
    }

    public function testScatteredNeverPenalises(): void
    {
        $user = $this->makeUser();
        $service = $this->service($this->makeProfile(PsychStatus::SCATTERED));

        self::assertSame(1.0, $service->getXpMultiplier($user, PsychStatusModifierService::CONTEXT_BATTLE));
        self::assertSame(1.0, $service->getXpMultiplier($user, PsychStatusModifierService::CONTEXT_REST));
    }

    public function testOptedOutUserAlwaysBaseline(): void
    {
        $user = $this->makeUser();
        $service = $this->service($this->makeProfile(PsychStatus::CHARGED, optedIn: false));

        self::assertSame(1.0, $service->getXpMultiplier($user, PsychStatusModifierService::CONTEXT_NEW_CHALLENGE));
    }

    public function testMissingProfileAlwaysBaseline(): void
    {
        $user = $this->makeUser();
        $service = $this->service(null);

        self::assertSame(1.0, $service->getXpMultiplier($user, PsychStatusModifierService::CONTEXT_NEW_CHALLENGE));
    }
}
