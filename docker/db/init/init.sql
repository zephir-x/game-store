-- Cleaning the database
DROP VIEW IF EXISTS v_game_statistics;
DROP VIEW IF EXISTS v_user_library_details;
DROP TRIGGER IF EXISTS after_user_delete ON users;
DROP FUNCTION IF EXISTS log_user_deletion;
DROP TABLE IF EXISTS audit_log CASCADE;
DROP TABLE IF EXISTS user_library CASCADE;
DROP TABLE IF EXISTS reviews CASCADE;
DROP TABLE IF EXISTS games CASCADE;
DROP TABLE IF EXISTS user_details CASCADE;
DROP TABLE IF EXISTS users CASCADE;

-- 1. BASE TABLES

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    role VARCHAR(20) DEFAULT 'USER' CHECK (role IN ('USER', 'ADMIN')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_details (
    user_id INTEGER PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(50),
    surname VARCHAR(50),
    bio TEXT,
    avatar VARCHAR(255) DEFAULT 'gaming-console.jpg'
);

CREATE TABLE games (
    id SERIAL PRIMARY KEY,
    title VARCHAR(100) UNIQUE NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(50) NOT NULL,
    price NUMERIC(10, 2) NOT NULL CHECK (price >= 0),
    graphics VARCHAR(255) DEFAULT 'default.jpg',
    specification TEXT
);

CREATE TABLE reviews (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    game_id INTEGER REFERENCES games(id) ON DELETE CASCADE,
    rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, game_id) -- the user can only rate the game once
);

CREATE TABLE user_library (
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    game_id INTEGER REFERENCES games(id) ON DELETE CASCADE,
    purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, game_id)
);

CREATE TABLE wishlist (
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    game_id INTEGER REFERENCES games(id) ON DELETE CASCADE,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, game_id)
);

-- Table for saving logs by trigger
CREATE TABLE audit_log (
    id SERIAL PRIMARY KEY,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. VIEWS

-- View 1: Combines users, their library, and games
CREATE VIEW v_user_library_details AS
SELECT 
    ul.user_id,
    g.id AS game_id,
    g.title,
    g.category,
    g.graphics,
    ul.purchased_at
FROM user_library ul
JOIN games g ON ul.game_id = g.id;

-- View 2: Dynamically calculates game statistics from reviews
CREATE VIEW v_game_statistics AS
SELECT 
    g.id AS game_id,
    g.title,
    g.price,
    COUNT(r.id) AS total_reviews,
    COALESCE(ROUND(AVG(r.rating), 2), 0.00) AS calculated_rating
FROM games g
LEFT JOIN reviews r ON g.id = r.game_id
GROUP BY g.id, g.title, g.price;

-- 3. FUNCTIONS AND TRIGGERS

-- A function that logs the fact that a user has been deleted
CREATE OR REPLACE FUNCTION log_user_deletion()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO audit_log (action, details)
    VALUES ('USER_DELETED', 'User ' || OLD.username || ' (ID: ' || OLD.id || ') was removed from the system.');
    RETURN OLD;
END;
$$ LANGUAGE plpgsql;

-- Trigger that fires after a user is deleted
CREATE TRIGGER after_user_delete
AFTER DELETE ON users
FOR EACH ROW
EXECUTE FUNCTION log_user_deletion();

-- 4. SEEDING DATA

-- THE PASSWORD IS: admin123 and user123 (hashed in BCRYPT)
INSERT INTO users (email, password_hash, username, role) VALUES 
('admin@gmail.com', '$2a$12$XLDJQF7Zcdeqx4P6UYavpuHB8dW3WNmuq8Phc8XlSKH5zoWTA39d6', 'admin', 'ADMIN'),
('user@gmail.com', '$2a$12$jQAjb9Wm00kTeYHG3t5n.eqQ.gUeBDEZkk3na6rF1YNwVEESrasr.', 'user', 'USER');

INSERT INTO user_details (user_id, name, surname, bio, avatar) VALUES 
(1, 'System', 'Administrator', 'I rule this place ;D', 'gaming-guy.jpg'),
(2, 'Just', 'User', 'I love RPG games.', 'gaming-girl.jpg');

INSERT INTO games (title, description, category, price, graphics, specification) VALUES 
('CyberStrike 2077', 'Futuristic RPG game.', 'RPG', 199.99, 'cyber.jpg', 'OS: Win 10, GPU: RTX 3060, CPU: i7-12700K, RAM: 16GB'),
('Witch Hunter 3', 'Epic fantasy game with an open world.', 'RPG', 99.99, 'witch.jpg', 'OS: Win 10, GPU: GTX 1060, CPU: i5-12400F, RAM: 8GB'),
('Space Marines', 'Futuristic FPS filled with action.', 'Shooter', 119.99, 'space.jpg', 'OS: Win 10, GPU: RTX 2060, CPU: i5-12700K, RAM: 16GB'),
('Neon Racers', 'High-speed futuristic racing in neon cities.', 'Racing', 79.99, 'neon.jpg', 'OS: Win 10, GPU: GTX 1660, CPU: i5-11400F, RAM: 8GB'),
('Kingdoms Reborn', 'Strategy game about building and managing your empire.', 'Strategy', 89.99, 'kingdom.jpg', 'OS: Win 10, GPU: GTX 1050 Ti, CPU: i3-10100, RAM: 8GB'),
('Dark Survival', 'Horror survival game in a post-apocalyptic world.', 'Horror', 69.99, 'survival.jpg', 'OS: Win 10, GPU: GTX 1650, CPU: i5-10400F, RAM: 8GB');