<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211005095058 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE seat_booked ADD ticket_id INT NOT NULL');
        $this->addSql('ALTER TABLE seat_booked ADD CONSTRAINT FK_A6DA6E03700047D2 FOREIGN KEY (ticket_id) REFERENCES ticket (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A6DA6E03700047D2 ON seat_booked (ticket_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE seat_booked DROP FOREIGN KEY FK_A6DA6E03700047D2');
        $this->addSql('DROP INDEX UNIQ_A6DA6E03700047D2 ON seat_booked');
        $this->addSql('ALTER TABLE seat_booked DROP ticket_id');
    }
}
