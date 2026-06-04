<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260503000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add uuid column to orders table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE orders ADD COLUMN uuid UUID NOT NULL DEFAULT gen_random_uuid()
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE orders ALTER COLUMN uuid DROP DEFAULT
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uniq_orders_uuid ON orders (uuid)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_orders_uuid');
        $this->addSql('ALTER TABLE orders DROP COLUMN uuid');
    }
}
