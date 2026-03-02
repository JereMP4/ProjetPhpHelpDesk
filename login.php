<?php
session_start();

$error = "";

// Page de retour prioritaire : ?from=... puis éventuelle redirect_after_login
$from = $_GET['from'] ?? ($_SESSION['redirect_after_login'] ?? null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $password = $_POST['password'] ?? '';

    // Charger les utilisateurs depuis users.json
    $usersFile = __DIR__ . '/users.json';
    $users = [];

    if (file_exists($usersFile)) {
        $json = file_get_contents($usersFile);
        $decoded = json_decode($json, true);
        if (is_array($decoded)) {
            $users = $decoded;
        }
    }

    $found = false;

    foreach ($users as $user) {
        // Ici on compare en clair (username + password)
        if ($user['username'] === $nom && $user['password'] === $password) {
            $found = true;

            session_regenerate_id(true);

            $_SESSION['username']  = $user['username'];
            $_SESSION['role']      = $user['role'] ?? 'student';
            $_SESSION['loggedin']  = true;
            // flag tuteur pour les permissions
            $_SESSION['is_tuteur'] = ($_SESSION['role'] === 'tuteur');

            if (!empty($from)) {
                unset($_SESSION['redirect_after_login']);
                header("Location: $from");
            } else {
                header('Location: ticket.php');
            }
            exit;
        }
    }

    if (!$found) {
        $error = "Nom d'utilisateur ou mot de passe incorrect.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Authentification</title>
    <link href="style/login.css" rel="stylesheet">
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <h1 class="login-title">AUTHENTIFICATION</h1>

        <?php if (!empty($error)): ?>
            <p class="error-message">
                <?php echo htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE); ?>
            </p>
        <?php endif; ?>

        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="nom">Nom d'utilisateur</label>
                <input id="nom" type="text" name="nom" required
                       value="<?php echo htmlspecialchars($_POST['nom'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE); ?>">
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input id="password" type="password" name="password" required>
            </div>

            <div class="form-actions">
                <input type="submit" value="Valider">
            </div>
        </form>
    </div>
</div>
</body>
</html>
