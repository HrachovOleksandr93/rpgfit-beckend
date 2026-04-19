<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add `roles` JSON column to `users` for Phase 5 of the Test Harness.
 *
 * MySQL disallows DB-level defaults on JSON columns, so the column is
 * created NOT NULL and a single UPDATE backfills every existing row
 * with `["ROLE_USER"]` before the migration returns.
 *
 * If the `APP_FOUNDER_EMAIL` env var is set, the founder account is
 * promoted to ROLE_SUPERADMIN after the backfill. Otherwise a warning
 * is logged via the migration output — this is intentional, because
 * the founder email is environment-specific and should not leak into
 * version control.
 */
final class Version20260418200400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add roles JSON column to users (Test Harness Phase 5)';
    }

    public function up(Schema $schema): void
    {
        // MySQL strict mode rejects adding a JSON NOT NULL column to a
        // populated table without a default (and JSON columns cannot carry
        // a default at all). The safe sequence is:
        //   1. add the column NULL
        //   2. backfill every row with ["ROLE_USER"]
        //   3. tighten to NOT NULL
        $this->addSql('ALTER TABLE users ADD roles JSON DEFAULT NULL');
        $this->addSql("UPDATE users SET roles = '[\"ROLE_USER\"]'");
        $this->addSql('ALTER TABLE users MODIFY roles JSON NOT NULL');

        $founderEmail = $_ENV['APP_FOUNDER_EMAIL'] ?? $_SERVER['APP_FOUNDER_EMAIL'] ?? null;
        if (is_string($founderEmail) && $founderEmail !== '') {
            // Parameter-bound UPDATE so the email is safely quoted by DBAL.
            $this->addSql(
                "UPDATE users SET roles = '[\"ROLE_SUPERADMIN\"]' WHERE login = :email",
                ['email' => $founderEmail],
            );
            $this->write(sprintf('[info] Founder %s promoted to ROLE_SUPERADMIN.', $founderEmail));
        } else {
            $this->warnIf(
                true,
                'APP_FOUNDER_EMAIL env var not set — no founder promoted. '
                . 'Run `php bin/console app:grant-role <email> ROLE_SUPERADMIN` manually.',
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP roles');
    }
}
