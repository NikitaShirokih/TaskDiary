<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260710134500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add password reset token fields to users.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD password_reset_token_hash VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD password_reset_token_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN "user".password_reset_token_expires_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP password_reset_token_hash');
        $this->addSql('ALTER TABLE "user" DROP password_reset_token_expires_at');
    }
}
