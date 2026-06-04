<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260502000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create order table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE orders (
                id          SERIAL PRIMARY KEY,
                product_id  INTEGER          NOT NULL,
                quantity    INTEGER          NOT NULL,
                total_price DOUBLE PRECISION NOT NULL,
                status      VARCHAR(50)      NOT NULL,
                created_at  TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                CONSTRAINT fk_order_product FOREIGN KEY (product_id) REFERENCES product(id)
            )
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE orders');
    }
}
