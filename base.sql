-- --------------------------------------------------------
-- HelpDesk L3 - Script de création de la base de données
-- --------------------------------------------------------

-- Désactivation temporaire des clés étrangères pour éviter
-- les conflits lors de la suppression/recréation des tables
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Tables
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS users (
                                     id            INT AUTO_INCREMENT PRIMARY KEY,
                                     username      VARCHAR(50)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('etudiant', 'tuteur') NOT NULL DEFAULT 'etudiant'
    );

CREATE TABLE IF NOT EXISTS tickets (
                                       id          INT AUTO_INCREMENT PRIMARY KEY,
                                       author_id   INT          NOT NULL,
                                       title       VARCHAR(255) NOT NULL,
    description TEXT         NOT NULL,
    category    ENUM('Cours', 'TD', 'TP')            NOT NULL,
    priority    ENUM('Basse', 'Moyenne', 'Haute')     NOT NULL,
    status      ENUM('Ouvert', 'En cours', 'Résolu') NOT NULL DEFAULT 'Ouvert',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id)
    );

CREATE TABLE IF NOT EXISTS comments (
                                        id         INT AUTO_INCREMENT PRIMARY KEY,
                                        ticket_id  INT  NOT NULL,
                                        author_id  INT  NOT NULL,
                                        message    TEXT NOT NULL,
                                        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                        FOREIGN KEY (ticket_id) REFERENCES tickets(id),
    FOREIGN KEY (author_id) REFERENCES users(id)
    );

SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Triggers
-- --------------------------------------------------------

DELIMITER $$

DROP TRIGGER IF EXISTS `tickets_only_student`$$
CREATE TRIGGER `tickets_only_student` BEFORE INSERT ON `tickets`
    FOR EACH ROW BEGIN
    DECLARE user_role VARCHAR(20);
    SELECT role INTO user_role FROM users WHERE id = NEW.author_id;
    IF user_role <> 'etudiant' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Seuls les étudiants peuvent créer des tickets';
END IF;
END$$

DROP TRIGGER IF EXISTS `tickets_only_student2`$$
CREATE TRIGGER `tickets_only_student2` BEFORE UPDATE ON `tickets`
    FOR EACH ROW BEGIN
    DECLARE user_role VARCHAR(20);
    SELECT role INTO user_role FROM users WHERE id = NEW.author_id;
    IF user_role <> 'etudiant' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Seuls les étudiants peuvent créer des tickets';
END IF;
END$$

DELIMITER ;
