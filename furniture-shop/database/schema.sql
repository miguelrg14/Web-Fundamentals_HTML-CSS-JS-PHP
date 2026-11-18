-- Furniture shop schema (MySQL 8 / MariaDB 10.6+). Recreates every table, so it also resets the data.
SET NAMES utf8mb4;

DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email         VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,   -- password_hash() output, never the password itself
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_users_email UNIQUE (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    price_cents INT UNSIGNED NOT NULL,     -- money as whole cents: no floating-point rounding
    image       VARCHAR(255) NOT NULL,
    CONSTRAINT ck_products_price CHECK (price_cents > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orders (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    total_cents INT UNSIGNED NOT NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The unit price is copied into the order, so later price changes don't rewrite past orders.
CREATE TABLE order_items (
    order_id         INT UNSIGNED     NOT NULL,
    product_id       INT UNSIGNED     NOT NULL,
    quantity         TINYINT UNSIGNED NOT NULL,
    unit_price_cents INT UNSIGNED     NOT NULL,
    PRIMARY KEY (order_id, product_id),
    CONSTRAINT fk_items_order    FOREIGN KEY (order_id)   REFERENCES orders (id) ON DELETE CASCADE,
    CONSTRAINT fk_items_product  FOREIGN KEY (product_id) REFERENCES products (id),
    CONSTRAINT ck_items_quantity CHECK (quantity BETWEEN 1 AND 99)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO products (name, price_cents, image) VALUES
    ('White chaise longue', 34900, 'images/products/couch.jpg'),
    ('Glass desk',          18950, 'images/products/desk.jpg');
