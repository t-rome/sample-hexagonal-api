<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260101000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema: user, product, orders, order_items';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE "user" (
                id       SERIAL       PRIMARY KEY,
                email    VARCHAR(180) NOT NULL,
                password VARCHAR(255) NOT NULL,
                roles    JSON         NOT NULL,
                CONSTRAINT uniq_user_email UNIQUE (email)
            )
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE product (
                id          SERIAL           PRIMARY KEY,
                name        VARCHAR(255)     NOT NULL,
                description TEXT             DEFAULT NULL,
                price       DOUBLE PRECISION NOT NULL,
                stock       INTEGER          NOT NULL DEFAULT 0,
                created_at  TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
            )
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE orders (
                id         SERIAL       PRIMARY KEY,
                uuid       UUID         NOT NULL,
                user_id    INTEGER      NOT NULL,
                status     VARCHAR(50)  NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                CONSTRAINT uniq_orders_uuid    UNIQUE (uuid),
                CONSTRAINT fk_order_user       FOREIGN KEY (user_id) REFERENCES "user"(id),
                CONSTRAINT chk_orders_status   CHECK (status IN ('pending', 'confirmed', 'shipped', 'delivered', 'cancelled'))
            )
        SQL);

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
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE order_items');
        $this->addSql('DROP TABLE orders');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE "user"');
    }
}
