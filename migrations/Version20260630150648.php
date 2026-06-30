<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260630150648 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make task categories user-owned and unique per user.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS uniq_3af346685e237e06');
        $this->addSql('ALTER TABLE categories ADD user_id INT DEFAULT NULL');
        $this->addSql('UPDATE categories SET user_id = (SELECT id FROM "user" ORDER BY id ASC LIMIT 1)');
        $this->addSql('DELETE FROM categories WHERE user_id IS NULL');
        $this->addSql('ALTER TABLE categories ALTER user_id SET NOT NULL');
        $this->addSql('ALTER TABLE categories ADD CONSTRAINT FK_3AF34668A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_3AF34668A76ED395 ON categories (user_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_category_user_name ON categories (user_id, name)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE categories DROP CONSTRAINT FK_3AF34668A76ED395');
        $this->addSql('DROP INDEX IF EXISTS IDX_3AF34668A76ED395');
        $this->addSql('DROP INDEX IF EXISTS uniq_category_user_name');
        $this->addSql('ALTER TABLE categories DROP user_id');
        $this->addSql('CREATE UNIQUE INDEX uniq_3af346685e237e06 ON categories (name)');
    }
}
