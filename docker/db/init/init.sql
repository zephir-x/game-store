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
    specification TEXT,
    developer VARCHAR(255) DEFAULT 'Unknown Studio',
    release_date DATE DEFAULT CURRENT_DATE
);

CREATE TABLE reviews (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    game_id INTEGER REFERENCES games(id) ON DELETE CASCADE,
    rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
    content TEXT NOT NULL,
    is_edited BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, game_id) -- the user can only rate the game once
);

CREATE TABLE review_likes (
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    review_id INT NOT NULL REFERENCES reviews(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, review_id) -- blocks the ability to like multiple times
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

INSERT INTO games (title, description, category, price, graphics, developer, release_date, specification) VALUES 
('CyberStrike 2077', 'Futuristic RPG game.', 'RPG', 199.99, 'cyber.jpg', 'Nexus Studios', '2077-12-12', '{"minimum":{"os":"Win 10","cpu":"i7-12700K","gpu":"RTX 3060","ram":"16GB","storage":"100GB SSD"},"recommended":{"os":"Win 11","cpu":"i9-13900K","gpu":"RTX 4080","ram":"32GB","storage":"100GB NVMe"}}'),
('Witch Hunter 3', 'Epic fantasy game with an open world.', 'RPG', 99.99, 'witch.jpg', 'Red Project', '2015-05-19', '{"minimum":{"os":"Win 10","cpu":"i5-12400F","gpu":"GTX 1060","ram":"8GB","storage":"50GB HDD"},"recommended":{"os":"Win 11","cpu":"i7-12700","gpu":"not specified","ram":"16GB","storage":"not specified"}}'),
('Space Marines', 'Futuristic FPS filled with action.', 'Shooter', 119.99, 'space.jpg', 'Galaxy Games', '2025-10-01', '{"minimum":{"os":"Win 10","cpu":"i5-12700K","gpu":"RTX 2060","ram":"16GB","storage":"60GB SSD"},"recommended":{"os":"Win 11","cpu":"i7-13700K","gpu":"RTX 3080","ram":"32GB","storage":"60GB SSD"}}'),
('Neon Racers', 'High-speed futuristic racing in neon cities.', 'Racing', 79.99, 'neon.jpg', 'Velocity Labs', '2026-03-15', '{"minimum":{"os":"Win 10","cpu":"i5-11400F","gpu":"GTX 1660","ram":"8GB","storage":"30GB SSD"},"recommended":{"os":"not specified","cpu":"not specified","gpu":"not specified","ram":"not specified","storage":"not specified"}}'),
('Kingdoms Reborn', 'Strategy game about building and managing your empire.', 'Strategy', 89.99, 'kingdom.jpg', 'Empire Builders', '2023-11-20', '{"minimum":{"os":"Win 10","cpu":"i3-10100","gpu":"GTX 1050 Ti","ram":"not specified","storage":"20GB HDD"},"recommended":{"os":"Win 10","cpu":"i5-10400","gpu":"GTX 1660 Ti","ram":"16GB","storage":"20GB SSD"}}'),
('Dark Survival', 'Horror survival game in a post-apocalyptic world.', 'Horror', 69.99, 'survival.jpg', 'Nightmare Studios', '2024-08-05', '{"minimum":{"os":"Win 10","cpu":"i5-10400F","gpu":"GTX 1650","ram":"8GB","storage":"40GB"},"recommended":{"os":"not specified","cpu":"not specified","gpu":"RTX 2070","ram":"16GB","storage":"40GB SSD"}}');