<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250825182046 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE hero (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, hp INT NOT NULL, def INT NOT NULL, atk INT NOT NULL, vit INT NOT NULL, res INT NOT NULL, star INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE hero_skill (id INT AUTO_INCREMENT NOT NULL, hero_id INT NOT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(255) NOT NULL, multiplicator DOUBLE PRECISION NOT NULL, scaling LONGTEXT NOT NULL, hits_number INT NOT NULL, cooldown INT NOT NULL, initial_cooldown INT NOT NULL, is_passive TINYINT(1) NOT NULL, targeting LONGTEXT NOT NULL, targeting_team LONGTEXT NOT NULL, does_damage TINYINT(1) NOT NULL, INDEX IDX_C102EBB045B0BCD (hero_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE scroll (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE scroll_rate (id INT AUTO_INCREMENT NOT NULL, scroll_id INT NOT NULL, star INT NOT NULL, rate DOUBLE PRECISION NOT NULL, INDEX IDX_96C232A64724FEBE (scroll_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE skill_effect (id INT AUTO_INCREMENT NOT NULL, skill_id INT NOT NULL, effect_type VARCHAR(100) NOT NULL, value DOUBLE PRECISION NOT NULL, chance INT NOT NULL, duration INT NOT NULL, scale_on LONGTEXT NOT NULL, target_side LONGTEXT NOT NULL, cumulative TINYINT(1) NOT NULL, INDEX IDX_992AC5E65585C142 (skill_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_collection (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, hero_id INT NOT NULL, INDEX IDX_5B2AA3DEA76ED395 (user_id), INDEX IDX_5B2AA3DE45B0BCD (hero_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_scroll (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, scroll_id INT NOT NULL, quantity INT NOT NULL, INDEX IDX_884525BEA76ED395 (user_id), INDEX IDX_884525BE4724FEBE (scroll_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE hero_skill ADD CONSTRAINT FK_C102EBB045B0BCD FOREIGN KEY (hero_id) REFERENCES hero (id)');
        $this->addSql('ALTER TABLE scroll_rate ADD CONSTRAINT FK_96C232A64724FEBE FOREIGN KEY (scroll_id) REFERENCES scroll (id)');
        $this->addSql('ALTER TABLE skill_effect ADD CONSTRAINT FK_992AC5E65585C142 FOREIGN KEY (skill_id) REFERENCES hero_skill (id)');
        $this->addSql('ALTER TABLE user_collection ADD CONSTRAINT FK_5B2AA3DEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_collection ADD CONSTRAINT FK_5B2AA3DE45B0BCD FOREIGN KEY (hero_id) REFERENCES hero (id)');
        $this->addSql('ALTER TABLE user_scroll ADD CONSTRAINT FK_884525BEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_scroll ADD CONSTRAINT FK_884525BE4724FEBE FOREIGN KEY (scroll_id) REFERENCES scroll (id)');
        $this->addSql('ALTER TABLE user CHANGE is_admin is_admin TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE hero_skill DROP FOREIGN KEY FK_C102EBB045B0BCD');
        $this->addSql('ALTER TABLE scroll_rate DROP FOREIGN KEY FK_96C232A64724FEBE');
        $this->addSql('ALTER TABLE skill_effect DROP FOREIGN KEY FK_992AC5E65585C142');
        $this->addSql('ALTER TABLE user_collection DROP FOREIGN KEY FK_5B2AA3DEA76ED395');
        $this->addSql('ALTER TABLE user_collection DROP FOREIGN KEY FK_5B2AA3DE45B0BCD');
        $this->addSql('ALTER TABLE user_scroll DROP FOREIGN KEY FK_884525BEA76ED395');
        $this->addSql('ALTER TABLE user_scroll DROP FOREIGN KEY FK_884525BE4724FEBE');
        $this->addSql('DROP TABLE hero');
        $this->addSql('DROP TABLE hero_skill');
        $this->addSql('DROP TABLE scroll');
        $this->addSql('DROP TABLE scroll_rate');
        $this->addSql('DROP TABLE skill_effect');
        $this->addSql('DROP TABLE user_collection');
        $this->addSql('DROP TABLE user_scroll');
        $this->addSql('ALTER TABLE user CHANGE is_admin is_admin TINYINT(1) DEFAULT 0 NOT NULL');
    }
}
