<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250929171609 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE skill_effect DROP FOREIGN KEY FK_992AC5E65585C142');
        $this->addSql('DROP INDEX IDX_992AC5E65585C142 ON skill_effect');
        $this->addSql('ALTER TABLE skill_effect ADD created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP skill_id, DROP value, DROP chance, DROP duration, DROP target_side, DROP cumulative, CHANGE effect_type name VARCHAR(100) NOT NULL, CHANGE scale_on description LONGTEXT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE skill_effect ADD skill_id INT NOT NULL, ADD value DOUBLE PRECISION NOT NULL, ADD chance INT NOT NULL, ADD duration INT NOT NULL, ADD target_side VARCHAR(50) NOT NULL, ADD cumulative TINYINT(1) NOT NULL, DROP created_at, CHANGE name effect_type VARCHAR(100) NOT NULL, CHANGE description scale_on LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE skill_effect ADD CONSTRAINT FK_992AC5E65585C142 FOREIGN KEY (skill_id) REFERENCES hero_skill (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_992AC5E65585C142 ON skill_effect (skill_id)');
    }
}
