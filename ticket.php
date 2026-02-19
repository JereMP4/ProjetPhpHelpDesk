<?php
// Page: ticket.php
// Permet à un étudiant connecté de créer un ticket (enregistré dans tickets.json)

session_start();

// --- Vérification connexion / rôle ---
// Si pas connecté : proposer le bouton de connexion et mémoriser la page
if (empty($_SESSION['username'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    ?>
    <!doctype html>
    <html lang="fr">
    <head>
        <meta charset="utf-8">
        <title>Créer un ticket</title>
    </head>
    <body>
    <p>Accès réservé aux étudiants. Veuillez vous connecter avec un compte étudiant.</p>
    <button onclick="window.location.href='login.php';">Se connecter</button>
    </body>
    </html>
    <?php
    exit;
}

// Si c'est un tuteur, on bloque la création de ticket étudiant
$isTuteur = !empty($_SESSION['is_tuteur']) && $_SESSION['is_tuteur'];
if ($isTuteur) {
    http_response_code(403);
    ?>
    <!doctype html>
    <html lang="fr">
    <head>
        <meta charset="utf-8">
        <title>Créer un ticket</title>
    </head>
    <body>
    <p>Les tuteurs ne peuvent pas créer de tickets étudiants.</p>
    <button onclick="window.location.href='listeTickets.php';">Retour à la liste des tickets</button>
    </body>
    </html>
    <?php
    exit;
}

$username = $_SESSION['username'];

// --- CSRF simple ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrf_token = $_SESSION['csrf_token'];

// Valeurs autorisées
$categories = ['Cours', 'TD', 'TP'];
$priorities = ['Basse', 'Moyenne', 'Haute'];
$statuses   = ['Ouvert', 'En cours', 'Résolu'];

// Traitement du formulaire
$errors  = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // vérification CSRF
    if (empty($_POST['csrf_token']) || !hash_equals($csrf_token, $_POST['csrf_token'])) {
        $errors[] = 'Jeton CSRF invalide.';
    }

    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category    = $_POST['category'] ?? '';
    $priority    = $_POST['priority'] ?? '';
    $status      = $_POST['status'] ?? 'Ouvert';

    if ($title === '')        $errors[] = 'Le titre est requis.';
    if ($description === '')  $errors[] = 'La description est requise.';
    if (!in_array($category, $categories, true)) $errors[] = 'Catégorie invalide.';
    if (!in_array($priority, $priorities, true)) $errors[] = 'Priorité invalide.';
    if (!in_array($status, $statuses, true))     $errors[] = 'Statut invalide.';

    if (empty($errors)) {
        // génération d'un identifiant unique sécurisé
        $id         = 'ticket_' . bin2hex(random_bytes(8));
        $author     = $username;
        $created_at = (new DateTime())->format('Y-m-d H:i:s');

        $ticket = [
                'id'         => $id,
                'author'     => $author,
                'title'      => $title,
                'description'=> $description,
                'category'   => $category,
                'priority'   => $priority,
                'status'     => $status,
                'created_at' => $created_at,
        ];

        // emplacement de stockage (fichier JSON)
        $file = __DIR__ . '/tickets.json';
        $all  = [];

        if (file_exists($file)) {
            $json    = file_get_contents($file);
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                $all = $decoded;
            }
        }

        $all[] = $ticket;

        $ok = file_put_contents(
                $file,
                json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                LOCK_EX
        );

        if ($ok === false) {
            $errors[] = 'Impossible d\'enregistrer le ticket.';
        } else {
            $success = "Ticket créé avec succès (ID: $id).";
            // réinitialiser le token pour éviter resoumission
            $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
            $csrf_token             = $_SESSION['csrf_token'];

            // petite pause puis redirection vers la liste
            sleep(1);
            header('Location: listeTickets.php');
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Créer un ticket</title>
    <style>
        body { font-family: Arial, sans-serif; max-width:800px; margin:2rem auto; padding:0 1rem; }
        form { display:flex; flex-direction:column; gap:.6rem; }
        label { font-weight:600; }
        textarea { min-height:120px; }
        .errors { color:#900; }
        .success { color:#060; }
        .top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; }
    </style>
</head>
<body>
<div class="top-bar">
    <h1>Créer un ticket</h1>
    <button type="button"
            onclick="window.location.href='logout.php?from=<?= urlencode($_SERVER['REQUEST_URI']) ?>';">
        Se déconnecter
    </button>
</div>

<p>Connecté en tant que :
    <strong><?php echo htmlspecialchars($username, ENT_QUOTES | ENT_SUBSTITUTE); ?></strong>
</p>

<p>
    <button type="button" onclick="window.location.href='listeTickets.php';">
        Retour à mes tickets
    </button>
</p>

<?php if ($errors): ?>
    <div class="errors">
        <ul>
            <?php foreach ($errors as $e): ?>
                <li><?php echo htmlspecialchars($e, ENT_QUOTES | ENT_SUBSTITUTE); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="success">
        <?php echo htmlspecialchars($success, ENT_QUOTES | ENT_SUBSTITUTE); ?>
    </div>
<?php endif; ?>

<form method="post" action="">
    <input type="hidden" name="csrf_token"
           value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES | ENT_SUBSTITUTE); ?>">

    <label for="title">Titre *</label>
    <input id="title" name="title" required
           value="<?php echo htmlspecialchars($_POST['title'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE); ?>">

    <label for="description">Description *</label>
    <textarea id="description" name="description" required><?php
        echo htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE);
        ?></textarea>

    <label for="category">Catégorie *</label>
    <select id="category" name="category" required>
        <?php foreach ($categories as $c): ?>
            <option value="<?php echo htmlspecialchars($c, ENT_QUOTES | ENT_SUBSTITUTE); ?>"
                    <?php echo (($_POST['category'] ?? '') === $c) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($c, ENT_QUOTES | ENT_SUBSTITUTE); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="priority">Priorité *</label>
    <select id="priority" name="priority" required>
        <?php foreach ($priorities as $p): ?>
            <option value="<?php echo htmlspecialchars($p, ENT_QUOTES | ENT_SUBSTITUTE); ?>"
                    <?php echo (($_POST['priority'] ?? '') === $p) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($p, ENT_QUOTES | ENT_SUBSTITUTE); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="status">Statut</label>
    <select id="status" name="status">
        <?php foreach ($statuses as $s): ?>
            <option value="<?php echo htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE); ?>"
                    <?php echo (($_POST['status'] ?? 'Ouvert') === $s) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Créer le ticket</button>
</form>
</body>
</html>
