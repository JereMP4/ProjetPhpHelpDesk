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
<html>
<head>
    <meta charset="utf-8">
    <title>Authentification</title>
</head>
<body>

<h1>AUTHENTIFICATION</h1>

<?php if (!empty($error)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE); ?></p>
<?php endif; ?>

<form method="POST">
    <label>Nom d'utilisateur</label>
    <input type="text" name="nom" required
           value="<?php echo htmlspecialchars($_POST['nom'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE); ?>"><br><br>

    <label>Mot de passe</label>
    <input type="password" name="password" required><br><br>

    <input type="submit" value="Valider">
</form>

</body>
</html>
