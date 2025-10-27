<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251027041200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Split name field into firstname and lastname fields';
    }

    public function up(Schema $schema): void
    {
        // Add new columns
        $this->addSql('ALTER TABLE offers ADD firstname VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE offers ADD lastname VARCHAR(100) NOT NULL');
        
        // Migrate existing data - split name into firstname and lastname
        $this->addSql("UPDATE offers SET firstname = SUBSTRING_INDEX(name, ' ', 1), lastname = CASE WHEN LOCATE(' ', name) > 0 THEN SUBSTRING(name, LOCATE(' ', name) + 1) ELSE 'User' END");
        
        // Drop the old name column
        $this->addSql('ALTER TABLE offers DROP COLUMN name');
    }

    public function down(Schema $schema): void
    {
        // Add back the name column
        $this->addSql('ALTER TABLE offers ADD name VARCHAR(255) NOT NULL');
        
        // Combine firstname and lastname back to name
        $this->addSql("UPDATE offers SET name = CONCAT(firstname, ' ', lastname)");
        
        // Drop the new columns
        $this->addSql('ALTER TABLE offers DROP COLUMN firstname');
        $this->addSql('ALTER TABLE offers DROP COLUMN lastname');
    }
}
