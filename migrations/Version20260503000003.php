<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260503000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user_id to orders table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders ADD COLUMN user_id INTEGER NOT NULL');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT fk_order_user FOREIGN KEY (user_id) REFERENCES "user"(id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders DROP CONSTRAINT fk_order_user');
        $this->addSql('ALTER TABLE orders DROP COLUMN user_id');
    }
}
