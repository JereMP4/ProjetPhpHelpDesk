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
    </head>
    <body>
    <p>Accès réservé. Veuillez vous connecter.</p>
    <button onclick="window.location.href='login.php';">Se connecter</button>
    </body>
    </html>
    <?php
    exit;
}

$currentUser = $_SESSION['username'];
$isTuteur    = !empty($_SESSION['is_tuteur']) && $_SESSION['is_tuteur'];

// Récupération de l'id de ticket dans l'URL, par ex. ?id=3
$ticketId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Chargement des tickets
$file = __DIR__ . '/tickets.json';
if (!file_exists($file)) {
    die('Fichier tickets.json introuvable');
}

$ticketsJson = file_get_contents($file);
$tickets     = json_decode($ticketsJson, true);

if (!is_array($tickets)) {
    die('Format de tickets.json invalide');
}

// Recherche du ticket courant dans $tickets
$ticketIndex = null;
foreach ($tickets as $index => $t) {
    if ((int)$t['id'] === $ticketId) {
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
    </head>
    <body>
    <p>Ticket introuvable.</p>
    <button onclick="window.location.href='listeTickets.php';">Retour à la liste</button>
    </body>
    </html>
    <?php
    exit;
}

$ticket = $tickets[$ticketIndex];

// Vérifier les droits :
// - étudiant : ne peut voir que ses tickets
// - tuteur : peut tout voir
if (!$isTuteur && $ticket['author'] !== $currentUser) {
    http_response_code(403);
    ?>
    <!doctype html>
    <html lang="fr">
    <head>
        <meta charset="utf-8">
        <title>Accès refusé</title>
    </head>
    <body>
    <p>Vous n'avez pas accès à ce ticket.</p>
    <button onclick="window.location.href='listeTickets.php';">Retour à la liste</button>
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

        header('Location: afficheTicket.php?id=' . $ticketId);
        exit;
    }
}

// Traitement du formulaire d'ajout de commentaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $message = trim($_POST['message']);

    if ($message !== '') {
        $auteur = $currentUser ?: 'Anonyme';

        $nouveauCommentaire = [
                'auteur'  => $auteur,
                'date'    => date('Y-m-d H:i:s'),
                'message' => $message
        ];

        if (!isset($tickets[$ticketIndex]['comments']) || !is_array($tickets[$ticketIndex]['comments'])) {
            $tickets[$ticketIndex]['comments'] = [];
        }

        $tickets[$ticketIndex]['comments'][] = $nouveauCommentaire;

        file_put_contents($file, json_encode($tickets, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        header('Location: afficheTicket.php?id=' . $ticketId);
        exit;
    }
}

// Recharger la version à jour du ticket (après éventuelle écriture)
$ticket = $tickets[$ticketIndex];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Ticket <?= htmlspecialchars($ticket['id'], ENT_QUOTES | ENT_SUBSTITUTE); ?></title>
    <style>
        body { font-family: Arial, sans-serif; max-width:800px; margin:2rem auto; padding:0 1rem; }
        .top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; }
        .field-row { display:flex; gap:8px; align-items:center; margin-bottom:4px; }
        .field-row span:first-child { font-weight:bold; }
        .ticket-box { border:1px solid #ccc; padding:10px; }
        .comment { margin-top:1rem; padding:0.5rem; border:1px solid #ddd; }
    </style>
</head>
<body>

<div class="top-bar">
    <h1>Ticket n°<?= htmlspecialchars($ticket['id'], ENT_QUOTES | ENT_SUBSTITUTE); ?></h1>
    <button type="button"
            onclick="window.location.href='logout.php?from=<?= urlencode($_SERVER['REQUEST_URI']) ?>';">
        Se déconnecter
    </button>
</div>

<p>Connecté en tant que
    <strong><?= htmlspecialchars($currentUser, ENT_QUOTES | ENT_SUBSTITUTE); ?></strong>
    <?= $isTuteur ? '(tuteur)' : '(étudiant)'; ?>
</p>

<p>
    <button onclick="window.location.href='listeTickets.php';">Retour à la liste</button>
</p>

<div class="ticket-box">
    <?php if ($isTuteur): ?>
        <p class="field-row">
            <span>Étudiant :</span>
            <span><?= htmlspecialchars($ticket['author'], ENT_QUOTES | ENT_SUBSTITUTE); ?></span>
        </p>
    <?php endif; ?>

    <p class="field-row">
        <span>ID :</span>
        <span><?= htmlspecialchars($ticket['id'], ENT_QUOTES | ENT_SUBSTITUTE); ?></span>
    </p>

    <p class="field-row">
        <span>Titre :</span>
        <span><?= htmlspecialchars($ticket['title'], ENT_QUOTES | ENT_SUBSTITUTE); ?></span>
    </p>

    <p><?= nl2br(htmlspecialchars($ticket['description'], ENT_QUOTES | ENT_SUBSTITUTE)); ?></p>

    <p><strong>Catégorie :</strong>
        <?= htmlspecialchars($ticket['category'], ENT_QUOTES | ENT_SUBSTITUTE); ?></p>
    <p><strong>Priorité :</strong>
        <?= htmlspecialchars($ticket['priority'], ENT_QUOTES | ENT_SUBSTITUTE); ?></p>
    <p><strong>Statut :</strong>
        <?= htmlspecialchars($ticket['status'], ENT_QUOTES | ENT_SUBSTITUTE); ?></p>
    <p><small>Créé le
            <?= htmlspecialchars($ticket['created_at'], ENT_QUOTES | ENT_SUBSTITUTE); ?></small></p>
</div>

<?php if ($isTuteur): ?>
    <hr>
    <h2>Modifier le statut du ticket</h2>
    <form method="post" action="">
        <label for="status">Statut :</label>
        <select name="status" id="status">
            <option value="ouvert"   <?= $ticket['status'] === 'ouvert'   ? 'selected' : '' ?>>Ouvert</option>
            <option value="en cours" <?= $ticket['status'] === 'en cours' ? 'selected' : '' ?>>En cours</option>
            <option value="résolu"   <?= $ticket['status'] === 'résolu'   ? 'selected' : '' ?>>Résolu</option>
        </select>
        <button type="submit" name="update_status">Mettre à jour</button>
    </form>
<?php endif; ?>

<hr>

<?php
if (!empty($ticket['comments'])) {
    echo '<h2>Commentaires</h2>';
    foreach ($ticket['comments'] as $comment) {
        echo '<div class="comment">';
        echo '<p><strong>' . htmlspecialchars($comment['auteur'], ENT_QUOTES | ENT_SUBSTITUTE) . '</strong> - ' .
                htmlspecialchars($comment['date'], ENT_QUOTES | ENT_SUBSTITUTE) . '</p>';
        echo '<p>' . nl2br(htmlspecialchars($comment['message'], ENT_QUOTES | ENT_SUBSTITUTE)) . '</p>';
        echo '</div>';
    }
} else {
    echo '<p>Aucun commentaire pour le moment.</p>';
}
?>

<hr>
<h2>Ajouter un commentaire</h2>
<form method="post" action="">
    <div>
        <label for="message">Message :</label><br>
        <textarea id="message" name="message" rows="4" cols="50" required></textarea>
    </div>
    <br>
    <button type="submit" name="add_comment">Envoyer</button>
</form>

</body>
</html>
