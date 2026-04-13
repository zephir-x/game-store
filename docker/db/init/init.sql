-- Cleaning the database (for easier testing)
DROP TABLE IF EXISTS reviews CASCADE;
DROP TABLE IF EXISTS library CASCADE;
DROP TABLE IF EXISTS user_details CASCADE;
DROP TABLE IF EXISTS games CASCADE;
DROP TABLE IF EXISTS users CASCADE;

-- Creating tables (entities) with relationships and constraints

-- Login and Role Table
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'USER' CHECK (role IN ('USER', 'ADMIN')),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Profile Details Table (1:1 relationship with users)
CREATE TABLE user_details (
    user_id INTEGER PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(100),
    surname VARCHAR(100),
    bio TEXT,
    avatar VARCHAR(255) DEFAULT 'default_avatar.png', -- path to predefined avatar
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Games Table
CREATE TABLE games (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    category VARCHAR(100) NOT NULL, -- e.g., RPG, FPS
    price NUMERIC(10, 2) NOT NULL, -- NUMERIC perfect for currencies
    release_date DATE,
    specification TEXT, -- system requirements
    graphics VARCHAR(255), -- path to thumbnail/box art
    average_rating NUMERIC(3, 2) DEFAULT 0.00, -- updated by Trigger
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Player Library Table (M:N relationship between users and games)
CREATE TABLE library (
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    game_id INTEGER REFERENCES games(id) ON DELETE CASCADE,
    purchased_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, game_id) -- composite primary key (prevents duplicates)
);

-- Review table (1:N ratio with users and games)
CREATE TABLE reviews (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    game_id INTEGER REFERENCES games(id) ON DELETE CASCADE,
    rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5), -- scale 1-5
    content TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, game_id) -- User can only add one review per game
);

-- Functions and Triggers

-- Function calculating and updating the average rating of a game
CREATE OR REPLACE FUNCTION update_game_average_rating()
RETURNS TRIGGER AS $$
BEGIN
    UPDATE games
    SET average_rating = (
        SELECT COALESCE(ROUND(AVG(rating)::numeric, 2), 0.00)
        FROM reviews
        WHERE game_id = COALESCE(NEW.game_id, OLD.game_id)
    )
    WHERE id = COALESCE(NEW.game_id, OLD.game_id);

    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

-- Trigger listening for changes in the reviews table
CREATE TRIGGER trigger_update_rating
AFTER INSERT OR UPDATE OR DELETE ON reviews
FOR EACH ROW
EXECUTE FUNCTION update_game_average_rating();

-- Views

-- User Library Details (Join of 3 tables)
-- For displaying on the user's profile under the "Library" tab
CREATE OR REPLACE VIEW v_user_library_details AS
SELECT 
    l.user_id,
    g.id AS game_id,
    g.title,
    g.category,
    g.graphics,
    g.price,
    l.purchased_at
FROM library l
JOIN games g ON l.game_id = g.id;

-- Game Statistics (Join of 2 tables)
-- For the store - shows the number of reviews and the average rating
CREATE OR REPLACE VIEW v_game_statistics AS
SELECT 
    g.id AS game_id,
    g.title,
    g.average_rating,
    COUNT(r.id) AS total_reviews,
    COALESCE(MAX(r.rating), 0) AS highest_rating,
    COALESCE(MIN(r.rating), 0) AS lowest_rating
FROM games g
LEFT JOIN reviews r ON g.id = r.game_id
GROUP BY g.id, g.title, g.average_rating;

-- Sample data (mock data)

-- Start by adding an admin (the password is "admin123" hashed with the BCRYPT algorithm)
INSERT INTO users (username, email, password_hash, role) 
VALUES ('admin', 'admin@gmail.com', '$2y$10$il9iJULRoOJWqY4L9PmOGeL32WJnLdPYWjtWtS3KnxWBXxWK6dQnG', 'ADMIN');

INSERT INTO user_details (user_id, name, surname, bio) 
VALUES (1, 'Main', 'Administrator', 'I am the owner of GameNest');

-- A few games to start with
INSERT INTO games (title, description, category, price, specification, graphics) VALUES 
('CyberStrike 2077', 'Futuristic RPG game.', 'RPG', 199.99, 'CPU: i7, RAM: 16GB, GPU: RTX 3060', 'cyberstrike.jpg'),
('Witch Hunter 3', 'Epic fantasy game with an open world.', 'RPG', 99.50, 'CPU: i5, RAM: 8GB, GPU: GTX 1060', 'witchhunter.jpg'),
('Space Marines', 'Futuristic FPS filled with action.', 'FPS', 120.00, 'CPU: i5, RAM: 8GB, GPU: GTX 1650', 'spacemarines.jpg');