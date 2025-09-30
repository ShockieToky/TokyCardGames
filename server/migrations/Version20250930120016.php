<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250930120016 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE link_skill_effect (id INT AUTO_INCREMENT NOT NULL, skill_id INT NOT NULL, effect_id INT NOT NULL, duration INT DEFAULT 1 NOT NULL, accuracy INT DEFAULT 100 NOT NULL, value DOUBLE PRECISION DEFAULT \'0\' NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_6B8545245585C142 (skill_id), INDEX IDX_6B854524F5E9B83B (effect_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE link_skill_effect ADD CONSTRAINT FK_6B8545245585C142 FOREIGN KEY (skill_id) REFERENCES hero_skill (id)');
        $this->addSql('ALTER TABLE link_skill_effect ADD CONSTRAINT FK_6B854524F5E9B83B FOREIGN KEY (effect_id) REFERENCES skill_effect (id)');
        $this->addSql('ALTER TABLE hero_skill DROP description, DROP multiplicator, DROP scaling, DROP hits_number, DROP cooldown, DROP initial_cooldown, DROP is_passive, DROP targeting, DROP targeting_team, DROP does_damage, CHANGE name name VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE link_skill_effect DROP FOREIGN KEY FK_6B8545245585C142');
        $this->addSql('ALTER TABLE link_skill_effect DROP FOREIGN KEY FK_6B854524F5E9B83B');
        $this->addSql('DROP TABLE link_skill_effect');
        $this->addSql('ALTER TABLE hero_skill ADD description VARCHAR(255) NOT NULL, ADD multiplicator DOUBLE PRECISION NOT NULL, ADD scaling LONGTEXT NOT NULL, ADD hits_number INT NOT NULL, ADD cooldown INT NOT NULL, ADD initial_cooldown INT NOT NULL, ADD is_passive TINYINT(1) NOT NULL, ADD targeting LONGTEXT NOT NULL, ADD targeting_team LONGTEXT NOT NULL, ADD does_damage TINYINT(1) NOT NULL, CHANGE name name VARCHAR(100) NOT NULL');
    }
}
