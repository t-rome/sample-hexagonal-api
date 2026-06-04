<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260501000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create product and user tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE "user" (
                id       SERIAL PRIMARY KEY,
                email    VARCHAR(180) NOT NULL,
                password VARCHAR(255) NOT NULL,
                roles    JSON         NOT NULL,
                CONSTRAINT uniq_user_email UNIQUE (email)
            )
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE product (
                id          SERIAL PRIMARY KEY,
                name        VARCHAR(255)     NOT NULL,
                description TEXT             DEFAULT NULL,
                price       DOUBLE PRECISION NOT NULL,
                created_at  TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
            )
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE "user"');
    }
}
