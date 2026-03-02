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
        <link rel="stylesheet" href="style/listeTickets.css">
    </head>
    <body>
    <div class="page-wrapper">
        <p class="user-info">
            Accès réservé aux étudiants et tuteurs. Veuillez vous connecter.
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
    <link rel="stylesheet" href="style/listeTickets.css">
</head>
<body>
<div class="page-wrapper">

    <div class="top-bar">
        <h1>Espace Personnel</h1>

        <div class="profile-wrapper">
            <button type="button" class="profile-button" id="profileToggle">
                <span class="profile-avatar">
                    <?= strtoupper(substr($currentUser, 0, 1)); ?>
                </span>
                <span class="profile-name">
                    <?= htmlspecialchars($currentUser, ENT_QUOTES | ENT_SUBSTITUTE); ?>
                </span>
            </button>

            <div class="profile-menu" id="profileMenu">
                <button type="button"
                        onclick="window.location.href='modifMDP.php';">
                    Modifier mon mot de passe
                </button>
                <button type="button"
                        onclick="window.location.href='logout.php?from=<?= urlencode($_SERVER['REQUEST_URI']) ?>';">
                    Se déconnecter
                </button>
            </div>
        </div>
    </div>

    <p class="user-info">
        Connecté en tant que :
        <strong><?= htmlspecialchars($currentUser, ENT_QUOTES | ENT_SUBSTITUTE); ?></strong>
        <?php if ($isTuteur): ?>
            (tuteur)
        <?php else: ?>
            (étudiant)
        <?php endif; ?>
    </p>

    <hr>

    <div class="actions-bar">
        <button class="btn btn-secondary"
                onclick="window.location.href='listeTickets.php';">
            Actualiser
        </button>
        <?php if (!$isTuteur): ?>
            <button class="btn btn-primary"
                    onclick="window.location.href='ticket.php';">
                Nouveau ticket
            </button>
        <?php endif; ?>
    </div>

    <div class="filters-wrapper">
        <form method="get" class="filters-form">

            <div class="filter-group">
                <label for="categorie">Catégorie</label>
                <select name="categorie" id="categorie">
                    <option value="">(toutes)</option>
                    <option value="Cours"  <?= $filtreCategorie === 'Cours' ? 'selected' : '' ?>>Cours</option>
                    <option value="TD"     <?= $filtreCategorie === 'TD' ? 'selected' : '' ?>>TD</option>
                    <option value="TP"     <?= $filtreCategorie === 'TP' ? 'selected' : '' ?>>TP</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="priorite">Priorité</label>
                <select name="priorite" id="priorite">
                    <option value="">(toutes)</option>
                    <option value="Basse"   <?= $filtrePriorite === 'Basse' ? 'selected' : '' ?>>Basse</option>
                    <option value="Moyenne" <?= $filtrePriorite === 'Moyenne' ? 'selected' : '' ?>>Moyenne</option>
                    <option value="Haute"   <?= $filtrePriorite === 'Haute' ? 'selected' : '' ?>>Haute</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="statut">Statut</label>
                <select name="statut" id="statut">
                    <option value="">(tous)</option>
                    <option value="Ouvert"     <?= $filtreStatut === 'Ouvert' ? 'selected' : '' ?>>Ouvert</option>
                    <option value="En cours"   <?= $filtreStatut === 'En cours' ? 'selected' : '' ?>>En cours</option>
                    <option value="Résolu"     <?= $filtreStatut === 'Résolu' ? 'selected' : '' ?>>Résolu</option>
                </select>
            </div>

            <div class="filters-actions">
                <button type="submit" class="btn btn-primary">Filtrer</button>
                <a href="listeTickets.php">Réinitialiser</a>
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
                <h3>
                    <strong>Titre :</strong>
                    <?= htmlspecialchars($t['title'], ENT_QUOTES | ENT_SUBSTITUTE); ?>
                </h3>

                <p>
                    <em><?= htmlspecialchars($t['category'], ENT_QUOTES | ENT_SUBSTITUTE); ?></em> |
                    <em><?= htmlspecialchars($t['priority'], ENT_QUOTES | ENT_SUBSTITUTE); ?></em> |
                    <em><?= htmlspecialchars($t['status'], ENT_QUOTES | ENT_SUBSTITUTE); ?></em> |
                    <em>Créé le
                        <?= htmlspecialchars($t['created_at'], ENT_QUOTES | ENT_SUBSTITUTE); ?></em>
                    <?php if ($isTuteur): ?>
                        par <em><?= htmlspecialchars($t['author'], ENT_QUOTES | ENT_SUBSTITUTE); ?></em>
                    <?php endif; ?>
                </p>

                <button class="btn btn-outline"
                        onclick="window.location.href='afficheTicket.php?id=<?= urlencode($t['id']) ?>';">
                    Voir le ticket
                </button>
            </div>
            <?php
        }

        if (!$found) {
            echo '<p class="user-info">Aucun ticket à afficher.</p>';
        }
        ?>
    </div>

</div> <!-- .page-wrapper -->

<script>
    const profileToggle = document.getElementById('profileToggle');
    const profileMenu   = document.getElementById('profileMenu');

    if (profileToggle && profileMenu) {
        profileToggle.addEventListener('click', () => {
            profileMenu.classList.toggle('profile-menu-open');
        });

        document.addEventListener('click', (e) => {
            if (!profileMenu.contains(e.target) && !profileToggle.contains(e.target)) {
                profileMenu.classList.remove('profile-menu-open');
            }
        });
    }
</script>
</body>
</html>
