<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211025115413 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE seat_booked ADD room_id INT NOT NULL');
        $this->addSql('ALTER TABLE seat_booked ADD CONSTRAINT FK_A6DA6E0354177093 FOREIGN KEY (room_id) REFERENCES room (id)');
        $this->addSql('CREATE INDEX IDX_A6DA6E0354177093 ON seat_booked (room_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE seat_booked DROP FOREIGN KEY FK_A6DA6E0354177093');
        $this->addSql('DROP INDEX IDX_A6DA6E0354177093 ON seat_booked');
        $this->addSql('ALTER TABLE seat_booked DROP room_id');
    }
}
