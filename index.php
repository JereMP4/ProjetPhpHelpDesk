<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Accueil</title>
</head>
<body>

<h1>Accueil</h1>

<?php if (!empty($_SESSION['username'])): ?>
    <!-- Déjà connecté : aller directement sur la liste -->
    <button onclick="window.location.href='listeTickets.php';">
        Aller sur mon espace
    </button>
<?php else: ?>
    <!-- Pas connecté : passer par login, puis retour listeTickets -->
    <button onclick="window.location.href='login.php?from=listeTickets.php';">
        Aller sur mon espace
    </button>
<?php endif; ?>

</body>
</html>
