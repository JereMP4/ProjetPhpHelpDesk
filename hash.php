<?php
// hash.php
session_start();

$access_password = 'secretpage'; // à changer si tu gardes cette protection
if (!isset($_SESSION['access_ok'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['access'])) {
        if ($_POST['access'] === $access_password) {
            $_SESSION['access_ok'] = true;
        } else {
            $access_error = "Mot de passe d'accès incorrect.";
        }
    }

    if (empty($_SESSION['access_ok'])) {
        ?>
        <!doctype html>
        <html lang="fr">
        <head>
            <meta charset="utf-8">
            <title>Accès générateur de hash</title>
            <link rel="stylesheet" href="style/hash.css">
        </head>
        <body>
        <div class="login-page">
            <div class="login-card">
                <h1 class="login-title">ACCÈS HASH</h1>

                <?php if (!empty($access_error)): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($access_error, ENT_QUOTES | ENT_SUBSTITUTE); // [web:19] ?>
                    </div>
                <?php endif; ?>

                <form method="post" class="login-form">
                    <div class="form-group">
                        <label for="access">Mot de passe d'accès</label>
                        <input type="password" id="access" name="access" required>
                    </div>

                    <div class="form-actions">
                        <input type="submit" value="Entrer">
                    </div>
                </form>
            </div>
        </div>
        </body>
        </html>
        <?php
        exit;
    }
}

$hash = null;
$password_input = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password_to_hash'])) {
    $password_input = $_POST['password_to_hash'];
    if ($password_input !== '') {
        $hash = password_hash($password_input, PASSWORD_DEFAULT); // [web:7][web:10]
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Générateur de hash de mot de passe</title>
    <link rel="stylesheet" href="style/hash.css">
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <h1 class="login-title">HASH PASSWORD</h1>

        <form method="post" class="login-form">
            <div class="form-group">
                <label for="password_to_hash">Mot de passe à hasher</label>
                <input
                    type="text"
                    id="password_to_hash"
                    name="password_to_hash"
                    value="<?php echo htmlspecialchars($password_input, ENT_QUOTES | ENT_SUBSTITUTE); // [web:19] ?>"
                    placeholder="Mot de passe en clair"
                    required
                >
            </div>

            <div class="form-actions">
                <input type="submit" value="Générer le hash">
            </div>
            <div class="form-actions">
                <button type="button" class="back-button" onclick="window.location.href='index.php'">
                    Retour à l'index
                </button>
            </div>
        </form>

        <?php if ($hash !== null): ?>
            <div class="form-group" style="margin-top: 16px;">
                <label>Hash généré</label>
                <div class="result">
                    <?php echo htmlspecialchars($hash, ENT_QUOTES | ENT_SUBSTITUTE); // [web:19] ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
