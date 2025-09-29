<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250927211410 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE weather_history (id UUID NOT NULL, city VARCHAR(100) NOT NULL, country_code VARCHAR(2) NOT NULL, temperature DOUBLE PRECISION NOT NULL, recorded_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_weather_history_city ON weather_history (city, country_code, recorded_at)');
        $this->addSql('COMMENT ON COLUMN weather_history.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN weather_history.recorded_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP TABLE weather_history');
    }
}
