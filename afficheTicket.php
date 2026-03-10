<?php
session_start();
require_once __DIR__ . '/db.php';

// Vérification connexion
if (empty($_SESSION['username'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

// Vérification du rôle directement en BDD
$stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE username = :username");
$stmt->execute([':username' => $_SESSION['username']]);
$currentUserData = $stmt->fetch(PDO::FETCH_ASSOC);

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

// --- CSRF token ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrfToken = $_SESSION['csrf_token'];

// Récupération de l'id de ticket dans l'URL
if (!isset($_GET['id'])) {
    http_response_code(400);
    ?>
    <!doctype html>
    <html lang="fr">
    <head>
        <meta charset="utf-8">
        <title>Ticket introuvable</title>
        /afficheTicket.css">
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
        /afficheTicket.css">
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

// Vérification accès : un étudiant ne peut voir que ses propres tickets
if (!$isTuteur && $ticket['author_id'] !== $_SESSION['userid']) {
    http_response_code(403);
    ?>
    <!doctype html>
    <html lang="fr">
    <head>
        <meta charset="utf-8">
        <title>Accès refusé</title>
        /afficheTicket.css">
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

// Modification du statut (tuteur uniquement)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    // Vérification CSRF
    if (empty($_POST['csrf_token']) || !hash_equals($csrfToken, $_POST['csrf_token'])) {
        http_response_code(403);
        die('Jeton CSRF invalide.');
    }
    if ($isTuteur) {
        $allowed = ['Ouvert', 'En cours', 'Résolu'];
        $newStatus = $_POST['status'] ?? '';
        if (in_array($newStatus, $allowed, true)) {
            $stmt = $pdo->prepare('UPDATE tickets SET status = :status WHERE id = :id');
            $stmt->execute([':status' => $newStatus, ':id' => $ticketId]);
        }
    }
    header('Location: afficheTicket.php?id=' . urlencode($ticketId));
    exit;
}

// Ajout de commentaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    // Vérification CSRF
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
    /afficheTicket.css">
</head>
<body>
<div class="page-wrapper">

    <?php require_once __DIR__ . '/header.php'; ?>

    <button class="btn btn-secondary" onclick="window.location.href='listeTickets.php'">Retour à la liste</button>

    <div class="ticket-detail">
        <p><strong>ID :</strong> <?= htmlspecialchars($ticket['id'], ENT_QUOTES | ENT_SUBSTITUTE) ?></p>
        <p><strong>Auteur :</strong> <?= htmlspecialchars($ticket['author'], ENT_QUOTES | ENT_SUBSTITUTE) ?></p>
        <p><strong>Titre :</strong> <?= htmlspecialchars($ticket['title'], ENT_QUOTES | ENT_SUBSTITUTE) ?></p>
        <p><strong>Description :</strong> <?= htmlspecialchars($ticket['description'], ENT_QUOTES | ENT_SUBSTITUTE) ?></p>
        <p><strong>Catégorie :</strong> <?= htmlspecialchars($ticket['category'], ENT_QUOTES | ENT_SUBSTITUTE) ?></p>
        <p><strong>Priorité :</strong> <?= htmlspecialchars($ticket['priority'], ENT_QUOTES | ENT_SUBSTITUTE) ?></p>
        <p><strong>Statut :</strong> <?= htmlspecialchars($ticket['status'], ENT_QUOTES | ENT_SUBSTITUTE) ?></p>
        <p><strong>Créé le :</strong> <?= htmlspecialchars($ticket['created_at'], ENT_QUOTES | ENT_SUBSTITUTE) ?></p>
    </div>

    <?php if ($isTuteur): ?>
        <section class="status-update">
            <h2>Modifier le statut</h2>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES | ENT_SUBSTITUTE) ?>">
                abel for="status">Statut</label>
                <select name="status" id="status">
                    <option value="Ouvert"   <?= $ticket['status'] === 'Ouvert'   ? 'selected' : '' ?>>Ouvert</option>
                    <option value="En cours" <?= $ticket['status'] === 'En cours' ? 'selected' : '' ?>>En cours</option>
                    <option value="Résolu"   <?= $ticket['status'] === 'Résolu'   ? 'selected' : '' ?>>Résolu</option>
                </select>
                <button type="submit" name="update_status" class="btn btn-primary">Mettre à jour</button>
            </form>
        </section>
    <?php endif; ?>

    <section class="comments">
        <h2>Commentaires</h2>
        <?php if (empty($comments)): ?>
            <p>Aucun commentaire pour le moment.</p>
        <?php else: ?>
            <?php foreach ($comments as $comment): ?>
                <div class="comment">
                    <p class="comment-meta">
                        <strong><?= htmlspecialchars($comment['auteur'], ENT_QUOTES | ENT_SUBSTITUTE) ?></strong>
                        — <?= htmlspecialchars($comment['date'], ENT_QUOTES | ENT_SUBSTITUTE) ?>
                    </p>
                    <p><?= htmlspecialchars($comment['message'], ENT_QUOTES | ENT_SUBSTITUTE) ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <section class="add-comment">
        <h2>Ajouter un commentaire</h2>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES | ENT_SUBSTITUTE) ?>">
            abel for="message">Message</label>
            <textarea id="message" name="message" required></textarea>
            <button type="submit" name="add_comment" class="btn btn-primary">Envoyer</button>
        </form>
    </section>

</div>
</body>
</html>
