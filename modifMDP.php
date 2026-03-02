<?php
session_start();

if (empty($_SESSION['username'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

$currentUser = $_SESSION['username'];

$errors  = [];
$success = null;

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrf_token = $_SESSION['csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !hash_equals($csrf_token, $_POST['csrf_token'])) {
        $errors[] = 'Jeton CSRF invalide.';
    }

    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirm     = $_POST['confirm_password'] ?? '';

    if ($oldPassword === '' || $newPassword === '' || $confirm === '') {
        $errors[] = 'Tous les champs sont obligatoires.';
    }
    if ($newPassword !== $confirm) {
        $errors[] = 'La confirmation ne correspond pas au nouveau mot de passe.';
    }

    $usersFile = __DIR__ . '/users.json';
    $users     = [];

    if (empty($errors)) {
        if (!file_exists($usersFile)) {
            $errors[] = 'Fichier utilisateurs introuvable.';
        } else {
            $json    = file_get_contents($usersFile);
            $decoded = json_decode($json, true);
            if (!is_array($decoded)) {
                $errors[] = 'Format de users.json invalide.';
            } else {
                $users = $decoded;
            }
        }
    }

    if (empty($errors)) {
        $found = false;

        foreach ($users as &$user) {
            if ($user['username'] === $currentUser) {
                // Comparaison en clair (comme dans login.php)
                if ($user['password'] !== $oldPassword) {
                    $errors[] = 'Ancien mot de passe incorrect.';
                } else {
                    $user['password'] = $newPassword;
                    $found = true;
                }
                break;
            }
        }
        unset($user);

        if (empty($errors)) {
            if (!$found) {
                $errors[] = 'Utilisateur introuvable dans users.json.';
            } else {
                $ok = file_put_contents(
                    $usersFile,
                    json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                    LOCK_EX
                );
                if ($ok === false) {
                    $errors[] = 'Impossible de mettre à jour le mot de passe.';
                } else {
                    $success = 'Mot de passe modifié avec succès.';
                    // éviter resoumission
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
                    $csrf_token             = $_SESSION['csrf_token'];
                }
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
    <link rel="stylesheet" href="style/modifMDP.css">
</head>
<body>
<div class="page-wrapper">
    <header class="top-bar">
        <h1>Modifier mon mot de passe</h1>
        <button class="btn btn-outline"
                onclick="window.location.href='logout.php?from=<?= urlencode($_SERVER['REQUEST_URI']) ?>';">
            Se déconnecter
        </button>
    </header>

    <p class="user-info">
        Connecté en tant que :
        <strong><?= htmlspecialchars($currentUser, ENT_QUOTES | ENT_SUBSTITUTE); ?></strong>
    </p>

    <p class="back-link">
        <button class="btn btn-secondary"
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
