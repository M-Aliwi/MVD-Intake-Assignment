<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251024133256 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create offers table for property bidding system';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE offers (
            id INT AUTO_INCREMENT NOT NULL,
            external_id VARCHAR(255) DEFAULT NULL,
            property_id VARCHAR(255) NOT NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(50) NOT NULL,
            amount DECIMAL(12,2) NOT NULL,
            conditions LONGTEXT DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'pending\',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            meta JSON DEFAULT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        $this->addSql('CREATE INDEX IDX_offers_external_id ON offers (external_id)');
        $this->addSql('CREATE INDEX IDX_offers_status ON offers (status)');
        $this->addSql('CREATE INDEX IDX_offers_created_at ON offers (created_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE offers');
    }
}
