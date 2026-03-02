CREATE TABLE users (
                       id INT AUTO_INCREMENT PRIMARY KEY,
                       username VARCHAR(50) NOT NULL UNIQUE,
                       password_hash VARCHAR(255) NOT NULL,
                       role ENUM('etudiant', 'tuteur') NOT NULL DEFAULT 'etudiant'
);

CREATE TABLE tickets (
                         id INT AUTO_INCREMENT PRIMARY KEY,
                         author_id INT NOT NULL,
                         title VARCHAR(255) NOT NULL,
                         description TEXT NOT NULL,
                         category ENUM('Cours', 'TD', 'TP') NOT NULL,
                         priority ENUM('Basse', 'Moyenne', 'Haute') NOT NULL,
                         status ENUM('Ouvert', 'En cours', 'Résolu') NOT NULL DEFAULT 'Ouvert',
                         created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                         FOREIGN KEY (author_id) REFERENCES users(id)
);

CREATE TABLE comments (
                          id INT AUTO_INCREMENT PRIMARY KEY,
                          ticket_id INT NOT NULL,
                          author_id INT NOT NULL,
                          message TEXT NOT NULL,
                          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                          FOREIGN KEY (ticket_id) REFERENCES tickets(id),
                          FOREIGN KEY (author_id) REFERENCES users(id)
);
