-- =========================================================
-- BASE DE DONNÉES : PARC D'ATTRACTIONS WILD WEST
-- =========================================================

CREATE DATABASE IF NOT EXISTS parc_attractions
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE parc_attractions;

-- =========================================================
-- 1. UTILISATEURS & RÔLES
-- =========================================================

CREATE TABLE role (
    id_role INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(50) NOT NULL UNIQUE -- client, gestionnaire, superadmin
) ENGINE=InnoDB;

CREATE TABLE utilisateur (
    id_utilisateur INT AUTO_INCREMENT PRIMARY KEY,
    id_role INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    date_naissance DATE,
    telephone VARCHAR(20),
    photo_profil VARCHAR(255),
    jetons INT DEFAULT 0,
    email_verifie TINYINT(1) DEFAULT 0,
    token_verification VARCHAR(255),
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP,
    actif TINYINT(1) DEFAULT 1,
    FOREIGN KEY (id_role) REFERENCES role(id_role)
) ENGINE=InnoDB;

-- =========================================================
-- 2. ATTRACTIONS
-- =========================================================

CREATE TABLE type_attraction (
    id_type INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL UNIQUE -- sensation, familial, aquatique...
) ENGINE=InnoDB;

CREATE TABLE attraction (
    id_attraction INT AUTO_INCREMENT PRIMARY KEY,
    id_type INT NOT NULL,
    nom VARCHAR(150) NOT NULL,
    description TEXT,
    age_minimum INT DEFAULT 0,
    taille_minimum INT, -- en cm
    places_disponibles INT NOT NULL DEFAULT 0,
    duree_minutes INT,
    statut ENUM('ouverte','fermee','maintenance') DEFAULT 'ouverte',
    image VARCHAR(255),
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_type) REFERENCES type_attraction(id_type)
) ENGINE=InnoDB;

