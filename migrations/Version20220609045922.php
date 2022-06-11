<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220609045922 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE autocomplete_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE compile_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE autocomplete (id INT NOT NULL, run_id BIGINT DEFAULT NULL, uuid UUID NOT NULL, status VARCHAR(255) DEFAULT \'pending\' NOT NULL, config TEXT NOT NULL, nonce VARCHAR(255) NOT NULL, site VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, package TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1C6A7C6484E3FEC4 ON autocomplete (run_id)');
        $this->addSql('COMMENT ON COLUMN autocomplete.uuid IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE compile (id INT NOT NULL, run_id BIGINT DEFAULT NULL, uuid UUID NOT NULL, status VARCHAR(255) DEFAULT \'pending\' NOT NULL, package TEXT DEFAULT NULL, config TEXT NOT NULL, version VARCHAR(255) NOT NULL, css TEXT DEFAULT NULL, nonce VARCHAR(255) NOT NULL, site VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, content TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_679B663A84E3FEC4 ON compile (run_id)');
        $this->addSql('COMMENT ON COLUMN compile.uuid IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE run (id BIGINT NOT NULL, job JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, status VARCHAR(255) DEFAULT \'completed\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
            BEGIN
                PERFORM pg_notify(\'messenger_messages\', NEW.queue_name::text);
                RETURN NEW;
            END;
        $$ LANGUAGE plpgsql;');
        $this->addSql('DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;');
        $this->addSql('CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();');
        $this->addSql('ALTER TABLE autocomplete ADD CONSTRAINT FK_1C6A7C6484E3FEC4 FOREIGN KEY (run_id) REFERENCES run (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE compile ADD CONSTRAINT FK_679B663A84E3FEC4 FOREIGN KEY (run_id) REFERENCES run (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE autocomplete DROP CONSTRAINT FK_1C6A7C6484E3FEC4');
        $this->addSql('ALTER TABLE compile DROP CONSTRAINT FK_679B663A84E3FEC4');
        $this->addSql('DROP SEQUENCE autocomplete_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE compile_id_seq CASCADE');
        $this->addSql('DROP TABLE autocomplete');
        $this->addSql('DROP TABLE compile');
        $this->addSql('DROP TABLE run');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
