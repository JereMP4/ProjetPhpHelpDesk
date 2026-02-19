<?php
session_start();

// --- Vérification connexion ---
if (empty($_SESSION['username'])) {
    // mémoriser la page pour revenir après login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    ?>
    <!doctype html>
    <html lang="fr">
    <head>
        <meta charset="utf-8">
        <title>Espace Personnel</title>
    </head>
    <body>
    <p>Accès réservé aux étudiants et tuteurs. Veuillez vous connecter.</p>
    <button onclick="window.location.href='login.php';">Se connecter</button>
    </body>
    </html>
    <?php
    exit;
}

$currentUser = $_SESSION['username'];
$isTuteur    = !empty($_SESSION['is_tuteur']) && $_SESSION['is_tuteur'];

// Charger les tickets
$file    = __DIR__ . '/tickets.json';
$tickets = [];

if (file_exists($file)) {
    $json    = file_get_contents($file);
    $decoded = json_decode($json, true);
    if (is_array($decoded)) {
        $tickets = $decoded;
    }
}

// Filtres
$filtreCategorie = $_GET['categorie'] ?? '';
$filtrePriorite  = $_GET['priorite']  ?? '';
$filtreStatut    = $_GET['statut']    ?? '';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Espace Personnel</title>
    <style>
        body { font-family: Arial, sans-serif; max-width:800px; margin:2rem auto; padding:0 1rem; }
        .top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; }
        .ticket { border:1px solid #ccc; padding:10px; margin-bottom:10px; }
    </style>
</head>
<body>

<div class="top-bar">
    <h1>Espace Personnel</h1>
    <button type="button"
            onclick="window.location.href='logout.php?from=<?= urlencode($_SERVER['REQUEST_URI']) ?>';">
        Se déconnecter
    </button>
</div>

<p>Connecté en tant que :
    <strong><?= htmlspecialchars($currentUser, ENT_QUOTES | ENT_SUBSTITUTE); ?></strong>
    <?php if ($isTuteur): ?>
        (tuteur)
    <?php else: ?>
        (étudiant)
    <?php endif; ?>
</p>

<hr>

<div style="display:flex; gap:10px; margin-bottom:15px;">
    <button onclick="window.location.href='listeTickets.php';">Actualiser</button>
    <?php if (!$isTuteur): ?>
        <button onclick="window.location.href='ticket.php';">Nouveau ticket</button>
    <?php endif; ?>
</div>

<div style="margin-bottom: 10px;">
    <form method="get" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">

        <div>
            <label for="categorie"><strong>Catégorie :</strong></label><br>
            <select name="categorie" id="categorie">
                <option value="">(toutes)</option>
                <option value="Cours"  <?= $filtreCategorie === 'Cours' ? 'selected' : '' ?>>Cours</option>
                <option value="TD"     <?= $filtreCategorie === 'TD' ? 'selected' : '' ?>>TD</option>
                <option value="TP"     <?= $filtreCategorie === 'TP' ? 'selected' : '' ?>>TP</option>
            </select>
        </div>

        <div>
            <label for="priorite"><strong>Priorité :</strong></label><br>
            <select name="priorite" id="priorite">
                <option value="">(toutes)</option>
                <option value="Basse"   <?= $filtrePriorite === 'Basse' ? 'selected' : '' ?>>Basse</option>
                <option value="Moyenne" <?= $filtrePriorite === 'Moyenne' ? 'selected' : '' ?>>Moyenne</option>
                <option value="Haute"   <?= $filtrePriorite === 'Haute' ? 'selected' : '' ?>>Haute</option>
            </select>
        </div>

        <div>
            <label for="statut"><strong>Statut :</strong></label><br>
            <select name="statut" id="statut">
                <option value="">(tous)</option>
                <option value="Ouvert"     <?= $filtreStatut === 'Ouvert' ? 'selected' : '' ?>>Ouvert</option>
                <option value="En cours"   <?= $filtreStatut === 'En cours' ? 'selected' : '' ?>>En cours</option>
                <option value="Résolu"     <?= $filtreStatut === 'Résolu' ? 'selected' : '' ?>>Résolu</option>
            </select>
        </div>

        <div>
            <button type="submit">Filtrer</button>
            <a href="listeTickets.php" style="margin-left:10px;">Réinitialiser</a>
        </div>
    </form>
</div>

<div id="listetickets">
    <?php
    $found = false;

    foreach ($tickets as $t) {

        // Appliquer les filtres
        if ($filtreCategorie !== '' && $t['category'] !== $filtreCategorie) {
            continue;
        }
        if ($filtrePriorite !== '' && $t['priority'] !== $filtrePriorite) {
            continue;
        }
        if ($filtreStatut !== '' && $t['status'] !== $filtreStatut) {
            continue;
        }

        // Si ce n'est pas un tuteur, ne montrer que ses tickets
        if (!$isTuteur && $t['author'] !== $currentUser) {
            continue;
        }

        $found = true;
        ?>
        <div class="ticket">
            <?php if ($isTuteur): ?>
                <p>
                    <strong>Étudiant :</strong>
                    <?= htmlspecialchars($t['author'], ENT_QUOTES | ENT_SUBSTITUTE); ?>
                </p>
            <?php endif; ?>

            <p>
                <strong>Titre :</strong>
                <?= htmlspecialchars($t['title'], ENT_QUOTES | ENT_SUBSTITUTE); ?>
            </p>

            <p>
                <strong>Description :</strong>
                <?= nl2br(htmlspecialchars($t['description'], ENT_QUOTES | ENT_SUBSTITUTE)); ?>
            </p>

            <p><strong>Catégorie :</strong>
                <?= htmlspecialchars($t['category'], ENT_QUOTES | ENT_SUBSTITUTE); ?></p>
            <p><strong>Priorité :</strong>
                <?= htmlspecialchars($t['priority'], ENT_QUOTES | ENT_SUBSTITUTE); ?></p>
            <p><strong>Statut :</strong>
                <?= htmlspecialchars($t['status'], ENT_QUOTES | ENT_SUBSTITUTE); ?></p>

            <button onclick="window.location.href='afficheTicket.php?id=<?= urlencode($t['id']) ?>';">
                Voir le ticket
            </button>

            <p><small>Créé le
                    <?= htmlspecialchars($t['created_at'], ENT_QUOTES | ENT_SUBSTITUTE); ?></small></p>
        </div>
        <?php
    }

    if (!$found) {
        echo "<p>Aucun ticket à afficher.</p>";
    }
    ?>
</div>

</body>
</html>
