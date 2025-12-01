
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

