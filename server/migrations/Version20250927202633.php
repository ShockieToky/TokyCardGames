<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250927202633 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE arena_defense (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, name VARCHAR(255) NOT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7B1A0A0DA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE arena_defense_heroes (arena_defense_id INT NOT NULL, user_collection_id INT NOT NULL, INDEX IDX_F89D825C691447E8 (arena_defense_id), INDEX IDX_F89D825CBFC7FBAD (user_collection_id), PRIMARY KEY(arena_defense_id, user_collection_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE arena_defense ADD CONSTRAINT FK_7B1A0A0DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE arena_defense_heroes ADD CONSTRAINT FK_F89D825C691447E8 FOREIGN KEY (arena_defense_id) REFERENCES arena_defense (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE arena_defense_heroes ADD CONSTRAINT FK_F89D825CBFC7FBAD FOREIGN KEY (user_collection_id) REFERENCES user_collection (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE arena_defense DROP FOREIGN KEY FK_7B1A0A0DA76ED395');
        $this->addSql('ALTER TABLE arena_defense_heroes DROP FOREIGN KEY FK_F89D825C691447E8');
        $this->addSql('ALTER TABLE arena_defense_heroes DROP FOREIGN KEY FK_F89D825CBFC7FBAD');
        $this->addSql('DROP TABLE arena_defense');
        $this->addSql('DROP TABLE arena_defense_heroes');
    }
}
