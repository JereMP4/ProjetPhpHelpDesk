# ProjetPhpHelpDesk

Université Bourgogne Europe – L3 Informatique – Développement d’applications Web

## Membres du groupe

- THIEBLEMONT Jérémy  
- RAHALI Thiziri  
- COIPELET Maxence  

## Description du projet

Projet de helpdesk web permettant à des étudiants de créer des tickets et à des tuteurs de les consulter, les commenter et mettre à jour leur statut.  
L’objectif est de mettre en pratique un développement PHP sans framework, avec une base MySQL et une gestion correcte des sessions et des rôles utilisateurs.

## Choix techniques

- **Langage** : PHP (sans framework)  
- **Base de données** : MySQL, accès via PDO avec requêtes préparées  
- **Stockage** : Option 2 (base de données) — script de création fourni dans `base.sql`  
- **Sécurité** :  
  - Protection XSS via `htmlspecialchars`  
  - Tokens CSRF sur les formulaires sensibles  
  - Validation côté serveur sur tous les formulaires importants  
  - Vérification du rôle utilisateur en base à chaque accès à une page protégée  
- **Sessions** :  
  - Utilisation de `$_SESSION` pour stocker l’utilisateur connecté et son rôle  
  - Re-vérification régulière des informations en base pour éviter la manipulation côté client  

## Structure des fichiers

- `index.php` — Page d’accueil / redirections selon l’état de connexion  
- `login.php` / `logout.php` — Authentification (connexion / déconnexion)  
- `listeTickets.php` — Liste des tickets (filtrée selon le rôle : étudiant / tuteur / admin)  
- `ticket.php` — Création d’un ticket (accessible aux étudiants)  
- `afficheTicket.php` — Détail d’un ticket, commentaires, mise à jour du statut (tuteur / admin)  
- `modifMDP.php` — Modification du mot de passe par l’utilisateur connecté  
- `db.php` + `config.local.php` — Connexion PDO à la base de données  
- `base.sql` — Script SQL de création de la base et des tables  
- `style/` — Feuilles de style CSS du projet  
- `hash.php` — Outil de développement pour générer le hash d’un mot de passe (pour insertion en base ; utilisé uniquement côté développeur)

## Base de données

Le script `base.sql` contient :

- La création de la base (si nécessaire)  
- La création des tables principales :  
  - `users` — utilisateurs de l’application (étudiants, tuteurs, admin, etc.)  
  - `tickets` — tickets créés par les étudiants  
  - `comments` — commentaires associés aux tickets  
- Des triggers SQL permettant de restreindre certaines actions, par exemple la création de tickets uniquement pour les étudiants.

## Remarques

- Les paramètres de connexion (`host`, `port`, `dbname`, `user`, `password`) sont définis dans `config.local.php`, adapté à l’environnement de TP.  
- Ce projet est réalisé dans le cadre d’un enseignement universitaire et n’est pas destiné à un déploiement en production tel quel (configuration, sécurité, gestion des erreurs, etc. seraient à renforcer pour un usage réel).

© 2026 THIEBLEMONT Jérémy. Tous droits réservés.  
Utilisation, modification et redistribution interdites sans autorisation écrite.
