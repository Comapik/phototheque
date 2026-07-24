<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260724122902 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE acces_client (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, client_id INT NOT NULL, evenement_id INT NOT NULL, INDEX IDX_ACF4C77219EB6921 (client_id), INDEX IDX_ACF4C772FD02F13 (evenement_id), UNIQUE INDEX UNIQ_ACCES_CLIENT_EVENEMENT (client_id, evenement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE evenement (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, date DATE NOT NULL, description LONGTEXT DEFAULT NULL, slug VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_EVENEMENT_SLUG (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE photo (id INT AUTO_INCREMENT NOT NULL, nom_original VARCHAR(255) NOT NULL, chemin_basse_def VARCHAR(255) NOT NULL, chemin_miniature VARCHAR(255) NOT NULL, largeur INT NOT NULL, hauteur INT NOT NULL, taille INT NOT NULL, ordre INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, evenement_id INT NOT NULL, INDEX IDX_14B78418FD02F13 (evenement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE acces_client ADD CONSTRAINT FK_ACF4C77219EB6921 FOREIGN KEY (client_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE acces_client ADD CONSTRAINT FK_ACF4C772FD02F13 FOREIGN KEY (evenement_id) REFERENCES evenement (id)');
        $this->addSql('ALTER TABLE photo ADD CONSTRAINT FK_14B78418FD02F13 FOREIGN KEY (evenement_id) REFERENCES evenement (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE acces_client DROP FOREIGN KEY FK_ACF4C77219EB6921');
        $this->addSql('ALTER TABLE acces_client DROP FOREIGN KEY FK_ACF4C772FD02F13');
        $this->addSql('ALTER TABLE photo DROP FOREIGN KEY FK_14B78418FD02F13');
        $this->addSql('DROP TABLE acces_client');
        $this->addSql('DROP TABLE evenement');
        $this->addSql('DROP TABLE photo');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
