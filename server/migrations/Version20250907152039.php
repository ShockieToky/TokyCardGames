<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250907152039 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE code (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, expiration_date DATETIME NOT NULL, scroll_id INT NOT NULL, scroll_count INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_code (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, code_id INT NOT NULL, INDEX IDX_D947C51A76ED395 (user_id), INDEX IDX_D947C5127DAFE17 (code_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_code ADD CONSTRAINT FK_D947C51A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_code ADD CONSTRAINT FK_D947C5127DAFE17 FOREIGN KEY (code_id) REFERENCES code (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_code DROP FOREIGN KEY FK_D947C51A76ED395');
        $this->addSql('ALTER TABLE user_code DROP FOREIGN KEY FK_D947C5127DAFE17');
        $this->addSql('DROP TABLE code');
        $this->addSql('DROP TABLE user_code');
    }
}
