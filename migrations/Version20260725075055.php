<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260725075055 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE evenement ADD upload_client_autorise TINYINT(1) NOT NULL DEFAULT 0");
        $this->addSql("ALTER TABLE photo ADD origine VARCHAR(20) NOT NULL DEFAULT 'photographe'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE evenement DROP upload_client_autorise');
        $this->addSql('ALTER TABLE photo DROP origine');
    }
}