-- Plages horaires spécifiques par attraction
CREATE TABLE plage_horaire (
    id_plage INT AUTO_INCREMENT PRIMARY KEY,
    id_attraction INT NOT NULL,
    jour_semaine ENUM('lundi','mardi','mercredi','jeudi','vendredi','samedi','dimanche') NOT NULL,
    heure_debut TIME NOT NULL,
    heure_fin TIME NOT NULL,
    capacite_creneau INT NOT NULL DEFAULT 10,
    FOREIGN KEY (id_attraction) REFERENCES attraction(id_attraction) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Créneaux réels générés (date + horaire) pour réservation
CREATE TABLE creneau (
    id_creneau INT AUTO_INCREMENT PRIMARY KEY,
    id_attraction INT NOT NULL,
    date_creneau DATE NOT NULL,
    heure_debut TIME NOT NULL,
    heure_fin TIME NOT NULL,
    places_restantes INT NOT NULL,
    FOREIGN KEY (id_attraction) REFERENCES attraction(id_attraction) ON DELETE CASCADE,
    UNIQUE KEY uniq_creneau (id_attraction, date_creneau, heure_debut)
) ENGINE=InnoDB;

-- =========================================================
-- 3. AVIS SUR LES ATTRACTIONS
-- =========================================================

CREATE TABLE avis (
    id_avis INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    id_attraction INT NOT NULL,
    note TINYINT NOT NULL CHECK (note BETWEEN 1 AND 5),
    message TEXT,
    statut ENUM('en_attente','valide','refuse') DEFAULT 'en_attente',
    date_avis DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE,
    FOREIGN KEY (id_attraction) REFERENCES attraction(id_attraction) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 4. RÉSERVATIONS
-- =========================================================

CREATE TABLE reservation (
    id_reservation INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    id_creneau INT NOT NULL,
    nb_personnes INT NOT NULL DEFAULT 1,
    statut ENUM('confirmee','annulee','terminee') DEFAULT 'confirmee',
    date_reservation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE,
    FOREIGN KEY (id_creneau) REFERENCES creneau(id_creneau) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 5. TICKETS, LOTS, PROMOTIONS
-- =========================================================

CREATE TABLE lot (
    id_lot INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    description TEXT,
    nb_tickets_achetes INT NOT NULL, -- ex: 3
    nb_tickets_offerts INT NOT NULL DEFAULT 0, -- ex: 1
    prix DECIMAL(10,2) NOT NULL,
    date_debut DATE,
    date_fin DATE, -- promo saisonnière (Noël, etc.)
    actif TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE prix_ticket (
    id_prix INT AUTO_INCREMENT PRIMARY KEY,
    id_attraction INT, -- NULL = ticket générique valable pour tout le parc
    libelle VARCHAR(100) NOT NULL, -- "Ticket adulte", "Ticket enfant"
    prix_unitaire DECIMAL(10,2) NOT NULL,
    date_maj DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_attraction) REFERENCES attraction(id_attraction) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE commande (
    id_commande INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    montant_total DECIMAL(10,2) NOT NULL,
    statut ENUM('en_attente','payee','annulee','remboursee') DEFAULT 'en_attente',
    id_stripe_transaction VARCHAR(255),
    date_commande DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE ligne_commande (
    id_ligne INT AUTO_INCREMENT PRIMARY KEY,
    id_commande INT NOT NULL,
    id_prix INT NULL,
    id_lot INT NULL,
    quantite INT NOT NULL DEFAULT 1,
    sous_total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_commande) REFERENCES commande(id_commande) ON DELETE CASCADE,
    FOREIGN KEY (id_prix) REFERENCES prix_ticket(id_prix) ON DELETE SET NULL,
    FOREIGN KEY (id_lot) REFERENCES lot(id_lot) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE ticket (
    id_ticket INT AUTO_INCREMENT PRIMARY KEY,
    id_ligne INT NOT NULL,
    id_utilisateur INT NOT NULL,
    code_qr VARCHAR(255) NOT NULL UNIQUE,
    utilise TINYINT(1) DEFAULT 0,
    date_utilisation DATETIME,
    FOREIGN KEY (id_ligne) REFERENCES ligne_commande(id_ligne) ON DELETE CASCADE,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 6. FORUM DE DISCUSSION
-- =========================================================

CREATE TABLE categorie_forum (
    id_categorie INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE sujet_forum (
    id_sujet INT AUTO_INCREMENT PRIMARY KEY,
    id_categorie INT NOT NULL,
    id_utilisateur INT NOT NULL,
    titre VARCHAR(255) NOT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    epingle TINYINT(1) DEFAULT 0,
    ferme TINYINT(1) DEFAULT 0,
    FOREIGN KEY (id_categorie) REFERENCES categorie_forum(id_categorie) ON DELETE CASCADE,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE message_forum (
    id_message INT AUTO_INCREMENT PRIMARY KEY,
    id_sujet INT NOT NULL,
    id_utilisateur INT NOT NULL,
    contenu TEXT NOT NULL,
    date_message DATETIME DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('visible','masque','signale') DEFAULT 'visible',
    FOREIGN KEY (id_sujet) REFERENCES sujet_forum(id_sujet) ON DELETE CASCADE,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 7. COMMUNAUTÉS / SORTIES
-- =========================================================

CREATE TABLE communaute (
    id_communaute INT AUTO_INCREMENT PRIMARY KEY,
    id_createur INT NOT NULL,
    nom VARCHAR(150) NOT NULL,
    description TEXT,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_createur) REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE membre_communaute (
    id_communaute INT NOT NULL,
    id_utilisateur INT NOT NULL,
    role_membre ENUM('membre','organisateur') DEFAULT 'membre',
    date_adhesion DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_communaute, id_utilisateur),
    FOREIGN KEY (id_communaute) REFERENCES communaute(id_communaute) ON DELETE CASCADE,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE sortie (
    id_sortie INT AUTO_INCREMENT PRIMARY KEY,
    id_communaute INT NOT NULL,
    titre VARCHAR(150) NOT NULL,
    date_sortie DATETIME NOT NULL,
    nb_places_max INT,
    FOREIGN KEY (id_communaute) REFERENCES communaute(id_communaute) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE inscription_sortie (
    id_sortie INT NOT NULL,
    id_utilisateur INT NOT NULL,
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_sortie, id_utilisateur),
    FOREIGN KEY (id_sortie) REFERENCES sortie(id_sortie) ON DELETE CASCADE,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Vote sur les activités préférées au sein d'une communauté
CREATE TABLE sondage (
    id_sondage INT AUTO_INCREMENT PRIMARY KEY,
    id_communaute INT NOT NULL,
    question VARCHAR(255) NOT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_communaute) REFERENCES communaute(id_communaute) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE option_sondage (
    id_option INT AUTO_INCREMENT PRIMARY KEY,
    id_sondage INT NOT NULL,
    id_attraction INT, -- optionnel si le vote porte sur une attraction
    libelle VARCHAR(150) NOT NULL,
    FOREIGN KEY (id_sondage) REFERENCES sondage(id_sondage) ON DELETE CASCADE,
    FOREIGN KEY (id_attraction) REFERENCES attraction(id_attraction) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE vote (
    id_option INT NOT NULL,
    id_utilisateur INT NOT NULL,
    date_vote DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_option, id_utilisateur),
    FOREIGN KEY (id_option) REFERENCES option_sondage(id_option) ON DELETE CASCADE,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Chat privé entre membres d'une communauté
CREATE TABLE message_prive (
    id_message INT AUTO_INCREMENT PRIMARY KEY,
    id_communaute INT NOT NULL,
    id_utilisateur INT NOT NULL,
    contenu TEXT NOT NULL,
    date_message DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_communaute) REFERENCES communaute(id_communaute) ON DELETE CASCADE,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 8. CONCOURS PHOTO
-- =========================================================

CREATE TABLE concours_photo (
    id_concours INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(150) NOT NULL,
    description TEXT,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    jetons_recompense INT DEFAULT 0,
    statut ENUM('en_cours','termine','a_venir') DEFAULT 'a_venir'
) ENGINE=InnoDB;

CREATE TABLE participation_concours (
    id_participation INT AUTO_INCREMENT PRIMARY KEY,
    id_concours INT NOT NULL,
    id_utilisateur INT NOT NULL,
    photo VARCHAR(255) NOT NULL,
    date_participation DATETIME DEFAULT CURRENT_TIMESTAMP,
    est_gagnante TINYINT(1) DEFAULT 0,
    nb_votes INT DEFAULT 0,
    FOREIGN KEY (id_concours) REFERENCES concours_photo(id_concours) ON DELETE CASCADE,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE vote_photo (
    id_participation INT NOT NULL,
    id_utilisateur INT NOT NULL,
    date_vote DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_participation, id_utilisateur),
    FOREIGN KEY (id_participation) REFERENCES participation_concours(id_participation) ON DELETE CASCADE,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 9. NEWSLETTER
-- =========================================================

CREATE TABLE newsletter (
    id_newsletter INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(200) NOT NULL,
    contenu TEXT NOT NULL,
    date_envoi DATETIME,
    id_auteur INT NOT NULL,
    FOREIGN KEY (id_auteur) REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE abonnement_newsletter (
    id_utilisateur INT PRIMARY KEY,
    date_abonnement DATETIME DEFAULT CURRENT_TIMESTAMP,
    actif TINYINT(1) DEFAULT 1,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 10. STATISTIQUES / AFFLUENCE
-- =========================================================

CREATE TABLE frequentation (
    id_frequentation INT AUTO_INCREMENT PRIMARY KEY,
    id_attraction INT NOT NULL,
    date_jour DATE NOT NULL,
    jour_semaine ENUM('lundi','mardi','mercredi','jeudi','vendredi','samedi','dimanche') NOT NULL,
    nb_visiteurs INT NOT NULL DEFAULT 0,
    FOREIGN KEY (id_attraction) REFERENCES attraction(id_attraction) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- DONNÉES DE BASE (SEED)
-- =========================================================

INSERT INTO role (libelle) VALUES ('client'), ('gestionnaire'), ('superadmin');

INSERT INTO type_attraction (libelle) VALUES
('Sensation forte'), ('Familial'), ('Aquatique'), ('Enfant');

-- =========================================================
-- INDEX UTILES POUR RECHERCHE / PERFORMANCE
-- =========================================================

CREATE INDEX idx_attraction_nom ON attraction(nom);
CREATE INDEX idx_message_forum_contenu ON message_forum(contenu(255));
CREATE INDEX idx_sujet_forum_titre ON sujet_forum(titre);
CREATE INDEX idx_utilisateur_email ON utilisateur(email);
