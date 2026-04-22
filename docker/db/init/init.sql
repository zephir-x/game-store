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

-- BASE TABLES

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
    release_date DATE DEFAULT CURRENT_DATE,
    is_featured BOOLEAN DEFAULT FALSE
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

-- VIEWS

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

-- View 3: For automatically fetching the 4 newest released (and available) games
CREATE OR REPLACE VIEW v_recommended_games AS
SELECT g.id, g.title, g.description, g.category, g.price, g.graphics, g.specification, g.developer, g.release_date,
       COALESCE(v.calculated_rating, 0.0) AS average_rating
FROM games g
LEFT JOIN v_game_statistics v ON g.id = v.game_id
WHERE g.release_date <= CURRENT_DATE
ORDER BY g.release_date DESC
LIMIT 4;

-- FUNCTIONS AND TRIGGERS

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

-- SEEDING DATA

-- THE PASSWORD IS: admin123, user1, user2 and user3 (hashed in BCRYPT)
INSERT INTO users (email, password_hash, username, role) VALUES 
('admin@gmail.com', '$2a$12$XLDJQF7Zcdeqx4P6UYavpuHB8dW3WNmuq8Phc8XlSKH5zoWTA39d6', 'admin', 'ADMIN'),
('user1@gmail.com', '$2a$12$mYSM3Vj9RCDZLMLPgk1UgOWDroUvTE0gRSWcTkHGpqc8EhesY1VeC', 'user1', 'USER'),
('user2@gmail.com', '$2a$12$Ws9B/MM3u/KANq2v40yVvOCFk0qn7fd1DdWGp.JJhC5JkrR6B6Ua2', 'user2', 'USER'),
('user3@gmail.com', '$2a$12$ILqmvxpsrns2m8oV3yw6l.XTkFpXoo87g2HlyDsZLGfr8qSMmDDDS', 'user3', 'USER');

INSERT INTO user_details (user_id, name, surname, bio, avatar) VALUES 
(1, 'System', 'Administrator', 'I rule this place ;D', 'gaming-guy.jpg'),
(2, 'Just', 'User', 'I love RPG games.', 'gaming-girl.jpg'),
(3, 'Casual', 'Gamer', 'Here for the fun and good times!', 'animal.jpg'),
(4, 'Pixel', 'Fan', 'Retro games are the best!', 'pixel.jpg');

INSERT INTO games (title, description, category, price, graphics, developer, release_date, specification, is_featured) VALUES 
-- [ID 1-4] LATEST RELEASES (Low ID, no reviews)
('Neon Racers', 'High-speed futuristic racing in neon cities.', 'Racing', 79.99, 'neon.jpg', 'Velocity Labs', '2026-03-15', '{"minimum":{"os":"Win 10","cpu":"i5-11400F","gpu":"GTX 1660","ram":"8GB","storage":"30GB SSD"},"recommended":{}}', FALSE),
('Galactic Vanguard', 'Explore the unknown in this space adventure.', 'Adventure', 49.99, 'galactic.jpg', 'Starlight Studios', '2026-04-10', '{"minimum":{"os":"Win 10","cpu":"i5-10400","gpu":"GTX 1060","ram":"8GB","storage":"40GB"},"recommended":{}}', FALSE),
('Shadow Protocol', 'Stealth-action game in a cyberpunk setting.', 'Action', 59.99, 'shadow.jpg', 'Ninja Works', '2026-04-01', '{"minimum":{"os":"Win 10","cpu":"i7","gpu":"RTX 2060","ram":"16GB","storage":"50GB SSD"},"recommended":{}}', FALSE),
('Mystic Forest', 'Relaxing puzzle game with beautiful landscapes.', 'Puzzle', 19.99, 'mystic.jpg', 'Zen Games', '2026-04-15', '{"minimum":{"os":"Win 10","cpu":"i3","gpu":"GTX 1050","ram":"8GB","storage":"10GB"},"recommended":{}}', FALSE),

-- [ID 5-6] UPCOMING RELEASES (Future release)
('CyberStrike 2077', 'Futuristic RPG game.', 'RPG', 199.99, 'cyber.jpg', 'Nexus Studios', '2077-12-12', '{"minimum":{"os":"Win 10","cpu":"i7-12700K","gpu":"RTX 3060","ram":"16GB","storage":"100GB SSD"},"recommended":{"os":"Win 11","cpu":"i9-13900K","gpu":"RTX 4080","ram":"32GB","storage":"100GB NVMe"}}', TRUE),
('Space Marines', 'Futuristic FPS filled with action.', 'Shooter', 0, 'space.jpg', 'Galaxy Games', '2026-10-01', '{"minimum":{"os":"Win 10","cpu":"i5-12700K","gpu":"RTX 2060","ram":"16GB","storage":"60GB SSD"},"recommended":{"os":"Win 11","cpu":"i7-13700K","gpu":"RTX 3080","ram":"32GB","storage":"60GB SSD"}}', FALSE),

