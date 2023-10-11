<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231001135824 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX userid ON checkinout');
        $this->addSql('ALTER TABLE checkinout ADD machine_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE checkinout ADD CONSTRAINT FK_77F7D200F6B75B26 FOREIGN KEY (machine_id) REFERENCES machines (id)');
        $this->addSql('CREATE INDEX IDX_77F7D200F6B75B26 ON checkinout (machine_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE checkinout DROP FOREIGN KEY FK_77F7D200F6B75B26');
        $this->addSql('DROP INDEX IDX_77F7D200F6B75B26 ON checkinout');
        $this->addSql('ALTER TABLE checkinout DROP machine_id');
        $this->addSql('CREATE INDEX userid ON checkinout (userid, checktime, sn)');
    }
}
