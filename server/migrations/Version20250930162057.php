<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250930162057 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE hero_skill ADD description LONGTEXT DEFAULT NULL, ADD multiplicator DOUBLE PRECISION DEFAULT NULL, ADD scaling VARCHAR(50) DEFAULT NULL, ADD hits_number INT DEFAULT NULL, ADD cooldown INT DEFAULT NULL, ADD initial_cooldown INT DEFAULT NULL, ADD is_passive TINYINT(1) DEFAULT NULL, ADD targeting VARCHAR(50) DEFAULT NULL, ADD targeting_team VARCHAR(50) DEFAULT NULL, ADD does_damage TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE hero_skill DROP description, DROP multiplicator, DROP scaling, DROP hits_number, DROP cooldown, DROP initial_cooldown, DROP is_passive, DROP targeting, DROP targeting_team, DROP does_damage');
    }
}
