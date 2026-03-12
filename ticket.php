<?php
/**
 * Page ticket.php
 * Permet à un étudiant connecté de créer un ticket enregistré en BDD.
 */
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

$username = $currentUserData['username'];
$isTuteur = $_SESSION['is_tuteur'];

if ($isTuteur) {
    http_response_code(403);
    ?>
    <!doctype html>
    <html lang="fr">
    <head>
        <meta charset="utf-8">
        <title>Créer un ticket</title>
        <link href="style/ticket.css" rel="stylesheet">
    </head>
    <body>
    <div class="page-wrapper">
        <p class="user-info">Les tuteurs ne peuvent pas créer de tickets étudiants.</p>
        <button class="btn btn-secondary" onclick="window.location.href='listeTickets.php'">Retour à la liste des tickets</button>
    </div>
    </body>
    </html>
    <?php
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrfToken = $_SESSION['csrf_token'];

$categories = ['Cours', 'TD', 'TP'];
$priorities = ['Basse', 'Moyenne', 'Haute'];
$statuses   = ['Ouvert', 'En cours', 'Résolu'];

$errors  = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_POST['csrf_token']) || !hash_equals($csrfToken, $_POST['csrf_token'])) {
        $errors[] = 'Jeton CSRF invalide.';
    }

    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $category    = $_POST['category']         ?? '';
    $priority    = $_POST['priority']         ?? '';
    $status = 'Ouvert';

    if (empty($title))                           $errors[] = 'Le titre est requis.';
    if (empty($description))                     $errors[] = 'La description est requise.';
    if (!in_array($category,  $categories, true)) $errors[] = 'Catégorie invalide.';
    if (!in_array($priority,  $priorities, true)) $errors[] = 'Priorité invalide.';
    if (!in_array($status,    $statuses,   true)) $errors[] = 'Statut invalide.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO tickets (author_id, title, description, category, priority, status, created_at)
            VALUES (:author_id, :title, :description, :category, :priority, :status, NOW())
        ");

        $ok = $stmt->execute([
                ':author_id'   => $_SESSION['userid'],
                ':title'       => $title,
                ':description' => $description,
                ':category'    => $category,
                ':priority'    => $priority,
                ':status'      => $status,
        ]);

        if (!$ok) {
            $errors[] = 'Impossible d\'enregistrer le ticket.';
        } else {
            $newId   = $pdo->lastInsertId();
            $success = "Ticket créé avec succès (ID : $newId).";

            $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
            $csrfToken = $_SESSION['csrf_token'];

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
    <link href="style/ticket.css" rel="stylesheet">
</head>
<body>
<div class="page-wrapper">

    <?php
    $pageTitle = 'Créer un ticket';
    include __DIR__ . '/header.php';
    ?>

    <p class="back-link">
        <button type="button" class="btn btn-secondary" onclick="window.location.href='listeTickets.php'">Retour à mes tickets</button>
    </p>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e, ENT_QUOTES | ENT_SUBSTITUTE) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success, ENT_QUOTES | ENT_SUBSTITUTE) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="" class="ticket-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES | ENT_SUBSTITUTE) ?>">

        <div class="form-group">
            <label for="title">Titre</label>
            <input id="title" name="title" type="text" required
                   value="<?= htmlspecialchars($_POST['title'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE) ?>">
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" required><?= htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE) ?></textarea>
        </div>

        <div class="form-row">

            <div class="form-group">
                <label for="category">Catégorie</label>
                <select id="category" name="category" required>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= htmlspecialchars($c, ENT_QUOTES | ENT_SUBSTITUTE) ?>"
                                <?= (($_POST['category'] ?? '') === $c) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c, ENT_QUOTES | ENT_SUBSTITUTE) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="priority">Priorité</label>
                <select id="priority" name="priority" required>
                    <?php foreach ($priorities as $p): ?>
                        <option value="<?= htmlspecialchars($p, ENT_QUOTES | ENT_SUBSTITUTE) ?>"
                                <?= (($_POST['priority'] ?? '') === $p) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p, ENT_QUOTES | ENT_SUBSTITUTE) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Créer le ticket</button>
        </div>

    </form>

</div>
</body>
</html>
