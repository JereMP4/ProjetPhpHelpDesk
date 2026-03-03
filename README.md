# ProjetPhpHelpDesk

Université Bourgogne Europe – L3 Informatique – Développement Applications Web

## Membres du groupe
- THIEBLEMONT Jérémy

## Choix techniques
- **Langage** : PHP sans framework
- **Base de données** : MySQL, accès via PDO avec requêtes préparées
- **Stockage** : Option 2 (base de données) — script de création fourni dans `base.sql`
- **Sécurité** : Protection XSS via `htmlspecialchars`, tokens CSRF sur les formulaires, validation serveur, vérification des rôles en BDD à chaque requête
- **Sessions** : `$_SESSION` avec re-vérification du rôle en BDD à chaque page protégée

## Structure des fichiers
- `login.php` / `logout.php` — Authentification
- `listeTickets.php` — Liste des tickets (filtrée selon le rôle)
- `ticket.php` — Création d'un ticket (étudiant uniquement)
- `afficheTicket.php` — Détail, commentaires, mise à jour statut (tuteur)
- `modifMDP.php` — Modification du mot de passe
- `db.php` + `config.local.php` — Connexion PDO
- `base.sql` — Script SQL de création des tables
- `style/` — Feuilles de style CSS

## Script SQL
Voir `base.sql` — contient la création des tables `users`, `tickets` et `comments`.

© 2026 THIEBLEMONT Jérémy.
