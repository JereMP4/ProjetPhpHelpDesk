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

$currentUser   = $currentUserData['username'];
$currentUserId = $_SESSION['userid'];

$errors  = [];
$success = null;

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrf_token = $_SESSION['csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !hash_equals($csrf_token, $_POST['csrf_token'])) {
        $errors[] = 'Jeton CSRF invalide.';
    }

    $oldPassword = $_POST['old_password']     ?? '';
    $newPassword = $_POST['new_password']     ?? '';
    $confirm     = $_POST['confirm_password'] ?? '';

    if ($oldPassword === '' || $newPassword === '' || $confirm === '') {
        $errors[] = 'Tous les champs sont obligatoires.';
    } elseif ($newPassword !== $confirm) {
        $errors[] = 'La confirmation ne correspond pas au nouveau mot de passe.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id');
        $stmt->execute([':id' => $currentUserId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $errors[] = 'Utilisateur introuvable.';
        } elseif (!password_verify($oldPassword, $user['password_hash'])) {
            $errors[] = 'Ancien mot de passe incorrect.';
        } else {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt    = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
            $ok      = $stmt->execute([
                    ':hash' => $newHash,
                    ':id'   => $currentUserId,
            ]);

            if (!$ok) {
                $errors[] = 'Impossible de mettre à jour le mot de passe.';
            } else {
                $success = 'Mot de passe modifié avec succès.';
                $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
                $csrf_token             = $_SESSION['csrf_token'];
            }
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Modifier mon mot de passe</title>
    <link href="style/modifMDP.css" rel="stylesheet">
</head>
<body>
<div class="page-wrapper">

    <?php
    $pageTitle = 'Modifier mon mot de passe';
    include __DIR__ . '/header.php';
    ?>

    <p class="back-link">
        <button class="btn btn-secondary"
                type="button"
                onclick="window.location.href='listeTickets.php';">
            Retour à mon espace
        </button>
    </p>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e, ENT_QUOTES | ENT_SUBSTITUTE); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success, ENT_QUOTES | ENT_SUBSTITUTE); ?>
        </div>
    <?php endif; ?>

    <form method="post" action="" class="password-form">
        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($csrf_token, ENT_QUOTES | ENT_SUBSTITUTE); ?>">

        <div class="form-group">
            <label for="old_password">Ancien mot de passe</label>
            <input id="old_password" name="old_password" type="password" required>
        </div>

        <div class="form-group">
            <label for="new_password">Nouveau mot de passe</label>
            <input id="new_password" name="new_password" type="password" required>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirmer le nouveau mot de passe</label>
            <input id="confirm_password" name="confirm_password" type="password" required>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Mettre à jour</button>
        </div>
    </form>
</div>
</body>
</html>
