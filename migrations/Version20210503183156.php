<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210503183156 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE event_data (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, gender VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, duration INT DEFAULT NULL, release_date DATE DEFAULT NULL, actors LONGTEXT DEFAULT NULL, poster_photo LONGBLOB DEFAULT NULL, rating DOUBLE PRECISION DEFAULT NULL, status TINYINT(1) NOT NULL, age_rating VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE room (id INT AUTO_INCREMENT NOT NULL, cinema_id INT NOT NULL, n_rows INT NOT NULL, n_columns INT NOT NULL, number INT NOT NULL, INDEX IDX_729F519BB4CB84B6 (cinema_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE seat (id INT AUTO_INCREMENT NOT NULL, room_id INT NOT NULL, ticket_id INT NOT NULL, row INT NOT NULL, number INT NOT NULL, status TINYINT(1) NOT NULL, INDEX IDX_3D5C366654177093 (room_id), UNIQUE INDEX UNIQ_3D5C3666700047D2 (ticket_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE session (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, cinema_id INT NOT NULL, room_id INT NOT NULL, schedule DATETIME NOT NULL, language VARCHAR(255) DEFAULT NULL, INDEX IDX_D044D5D471F7E88B (event_id), INDEX IDX_D044D5D4B4CB84B6 (cinema_id), INDEX IDX_D044D5D454177093 (room_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE room ADD CONSTRAINT FK_729F519BB4CB84B6 FOREIGN KEY (cinema_id) REFERENCES cinema (id)');
        $this->addSql('ALTER TABLE seat ADD CONSTRAINT FK_3D5C366654177093 FOREIGN KEY (room_id) REFERENCES room (id)');
        $this->addSql('ALTER TABLE seat ADD CONSTRAINT FK_3D5C3666700047D2 FOREIGN KEY (ticket_id) REFERENCES ticket (id)');
        $this->addSql('ALTER TABLE session ADD CONSTRAINT FK_D044D5D471F7E88B FOREIGN KEY (event_id) REFERENCES event_data (id)');
        $this->addSql('ALTER TABLE session ADD CONSTRAINT FK_D044D5D4B4CB84B6 FOREIGN KEY (cinema_id) REFERENCES cinema (id)');
        $this->addSql('ALTER TABLE session ADD CONSTRAINT FK_D044D5D454177093 FOREIGN KEY (room_id) REFERENCES room (id)');
        $this->addSql('ALTER TABLE cinema DROP rooms, CHANGE title name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE comment ADD event_id INT NOT NULL');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C71F7E88B FOREIGN KEY (event_id) REFERENCES event_data (id)');
        $this->addSql('CREATE INDEX IDX_9474526C71F7E88B ON comment (event_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C71F7E88B');
        $this->addSql('ALTER TABLE session DROP FOREIGN KEY FK_D044D5D471F7E88B');
        $this->addSql('ALTER TABLE seat DROP FOREIGN KEY FK_3D5C366654177093');
        $this->addSql('ALTER TABLE session DROP FOREIGN KEY FK_D044D5D454177093');
        $this->addSql('DROP TABLE event_data');
        $this->addSql('DROP TABLE room');
        $this->addSql('DROP TABLE seat');
        $this->addSql('DROP TABLE session');
        $this->addSql('ALTER TABLE cinema ADD rooms LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:object)\', CHANGE name title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('DROP INDEX IDX_9474526C71F7E88B ON comment');
        $this->addSql('ALTER TABLE comment DROP event_id');
    }
}
