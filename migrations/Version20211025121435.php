<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211025121435 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE seat_booked DROP FOREIGN KEY FK_A6DA6E03C1DAFE35');
        $this->addSql('DROP INDEX IDX_A6DA6E03C1DAFE35 ON seat_booked');
        $this->addSql('ALTER TABLE seat_booked DROP seat_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE seat_booked ADD seat_id INT NOT NULL');
        $this->addSql('ALTER TABLE seat_booked ADD CONSTRAINT FK_A6DA6E03C1DAFE35 FOREIGN KEY (seat_id) REFERENCES seat (id)');
        $this->addSql('CREATE INDEX IDX_A6DA6E03C1DAFE35 ON seat_booked (seat_id)');
    }
}
