-- Drop database if exists
DROP DATABASE IF EXISTS food_delivery;

-- Create database
CREATE DATABASE food_delivery;
USE food_delivery;

-- Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    is_admin BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    is_available BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Create orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'out_for_delivery', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    address TEXT NOT NULL,
    phone VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Create order_items table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    options TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- Create payments table
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('credit_card', 'debit_card', 'cash_on_delivery', 'paypal') DEFAULT 'cash_on_delivery',
    transaction_id VARCHAR(255),
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Create cart table
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY (user_id, product_id)
);

-- Insert admin user
INSERT INTO users (name, email, password, is_admin) VALUES 
('Admin', 'admin@cincin.com', '$2y$10$JmZzrD7AZGZnvp8Z8K2Qv.YtQJVgUUYOVyZWrHGtjnbzZdJTMZpZy', 1);

-- Insert sample categories
INSERT INTO categories (name, description, image) VALUES
('Pizza', 'Delicious Italian pizzas with various toppings', 'pizza-category.jpg'),
('Burger', 'Juicy burgers with fresh ingredients', 'burger-category.jpg'),
('Pasta', 'Authentic Italian pasta dishes', 'pasta-category.jpg'),
('Salad', 'Fresh and healthy salads', 'salad-category.jpg'),
('Dessert', 'Sweet treats to satisfy your cravings', 'dessert-category.jpg'),
('Beverage', 'Refreshing drinks and beverages', 'beverage-category.jpg');

-- Insert sample products
INSERT INTO products (category_id, name, description, price, image) VALUES
(1, 'Margherita Pizza', 'Classic pizza with tomato sauce, mozzarella, and basil', 9.99, 'margherita-pizza.jpg'),
(1, 'Pepperoni Pizza', 'Pizza topped with pepperoni slices and cheese', 11.99, 'pepperoni-pizza.jpg'),
(1, 'Vegetarian Pizza', 'Pizza loaded with fresh vegetables', 10.99, 'vegetarian-pizza.jpg'),
(2, 'Classic Burger', 'Beef patty with lettuce, tomato, and special sauce', 8.99, 'classic-burger.jpg'),
(2, 'Cheese Burger', 'Beef patty with melted cheese, lettuce, and tomato', 9.99, 'cheese-burger.jpg'),
(2, 'Veggie Burger', 'Plant-based patty with fresh vegetables', 7.99, 'veggie-burger.jpg'),
(3, 'Spaghetti Bolognese', 'Spaghetti with rich meat sauce', 12.99, 'spaghetti-bolognese.jpg'),
(3, 'Fettuccine Alfredo', 'Fettuccine pasta with creamy Alfredo sauce', 13.99, 'fettuccine-alfredo.jpg'),
(4, 'Caesar Salad', 'Fresh romaine lettuce with Caesar dressing and croutons', 6.99, 'caesar-salad.jpg'),
(4, 'Greek Salad', 'Traditional Greek salad with feta cheese and olives', 7.99, 'greek-salad.jpg'),
(5, 'Chocolate Cake', 'Rich and moist chocolate cake', 5.99, 'chocolate-cake.jpg'),
(5, 'Cheesecake', 'Creamy New York style cheesecake', 6.99, 'cheesecake.jpg'),
(6, 'Coca-Cola', 'Classic Coca-Cola beverage', 1.99, 'coca-cola.jpg'),
(6, 'Orange Juice', 'Freshly squeezed orange juice', 2.99, 'orange-juice.jpg');

-- Insert sample user
INSERT INTO users (name, email, password, address, phone) VALUES
('John Doe', 'john@example.com', '$2y$10$JmZzrD7AZGZnvp8Z8K2Qv.YtQJVgUUYOVyZWrHGtjnbzZdJTMZpZy', '123 Main St, City', '555-123-4567');

-- Insert sample order
INSERT INTO orders (user_id, total_amount, status, payment_status, address, phone) VALUES
(2, 32.97, 'delivered', 'completed', '123 Main St, City', '555-123-4567');

-- Insert sample order items
INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(1, 1, 2, 9.99),
(1, 4, 1, 8.99),
(1, 14, 2, 2.99);

-- Insert sample payment
INSERT INTO payments (order_id, amount, payment_method, transaction_id, status) VALUES
(1, 32.97, 'credit_card', 'TXN123456789', 'completed'); 