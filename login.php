<?php
session_start();

require_once __DIR__ . '/db.php';

$error = "";

// Page de retour prioritaire : ?from=... puis éventuelle redirect_after_login
$from = $_GET['from'] ?? ($_SESSION['redirect_after_login'] ?? null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($nom === '' || $password === '') {
        $error = "Tous les champs sont obligatoires.";
    } else {
        // Requête préparée pour récupérer l'utilisateur
        $stmt = $pdo->prepare('SELECT id, username, password_hash, role 
                               FROM users 
                               WHERE username = :username');
        $stmt->execute([':username' => $nom]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Login OK
            session_regenerate_id(true);

            $_SESSION['userid']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['role']      = $user['role'] ?? 'etudiant';
            $_SESSION['loggedin']  = true;
            $_SESSION['is_tuteur'] = ($_SESSION['role'] === 'tuteur');

            if (!empty($from)) {
                unset($_SESSION['redirect_after_login']);
                // Sécurité : on rejette toute URL externe (avec un host)
                $parsed = parse_url($from);
                if ($parsed === false || isset($parsed['host'])) {
                    header('Location: listeTickets.php');
                } else {
                    header('Location: ' . $from);
                }
            } else {
                header('Location: listeTickets.php');
            }

            exit;
        } else {
            $error = "Nom d'utilisateur ou mot de passe incorrect.";
        }
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
                <?php echo htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
            </p>
        <?php endif; ?>

        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="nom">Nom d'utilisateur</label>
                <input id="nom" type="text" name="nom" required
                       value="<?php echo htmlspecialchars($_POST['nom'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
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