-- [ID 7-10] OLDER GAMES (Will get reviews)
('Witch Hunter 3', 'Epic fantasy game with an open world.', 'RPG', 99.99, 'witch.jpg', 'Red Project', '2015-05-19', '{"minimum":{"os":"Win 10","cpu":"i5-12400F","gpu":"GTX 1060","ram":"8GB","storage":"50GB HDD"},"recommended":{"os":"Win 11","cpu":"i7-12700","gpu":"RTX 2070","ram":"16GB","storage":"50GB SSD"}}', FALSE),
('Kingdoms Reborn', 'Strategy game about building and managing your empire.', 'Strategy', 89.99, 'kingdom.jpg', 'Empire Builders', '2023-11-20', '{"minimum":{"os":"Win 10","cpu":"i3-10100","gpu":"GTX 1050 Ti","ram":"8GB","storage":"20GB HDD"},"recommended":{"os":"Win 10","cpu":"i5-10400","gpu":"GTX 1660 Ti","ram":"16GB","storage":"20GB SSD"}}', FALSE),
('Dark Survival', 'Horror survival game in a post-apocalyptic world.', 'Horror', 0, 'survival.jpg', 'Nightmare Studios', '2024-08-05', '{"minimum":{"os":"Win 10","cpu":"i5-10400F","gpu":"GTX 1650","ram":"8GB","storage":"40GB"},"recommended":{"os":"Win 10","cpu":"i7","gpu":"RTX 2070","ram":"16GB","storage":"40GB SSD"}}', FALSE),
('Pixel Legends', 'Retro 2D platformer full of nostalgia.', 'Platformer', 14.99, 'pixel.jpg', 'Retro Bits', '2022-02-14', '{"minimum":{"os":"Win 10","cpu":"Dual Core","gpu":"Integrated","ram":"4GB","storage":"2GB"},"recommended":{}}', FALSE);

-- Admin (ID 1) has everything
INSERT INTO user_library (user_id, game_id) VALUES 
(1, 1), (1, 2), (1, 3), (1, 4), (1, 7), (1, 8), (1, 9), (1, 10);

-- Regular users: own older games so they can post reviews
INSERT INTO user_library (user_id, game_id) VALUES 
(2, 7), (2, 8), (2, 9),  -- User 2 has Witch Hunter, Kingdoms and Survival
(3, 7), (3, 8),          -- User 3 has Witch Hunter and Kingdoms
(4, 7), (4, 10);         -- User 4 has Witch Hunter and Pixel Legends

-- Wishlist for regular users (games they don't own)
INSERT INTO wishlist (user_id, game_id) VALUES 
(1, 5), (1, 6),          -- Admin is waiting for the upcoming releases
(2, 5), (2, 6),          -- User 2 is waiting for the upcoming releases
(3, 1), (3, 2),          -- User 3 wants the latest releases
(4, 3), (4, 4);          -- User 4 wants the latest releases

INSERT INTO reviews (user_id, game_id, rating, content) VALUES 
-- ID 7 Game (Witch Hunter 3) - 3 reviews
(2, 7, 5, 'An absolute masterpiece. The open world is breathtaking and the story is gripping from start to finish!'),
(3, 7, 4, 'Great RPG mechanics, though combat can get a bit repetitive late game. Still highly recommended.'),
(4, 7, 5, 'I got lost in this world for over 100 hours. The side quests are better than most games main storylines.'),

-- ID 8 Game (Kingdoms Reborn) - 2 reviews
(2, 8, 4, 'Really solid strategy game. The empire-building aspects are deep and rewarding.'),
(3, 8, 3, 'Good potential, but the late-game optimization needs some work. It gets laggy with large empires.'),

-- ID 9 (Dark Survival) Game - 1 review
(2, 9, 5, 'Terrifying atmosphere! Playing this with headphones at night is an experience I will never forget.'),

-- ID 10 Game (Pixel Legends) - 1 review
(4, 10, 4, 'A lovely throwback to the classic 16-bit era. Precise platforming and great music.');

INSERT INTO review_likes (user_id, review_id) VALUES 
(2, 2), (2, 3), (2, 5), (3, 1), (3, 4), (3, 7), (4, 1), (4, 4), (4, 6); 