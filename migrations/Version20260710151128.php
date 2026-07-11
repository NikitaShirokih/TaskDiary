<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260710151128 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add API tokens table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE api_tokens (id SERIAL NOT NULL, user_id INT NOT NULL, name VARCHAR(100) NOT NULL, token_hash VARCHAR(64) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, last_used_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, revoked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2CAD560EA76ED395 ON api_tokens (user_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_api_token_hash ON api_tokens (token_hash)');
        $this->addSql('COMMENT ON COLUMN api_tokens.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN api_tokens.last_used_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN api_tokens.revoked_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE api_tokens ADD CONSTRAINT FK_2CAD560EA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE api_tokens DROP CONSTRAINT FK_2CAD560EA76ED395');
        $this->addSql('DROP TABLE api_tokens');
    }
}
