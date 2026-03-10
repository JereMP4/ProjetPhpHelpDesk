<?php
session_start();
require_once __DIR__ . '/db.php';

// --- Vérification du rôle directement en BDD (modèle PDO de login.php) ---
if (empty($_SESSION['username'])) {
    // On ne fait plus l'affichage HTML ici : le header.php gérera la redirection vers login.php
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

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

// --- Filtres ---
$filtreCategorie = $_GET['categorie'] ?? '';
$filtrePriorite  = $_GET['priorite']  ?? '';
$filtreStatut    = $_GET['statut']    ?? '';

// --- Construction de la requête avec filtres ---
$sql    = "SELECT t.*, u.username AS author FROM tickets t JOIN users u ON u.id = t.author_id WHERE 1=1";
$params = [];

// Si étudiant : seulement ses tickets
if (!$isTuteur) {
    $sql .= " AND t.author_id = :author_id";
    $params[':author_id'] = $_SESSION['userid'];
}

if ($filtreCategorie !== '' && $filtreCategorie !== 'toutes') {
    $sql .= " AND t.category = :category";
    $params[':category'] = $filtreCategorie;
}

if ($filtrePriorite !== '' && $filtrePriorite !== 'toutes') {
    $sql .= " AND t.priority = :priority";
    $params[':priority'] = $filtrePriorite;
}

if ($filtreStatut !== '' && $filtreStatut !== 'tous') {
    $sql .= " AND t.status = :status";
    $params[':status'] = $filtreStatut;
}

$sql .= " ORDER BY t.created_at DESC";

$stmt    = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Espace Personnel</title>
    <link href="style/listeTickets.css" rel="stylesheet">
</head>
<body>
<div class="page-wrapper">

    <?php
    // Header factorisé avec redirection si pas connecté
    $pageTitle = 'Espace Personnel';
    include __DIR__ . '/header.php';
    ?>

    <hr>

    <div class="actions-bar">
        <button class="btn btn-secondary" onclick="window.location.href='listeTickets.php'">Actualiser</button>
        <?php if (!$isTuteur): ?>
            <button class="btn btn-primary" onclick="window.location.href='ticket.php'">Nouveau ticket</button>
        <?php endif; ?>
    </div>

    <div class="filters-wrapper">
        <form method="get" class="filters-form">

            <div class="filter-group">
                <label for="categorie">Catégorie</label>
                <select name="categorie" id="categorie">
                    <option value="toutes">toutes</option>
                    <option value="Cours"   <?= $filtreCategorie === 'Cours' ? 'selected' : '' ?>>Cours</option>
                    <option value="TD"      <?= $filtreCategorie === 'TD'    ? 'selected' : '' ?>>TD</option>
                    <option value="TP"      <?= $filtreCategorie === 'TP'    ? 'selected' : '' ?>>TP</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="priorite">Priorité</label>
                <select name="priorite" id="priorite">
                    <option value="toutes">toutes</option>
                    <option value="Basse"   <?= $filtrePriorite === 'Basse'   ? 'selected' : '' ?>>Basse</option>
                    <option value="Moyenne" <?= $filtrePriorite === 'Moyenne' ? 'selected' : '' ?>>Moyenne</option>
                    <option value="Haute"   <?= $filtrePriorite === 'Haute'   ? 'selected' : '' ?>>Haute</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="statut">Statut</label>
                <select name="statut" id="statut">
                    <option value="tous">tous</option>
                    <option value="Ouvert"   <?= $filtreStatut === 'Ouvert'   ? 'selected' : '' ?>>Ouvert</option>
                    <option value="En cours" <?= $filtreStatut === 'En cours' ? 'selected' : '' ?>>En cours</option>
                    <option value="Résolu"   <?= $filtreStatut === 'Résolu'   ? 'selected' : '' ?>>Résolu</option>
                </select>
            </div>

            <div class="filters-actions">
                <button type="submit" class="btn btn-primary">Filtrer</button>
                <a href="listeTickets.php">Réinitialiser</a>
            </div>

        </form>
    </div>

    <div id="liste-tickets">
        <?php if (empty($tickets)): ?>
            <p class="user-info">Aucun ticket à afficher.</p>
        <?php else: ?>
            <?php foreach ($tickets as $t): ?>
                <div class="ticket">
                    <h3><strong>Titre : </strong><?= htmlspecialchars($t['title'], ENT_QUOTES | ENT_SUBSTITUTE) ?></h3>
                    <p>
                        <em><?= htmlspecialchars($t['category'], ENT_QUOTES | ENT_SUBSTITUTE) ?></em> —
                        <em><?= htmlspecialchars($t['priority'], ENT_QUOTES | ENT_SUBSTITUTE) ?></em> —
                        <em><?= htmlspecialchars($t['status'],   ENT_QUOTES | ENT_SUBSTITUTE) ?></em> —
                        <em>Créé le <?= htmlspecialchars($t['created_at'], ENT_QUOTES | ENT_SUBSTITUTE) ?></em>
                        <?php if ($isTuteur): ?>
                            par <em><?= htmlspecialchars($t['author'], ENT_QUOTES | ENT_SUBSTITUTE) ?></em>
                        <?php endif; ?>
                    </p>
                    <button class="btn btn-outline" onclick="window.location.href='afficheTicket.php?id=<?= urlencode($t['id']) ?>'">
                        Voir le ticket
                    </button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>
</body>
</html>
