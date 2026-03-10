<?php
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['username'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE username = :username");
$stmt->execute([':username' => $_SESSION['username']]);
$currentUserData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentUserData) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$_SESSION['userid']    = $currentUserData['id'];
$_SESSION['role']      = $currentUserData['role'] ?? 'etudiant';
$_SESSION['is_tuteur'] = ($_SESSION['role'] === 'tuteur');

$currentUser = $currentUserData['username'];
$isTuteur    = $_SESSION['is_tuteur'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrfToken = $_SESSION['csrf_token'];

if (!isset($_GET['id'])) {
    http_response_code(400);
    ?>
    <!doctype html>
    <html lang="fr">
    <head>
        <meta charset="utf-8">
        <title>Ticket introuvable</title>
        <link href="style/afficheTicket.css" rel="stylesheet">
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
        <link href="style/afficheTicket.css" rel="stylesheet">
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

if (!$isTuteur && $ticket['author_id'] !== $_SESSION['userid']) {
    http_response_code(403);
    ?>
    <!doctype html>
    <html lang="fr">
    <head>
        <meta charset="utf-8">
        <title>Accès refusé</title>
        <link href="style/afficheTicket.css" rel="stylesheet">
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (empty($_POST['csrf_token']) || !hash_equals($csrfToken, $_POST['csrf_token'])) {
        http_response_code(403);
        die('Jeton CSRF invalide.');
    }
    if ($isTuteur) {
        $allowed   = ['Ouvert', 'En cours', 'Résolu'];
        $newStatus = $_POST['status'] ?? '';
        if (in_array($newStatus, $allowed, true)) {
            $stmt = $pdo->prepare('UPDATE tickets SET status = :status WHERE id = :id');
            $stmt->execute([':status' => $newStatus, ':id' => $ticketId]);
        }
    }
    header('Location: afficheTicket.php?id=' . urlencode($ticketId));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    if (empty($_POST['csrf_token']) || !hash_equals($csrfToken, $_POST['csrf_token'])) {
        http_response_code(403);
        die('Jeton CSRF invalide.');
    }
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

$stmt = $pdo->prepare('SELECT c.message, c.created_at AS date, u.username AS auteur FROM comments c JOIN users u ON u.id = c.author_id WHERE c.ticket_id = :ticket_id ORDER BY c.created_at ASC');
$stmt->execute([':ticket_id' => $ticketId]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Ticket <?= htmlspecialchars($ticket['id'], ENT_QUOTES | ENT_SUBSTITUTE) ?></title>
    <link href="style/afficheTicket.css" rel="stylesheet">
</head>
<body>
<div class="page-wrapper">

    <?php require_once __DIR__ . '/header.php'; ?>

    <div class="back-link">
        <button class="btn btn-secondary" onclick="window.location.href='listeTickets.php'">Retour à la liste</button>
    </div>

    <div class="ticket-card">
        <div class="ticket-row">
            <span class="label">Étudiant :</span>
            <span><?= htmlspecialchars($ticket['author'], ENT_QUOTES | ENT_SUBSTITUTE) ?></span>
        </div>
        <div class="ticket-row">
            <span class="label">ID :</span>
            <span><?= htmlspecialchars($ticket['id'], ENT_QUOTES | ENT_SUBSTITUTE) ?></span>
        </div>
        <div class="ticket-row">
            <span class="label">Titre :</span>
            <span><?= htmlspecialchars($ticket['title'], ENT_QUOTES | ENT_SUBSTITUTE) ?></span>
        </div>
        <p class="ticket-description"><?= htmlspecialchars($ticket['description'], ENT_QUOTES | ENT_SUBSTITUTE) ?></p>
        <div class="ticket-meta">
            <span class="chip">Catégorie : <?= htmlspecialchars($ticket['category'], ENT_QUOTES | ENT_SUBSTITUTE) ?></span>
            <span class="chip">Priorité : <?= htmlspecialchars($ticket['priority'], ENT_QUOTES | ENT_SUBSTITUTE) ?></span>
            <span class="chip">Statut : <?= htmlspecialchars($ticket['status'], ENT_QUOTES | ENT_SUBSTITUTE) ?></span>
            <span class="chip">Créé le <?= htmlspecialchars($ticket['created_at'], ENT_QUOTES | ENT_SUBSTITUTE) ?></span>
        </div>
    </div>

    <?php if ($isTuteur): ?>
        <div class="status-section">
            <h2>Modifier le statut</h2>
            <form class="status-form" method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES | ENT_SUBSTITUTE) ?>">
                <label for="status">Statut :</label>
                <select name="status" id="status">
                    <option value="Ouvert"   <?= $ticket['status'] === 'Ouvert'   ? 'selected' : '' ?>>Ouvert</option>
                    <option value="En cours" <?= $ticket['status'] === 'En cours' ? 'selected' : '' ?>>En cours</option>
                    <option value="Résolu"   <?= $ticket['status'] === 'Résolu'   ? 'selected' : '' ?>>Résolu</option>
                </select>
                <button type="submit" name="update_status" class="btn btn-primary">Mettre à jour</button>
            </form>
        </div>
    <?php endif; ?>

    <div class="comments-section">
        <h2>Commentaires</h2>
        <?php if (empty($comments)): ?>
            <p class="no-comments">Aucun commentaire pour le moment.</p>
        <?php else: ?>
            <div class="comments-list">
                <?php foreach ($comments as $comment): ?>
                    <div class="comment-card">
                        <div class="comment-header">
                            <span class="comment-author"><?= htmlspecialchars($comment['auteur'], ENT_QUOTES | ENT_SUBSTITUTE) ?></span>
                            <span class="comment-date"><?= htmlspecialchars($comment['date'], ENT_QUOTES | ENT_SUBSTITUTE) ?></span>
                        </div>
                        <p class="comment-body"><?= htmlspecialchars($comment['message'], ENT_QUOTES | ENT_SUBSTITUTE) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="add-comment-section">
        <h2>Ajouter un commentaire</h2>
        <form class="comment-form" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES | ENT_SUBSTITUTE) ?>">
            <div class="form-group">
                <label for="message">Message</label>
                <textarea id="message" name="message" required></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" name="add_comment" class="btn btn-primary">Envoyer</button>
            </div>
        </form>
    </div>

</div>
</body>
</html>
