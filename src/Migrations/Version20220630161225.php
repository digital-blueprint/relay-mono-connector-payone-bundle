<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayoneBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220630161225 extends EntityManagerMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE mono_connector_payone_payments (identifier INT AUTO_INCREMENT NOT NULL, created_at DATETIME DEFAULT NULL, payment_identifier VARCHAR(255) NOT NULL, psp_identifier VARCHAR(255) NOT NULL, PRIMARY KEY(identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE mono_connector_payone_payments');
    }
}
