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

// Récupérer l'id dans l'URL
$ticketId = $_GET['id'] ?? '';
$ticket   = null;

if ($ticketId !== '') {
    $file = __DIR__ . '/tickets.json';
    if (file_exists($file)) {
        $json    = file_get_contents($file);
        $decoded = json_decode($json, true);
        if (is_array($decoded)) {
            foreach ($decoded as $t) {
                if ($t['id'] === $ticketId) {
                    $ticket = $t;
                    break;
                }
            }
        }
    }
}

// Si pas trouvé
if ($ticket === null) {
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

</body>
</html>
