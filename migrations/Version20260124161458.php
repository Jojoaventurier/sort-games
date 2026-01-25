<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260124161458 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE game_entry (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, tag VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, version VARCHAR(255) DEFAULT NULL, normalized_name VARCHAR(255) DEFAULT NULL, game_list_id INT NOT NULL, INDEX IDX_1912E4FFA86C69A4 (game_list_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE game_list (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, source VARCHAR(255) DEFAULT NULL, uploaded_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE game_entry ADD CONSTRAINT FK_1912E4FFA86C69A4 FOREIGN KEY (game_list_id) REFERENCES game_list (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game_entry DROP FOREIGN KEY FK_1912E4FFA86C69A4');
        $this->addSql('DROP TABLE game_entry');
        $this->addSql('DROP TABLE game_list');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
