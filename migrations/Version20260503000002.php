<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260503000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Introduce order_items table; remove product/quantity/total_price from orders';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE order_items (
                id         SERIAL           PRIMARY KEY,
                order_id   INTEGER          NOT NULL,
                product_id INTEGER          NOT NULL,
                quantity   INTEGER          NOT NULL,
                unit_price DOUBLE PRECISION NOT NULL,
                CONSTRAINT fk_order_item_order   FOREIGN KEY (order_id)   REFERENCES orders(id)  ON DELETE CASCADE,
                CONSTRAINT fk_order_item_product FOREIGN KEY (product_id) REFERENCES product(id)
            )
        SQL);
        $this->addSql('ALTER TABLE orders DROP COLUMN product_id');
        $this->addSql('ALTER TABLE orders DROP COLUMN quantity');
        $this->addSql('ALTER TABLE orders DROP COLUMN total_price');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE order_items');
        $this->addSql('ALTER TABLE orders ADD COLUMN product_id INTEGER NOT NULL');
        $this->addSql('ALTER TABLE orders ADD COLUMN quantity INTEGER NOT NULL');
        $this->addSql('ALTER TABLE orders ADD COLUMN total_price DOUBLE PRECISION NOT NULL');
    }
}
