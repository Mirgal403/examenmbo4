
use bowl;
CREATE TABLE IF NOT EXISTS klant (
    klant_id        INT AUTO_INCREMENT PRIMARY KEY,
    voornaam        VARCHAR(50),
    achternaam      VARCHAR(50),
    email           VARCHAR(100),
    wachtwoord_hash VARCHAR(255),
    telefoon        VARCHAR(20)
);
SHOW TABLES;
SELECT * FROM klant;
CREATE TABLE IF NOT EXISTS medewerker (
    medewerker_id    INT AUTO_INCREMENT PRIMARY KEY,
    naam             VARCHAR(100) NOT NULL,
    email            VARCHAR(100) NOT NULL UNIQUE,
    wachtwoord_hash  VARCHAR(255) NOT NULL,
    rol              VARCHAR(50)  NOT NULL
);
CREATE TABLE IF NOT EXISTS baan (
    baan_id        INT AUTO_INCREMENT PRIMARY KEY,
    baan_nummer    INT NOT NULL UNIQUE,
    is_kinderbaan  TINYINT(1) NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS optie (
    optie_id      INT AUTO_INCREMENT PRIMARY KEY,
    naam          VARCHAR(100) NOT NULL,
    beschrijving  VARCHAR(255),
    meerprijs     DECIMAL(6,2) NOT NULL
);

CREATE TABLE IF NOT EXISTS tarief (
    tarief_id      INT AUTO_INCREMENT PRIMARY KEY,
    naam           VARCHAR(50)   NOT NULL,        -- bv. 'Ma-do', 'Vr-zo middag'
    prijs_per_uur  DECIMAL(6,2)  NOT NULL,
    tijd_van       TIME          NOT NULL,
    tijd_tot       TIME          NOT NULL
);
CREATE TABLE IF NOT EXISTS reservering (
    reservering_id      INT AUTO_INCREMENT PRIMARY KEY,
    klant_id            INT NOT NULL,
    medewerker_id       INT NULL,
    baan_id             INT NOT NULL,
    tarief_id           INT NULL,
    datum               DATE NOT NULL,
    starttijd           TIME NOT NULL,
    eindtijd            TIME NOT NULL,
    duur_uren           TINYINT NOT NULL,
    aantal_volwassenen  INT NOT NULL,
    aantal_kinderen     INT NOT NULL,
    totaal_prijs        DECIMAL(6,2) NOT NULL,
    is_magic_bowlen     TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_res_klant
        FOREIGN KEY (klant_id) REFERENCES klant(klant_id),
    CONSTRAINT fk_res_medewerker
        FOREIGN KEY (medewerker_id) REFERENCES medewerker(medewerker_id),
    CONSTRAINT fk_res_baan
        FOREIGN KEY (baan_id) REFERENCES baan(baan_id),
    CONSTRAINT fk_res_tarief
        FOREIGN KEY (tarief_id) REFERENCES tarief(tarief_id)
);
CREATE TABLE IF NOT EXISTS reservering_optie (
    reservering_id  INT NOT NULL,
    optie_id        INT NOT NULL,
    aantal          INT NOT NULL DEFAULT 1,
    PRIMARY KEY (reservering_id, optie_id),
    CONSTRAINT fk_ro_reservering
        FOREIGN KEY (reservering_id) REFERENCES reservering(reservering_id),
    CONSTRAINT fk_ro_optie
        FOREIGN KEY (optie_id) REFERENCES optie(optie_id)
);

CREATE TABLE IF NOT EXISTS score (
    score_id        INT AUTO_INCREMENT PRIMARY KEY,
    reservering_id  INT NOT NULL,
    speler_naam     VARCHAR(100) NOT NULL,
    score           INT NOT NULL,
    CONSTRAINT fk_score_reservering
        FOREIGN KEY (reservering_id) REFERENCES reservering(reservering_id)
);
SHOW TABLES;

-- Create users table for authentication and admin functionality
USE bowl;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role VARCHAR(50) NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert initial admin user (username: 123456, password: 123)
INSERT INTO users (username, email, password, role) VALUES 
('123456', 'admin@example.com', '$2y$10$YIjlrWxVd0V7MKlkGkq1..x7nL.8Qz7VpP6L8VK2.KZ0kJ1X6QLLK', 'admin')
ON DUPLICATE KEY UPDATE role='admin';

SHOW TABLES;
-- Voeg testdata toe aan de database

-- Voeg banen toe
INSERT INTO baan (baan_nummer, is_kinderbaan) VALUES
(1, 0),
(2, 0),
(3, 0),
(4, 0),
(5, 0),
(6, 0),
(7, 1),
(8, 1);

-- Voeg tarieven toe
INSERT INTO tarief (naam, prijs_per_uur, tijd_van, tijd_tot) VALUES
('Ma-do 14:00-22:00', 24.00, '14:00:00', '22:00:00'),
('Vr-zo 14:00-18:00', 28.00, '14:00:00', '18:00:00'),
('Vr-zo 18:00-24:00', 31.50, '18:00:00', '24:00:00');

-- Voeg opties toe
INSERT INTO optie (naam, beschrijving, meerprijs) VALUES
('Snackpakket basis de luxe', 'Chips, cola en verassing', 15.00),
('Kinderpartij (chips, cola en verassing)', 'Speciaal voor kinderverjaardagen', 25.00),
('Vrijgezellenfeest', 'Decoratie en special menu', 40.00);