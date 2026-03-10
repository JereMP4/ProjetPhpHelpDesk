<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Accueil</title>
    <link rel="stylesheet" href="style/index.css">
</head>
<body>
<div class="home-page">
    <div class="home-hero">
        <span class="badge">Support L3 Info</span>
        <h1 class="home-title">HelpDesk</h1>
        <p class="home-subtitle">
            Pose tes questions de cours, TD ou TP et suis l'avancement de tes demandes
            en temps réel avec les tuteurs.
        </p>

        <div class="home-actions">
            <?php if (!empty($_SESSION['username'])): ?>
                <button class="btn btn-primary"
                        onclick="window.location.href='listeTickets.php';">
                    Aller sur mon espace
                </button>
            <?php else: ?>
                <button class="btn btn-primary"
                        onclick="window.location.href='login.php?from=listeTickets.php';">
                    Aller sur mon espace
                </button>
            <?php endif; ?>
        </div>

        <div class="home-meta">
            <span class="chip">Création de tickets</span>
            <span class="chip">Suivi des réponses</span>
            <span class="chip">Suivi personalisé</span>
        </div>
    </div>
</div>
</body>
</html>
