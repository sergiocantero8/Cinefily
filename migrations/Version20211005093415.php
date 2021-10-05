<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211005093415 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE seat_booked (id INT AUTO_INCREMENT NOT NULL, session_id INT NOT NULL, seat_id INT NOT NULL, INDEX IDX_A6DA6E03613FECDF (session_id), INDEX IDX_A6DA6E03C1DAFE35 (seat_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE seat_booked ADD CONSTRAINT FK_A6DA6E03613FECDF FOREIGN KEY (session_id) REFERENCES session (id)');
        $this->addSql('ALTER TABLE seat_booked ADD CONSTRAINT FK_A6DA6E03C1DAFE35 FOREIGN KEY (seat_id) REFERENCES seat (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE seat_booked');
    }
}
