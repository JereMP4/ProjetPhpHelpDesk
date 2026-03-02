<?php
session_start();

// Vérification connexion
if (empty($_SESSION['username'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    ?>
    <!doctype html>
    <html lang="fr">
    <head>
        <meta charset="utf-8">
        <title>Ticket</title>
        <link rel="stylesheet" href="style/afficheTicket.css">
    </head>
    <body>
    <div class="page-wrapper">
        <p class="user-info">
            Accès réservé. Veuillez vous connecter.
        </p>
        <button class="btn btn-primary" onclick="window.location.href='login.php';">
            Se connecter
        </button>
    </div>
    </body>
    </html>
    <?php
    exit;
}

$currentUser = $_SESSION['username'];
$isTuteur    = !empty($_SESSION['is_tuteur']) && $_SESSION['is_tuteur'];

// Récupération de l'id de ticket dans l'URL, par ex. ?id=...
$ticketId = $_GET['id'] ?? '';

$file = __DIR__ . '/tickets.json';
if (!file_exists($file)) {
    die('Fichier tickets.json introuvable');
}

$ticketsJson = file_get_contents($file);
$tickets     = json_decode($ticketsJson, true);

if (!is_array($tickets)) {
    die('Format de tickets.json invalide');
}

// Recherche du ticket courant
$ticketIndex = null;
foreach ($tickets as $index => $t) {
    if ($t['id'] === $ticketId) {
        $ticketIndex = $index;
        break;
    }
}

if ($ticketIndex === null) {
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
        <button class="btn btn-secondary" onclick="window.location.href='listeTickets.php';">
            Retour à la liste
        </button>
    </div>
    </body>
    </html>
    <?php
    exit;
}

$ticket = $tickets[$ticketIndex];

// Droits : étudiant = seulement ses tickets, tuteur = tout
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
        <button class="btn btn-secondary" onclick="window.location.href='listeTickets.php';">
            Retour à la liste
        </button>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// Changement de statut par un tuteur
if ($isTuteur && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = $_POST['status'] ?? '';

    $allowedStatuses = ['ouvert', 'en cours', 'résolu'];
    if (in_array($newStatus, $allowedStatuses, true)) {
        $tickets[$ticketIndex]['status'] = $newStatus;

        file_put_contents($file, json_encode($tickets, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        header('Location: afficheTicket.php?id=' . urlencode($ticketId));
        exit;
    }
}

// Ajout de commentaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $message = trim($_POST['message'] ?? '');

    if ($message !== '') {
        $auteur = $currentUser ?: 'Anonyme';

        $nouveauCommentaire = [
                'auteur'  => $auteur,
                'date'    => date('Y-m-d H:i:s'),
                'message' => $message,
        ];

        if (!isset($tickets[$ticketIndex]['comments']) || !is_array($tickets[$ticketIndex]['comments'])) {
            $tickets[$ticketIndex]['comments'] = [];
        }

        $tickets[$ticketIndex]['comments'][] = $nouveauCommentaire;

        file_put_contents($file, json_encode($tickets, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        header('Location: afficheTicket.php?id=' . urlencode($ticketId));
        exit;
    }
}

// Version à jour
$ticket = $tickets[$ticketIndex];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Ticket <?= htmlspecialchars($ticket['id'], ENT_QUOTES | ENT_SUBSTITUTE); ?></title>
    <link rel="stylesheet" href="style/afficheTicket.css">
</head>
<body>
<div class="page-wrapper">

    <header class="top-bar">
        <div>
            <h1>Ticket n°<?= htmlspecialchars($ticket['id'], ENT_QUOTES | ENT_SUBSTITUTE); ?></h1>
            <p class="user-info">
                Connecté en tant que
                <strong><?= htmlspecialchars($currentUser, ENT_QUOTES | ENT_SUBSTITUTE); ?></strong>
                <?= $isTuteur ? '(tuteur)' : '(étudiant)'; ?>
            </p>
        </div>
        <button type="button" class="btn btn-outline"
                onclick="window.location.href='logout.php?from=<?= urlencode($_SERVER['REQUEST_URI']) ?>';">
            Se déconnecter
        </button>
    </header>

    <p class="back-link">
        <button class="btn btn-secondary" onclick="window.location.href='listeTickets.php';">
            Retour à la liste
        </button>
    </p>

    <section class="ticket-card">
        <?php if ($isTuteur): ?>
            <div class="ticket-row">
                <span class="label">Étudiant :</span>
                <span><?= htmlspecialchars($ticket['author'], ENT_QUOTES | ENT_SUBSTITUTE); ?></span>
            </div>
        <?php endif; ?>

        <div class="ticket-row">
            <span class="label">ID :</span>
            <span><?= htmlspecialchars($ticket['id'], ENT_QUOTES | ENT_SUBSTITUTE); ?></span>
        </div>

        <div class="ticket-row">
            <span class="label">Titre :</span>
            <span><?= htmlspecialchars($ticket['title'], ENT_QUOTES | ENT_SUBSTITUTE); ?></span>
        </div>

        <div class="ticket-description">
            <?= nl2br(htmlspecialchars($ticket['description'], ENT_QUOTES | ENT_SUBSTITUTE)); ?>
        </div>

        <div class="ticket-meta">
            <span class="chip">
                Catégorie :
                <?= htmlspecialchars($ticket['category'], ENT_QUOTES | ENT_SUBSTITUTE); ?>
            </span>
            <span class="chip">
                Priorité :
                <?= htmlspecialchars($ticket['priority'], ENT_QUOTES | ENT_SUBSTITUTE); ?>
            </span>
            <span class="chip">
                Statut :
                <?= htmlspecialchars($ticket['status'], ENT_QUOTES | ENT_SUBSTITUTE); ?>
            </span>
            <span class="chip">
                Créé le
                <?= htmlspecialchars($ticket['created_at'], ENT_QUOTES | ENT_SUBSTITUTE); ?>
            </span>
        </div>
    </section>

    <?php if ($isTuteur): ?>
        <section class="status-section">
            <h2>Modifier le statut</h2>
            <form method="post" action="" class="status-form">
                <label for="status">Statut :</label>
                <select name="status" id="status">
                    <option value="ouvert"   <?= $ticket['status'] === 'ouvert'   ? 'selected' : '' ?>>Ouvert</option>
                    <option value="en cours" <?= $ticket['status'] === 'en cours' ? 'selected' : '' ?>>En cours</option>
                    <option value="résolu"   <?= $ticket['status'] === 'résolu'   ? 'selected' : '' ?>>Résolu</option>
                </select>
                <button type="submit" name="update_status" class="btn btn-primary">
                    Mettre à jour
                </button>
            </form>
        </section>
    <?php endif; ?>

    <section class="comments-section">
        <h2>Commentaires</h2>

        <?php if (!empty($ticket['comments'])): ?>
            <div class="comments-list">
                <?php foreach ($ticket['comments'] as $comment): ?>
                    <article class="comment-card">
                        <header class="comment-header">
                            <span class="comment-author">
                                <?= htmlspecialchars($comment['auteur'], ENT_QUOTES | ENT_SUBSTITUTE); ?>
                            </span>
                            <span class="comment-date">
                                <?= htmlspecialchars($comment['date'], ENT_QUOTES | ENT_SUBSTITUTE); ?>
                            </span>
                        </header>
                        <p class="comment-body">
                            <?= nl2br(htmlspecialchars($comment['message'], ENT_QUOTES | ENT_SUBSTITUTE)); ?>
                        </p>
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
                <button type="submit" name="add_comment" class="btn btn-primary">
                    Envoyer
                </button>
            </div>
        </form>
    </section>

</div>
</body>
</html>
