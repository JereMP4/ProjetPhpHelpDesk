<?php
session_start();
require_once __DIR__ . '/db.php';

// Vérification connexion (cohérent avec listeTickets)
if (empty($_SESSION['username'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

// --- Vérification du rôle directement en BDD (modèle PDO de login.php) ---
$stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE username = :username");
$stmt->execute([':username' => $_SESSION['username']]);
$currentUserData = $stmt->fetch(PDO::FETCH_ASSOC);

// Si l'utilisateur n'existe plus en BDD → on détruit la session
if (!$currentUserData) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Mise à jour de la session avec les données fraîches de la BDD
$_SESSION['userid']    = $currentUserData['id'];
$_SESSION['role']      = $currentUserData['role'] ?? 'etudiant';
$_SESSION['is_tuteur'] = ($_SESSION['role'] === 'tuteur');

$currentUser = $currentUserData['username'];
$isTuteur    = $_SESSION['is_tuteur'];

// Récupération de l'id de ticket dans l'URL, par ex. ?id=...
if (!isset($_GET['id'])) {
    http_response_code(400);
    ?>
    <!doctype html>
    <html lang="fr">
    <head>
        <meta charset="utf-8">
        <title>Ticket introuvable</title>
        <link rel="stylesheet" href="style/afficheTicket.css">
    </head>
    <body>
    <div class="page-wrapper">
        <p class="user-info">Identifiant de ticket manquant.</p>
        <button class="btn btn-secondary" onclick="window.location.href='listeTickets.php'">Retour à la liste</button>
    </div>
    </body>
    </html>
    <?php
    exit;
}

$ticketId = (int) $_GET['id'];

// Récupération du ticket
$stmt = $pdo->prepare('SELECT t.*, u.username AS author FROM tickets t JOIN users u ON u.id = t.author_id WHERE t.id = :id');
$stmt->execute([':id' => $ticketId]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    http_response_code(404);
    ?>
    <!doctype html>
    <html lang="fr">
    <head>
        <meta charset="utf-8">
        <title>Ticket introuvable</title>
        <link rel="stylesheet" href="style/afficheTicket.css">
    </head>
    <body>
    <div class="page-wrapper">
        <p class="user-info">Ticket introuvable.</p>
        <button class="btn btn-secondary" onclick="window.location.href='listeTickets.php'">Retour à la liste</button>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// Droits : étudiant seulement ses tickets, tuteur tout
if (!$isTuteur && $ticket['author'] !== $currentUser) {
    http_response_code(403);
    ?>
    <!doctype html>
    <html lang="fr">
    <head>
        <meta charset="utf-8">
        <title>Accès refusé</title>
        <link rel="stylesheet" href="style/afficheTicket.css">
    </head>
    <body>
    <div class="page-wrapper">
        <p class="user-info">Vous n'avez pas accès à ce ticket.</p>
        <button class="btn btn-secondary" onclick="window.location.href='listeTickets.php'">Retour à la liste</button>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// Changement de statut par un tuteur
if ($isTuteur && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus       = $_POST['status'] ?? '';
    $allowedStatuses = ['Ouvert', 'En cours', 'Résolu'];
    if (in_array($newStatus, $allowedStatuses, true)) {
        $stmt = $pdo->prepare('UPDATE tickets SET status = :status WHERE id = :id');
        $stmt->execute([':status' => $newStatus, ':id' => $ticketId]);
    }
    header('Location: afficheTicket.php?id=' . urlencode($ticketId));
    exit;
}

// Ajout de commentaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $message = trim($_POST['message'] ?? '');
    if ($message !== '') {
        $stmt = $pdo->prepare('INSERT INTO comments (ticket_id, author_id, message, created_at) VALUES (:ticket_id, :author_id, :message, NOW())');
        $stmt->execute([
                ':ticket_id' => $ticketId,
                ':author_id' => $_SESSION['userid'],
                ':message'   => $message,
        ]);
    }
    header('Location: afficheTicket.php?id=' . urlencode($ticketId));
    exit;
}

// Récupération des commentaires
$stmt = $pdo->prepare('SELECT c.message, c.created_at AS date, u.username AS auteur FROM comments c JOIN users u ON u.id = c.author_id WHERE c.ticket_id = :ticket_id ORDER BY c.created_at ASC');
$stmt->execute([':ticket_id' => $ticketId]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Ticket <?= htmlspecialchars($ticket['id'], ENT_QUOTES | ENT_SUBSTITUTE) ?></title>
    <link rel="stylesheet" href="style/afficheTicket.css">
</head>
<body>
<div class="page-wrapper">

    <?php
    // Header factorisé (menu profil + redirection si pas connecté)
    $pageTitle = 'Ticket n°' . htmlspecialchars($ticket['id'], ENT_QUOTES | ENT_SUBSTITUTE);
    include __DIR__ . '/header.php';
    ?>

    <p class="back-link">
        <button class="btn btn-secondary" onclick="window.location.href='listeTickets.php'">Retour à la liste</button>
    </p>

    <section class="ticket-card">
        <?php if ($isTuteur): ?>
            <div class="ticket-row">
                <span class="label">Étudiant</span>
                <span><?= htmlspecialchars($ticket['author'], ENT_QUOTES | ENT_SUBSTITUTE) ?></span>
            </div>
        <?php endif; ?>
        <div class="ticket-row">
            <span class="label">ID</span>
            <span><?= htmlspecialchars($ticket['id'], ENT_QUOTES | ENT_SUBSTITUTE) ?></span>
        </div>
        <div class="ticket-row">
            <span class="label">Titre</span>
            <span><?= htmlspecialchars($ticket['title'], ENT_QUOTES | ENT_SUBSTITUTE) ?></span>
        </div>
        <div class="ticket-description">
            <?= nl2br(htmlspecialchars($ticket['description'], ENT_QUOTES | ENT_SUBSTITUTE)) ?>
        </div>
        <div class="ticket-meta">
            <span class="chip">Catégorie <?= htmlspecialchars($ticket['category'], ENT_QUOTES | ENT_SUBSTITUTE) ?></span>
            <span class="chip">Priorité <?= htmlspecialchars($ticket['priority'], ENT_QUOTES | ENT_SUBSTITUTE) ?></span>
            <span class="chip">Statut <?= htmlspecialchars($ticket['status'], ENT_QUOTES | ENT_SUBSTITUTE) ?></span>
            <span class="chip">Créé le <?= htmlspecialchars($ticket['created_at'], ENT_QUOTES | ENT_SUBSTITUTE) ?></span>
        </div>
    </section>

    <?php if ($isTuteur): ?>
        <section class="status-section">
            <h2>Modifier le statut</h2>
            <form method="post" action="" class="status-form">
                <label for="status">Statut</label>
                <select name="status" id="status">
                    <option value="Ouvert"   <?= $ticket['status'] === 'Ouvert'   ? 'selected' : '' ?>>Ouvert</option>
                    <option value="En cours" <?= $ticket['status'] === 'En cours' ? 'selected' : '' ?>>En cours</option>
                    <option value="Résolu"   <?= $ticket['status'] === 'Résolu'   ? 'selected' : '' ?>>Résolu</option>
                </select>
                <button type="submit" name="update_status" class="btn btn-primary">Mettre à jour</button>
            </form>
        </section>
    <?php endif; ?>

    <section class="comments-section">
        <h2>Commentaires</h2>
        <?php if (!empty($comments)): ?>
            <div class="comments-list">
                <?php foreach ($comments as $comment): ?>
                    <article class="comment-card">
                        <header class="comment-header">
                            <span class="comment-author"><?= htmlspecialchars($comment['auteur'], ENT_QUOTES | ENT_SUBSTITUTE) ?></span>
                            <span class="comment-date"><?= htmlspecialchars($comment['date'], ENT_QUOTES | ENT_SUBSTITUTE) ?></span>
                        </header>
                        <p class="comment-body"><?= nl2br(htmlspecialchars($comment['message'], ENT_QUOTES | ENT_SUBSTITUTE)) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="no-comments">Aucun commentaire pour le moment.</p>
        <?php endif; ?>
    </section>

    <section class="add-comment-section">
        <h2>Ajouter un commentaire</h2>
        <form method="post" action="" class="comment-form">
            <div class="form-group">
                <label for="message">Message</label>
                <textarea id="message" name="message" rows="4" required></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" name="add_comment" class="btn btn-primary">Envoyer</button>
            </div>
        </form>
    </section>
</div>
</body>
</html>
