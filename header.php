<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['username'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

$currentUser = $_SESSION['username'];
$isTuteur    = $_SESSION['is_tuteur'] ?? false;
?>
<header class="top-bar">
    <h1><?= $pageTitle ?? 'Espace personnel' ?></h1>

    <div class="profile-wrapper">
        <button type="button" class="profile-button" id="profileToggle">
            <span class="profile-avatar">
                <?= strtoupper(substr($currentUser, 0, 1)) ?>
            </span>
            <span class="profile-name">
                <?= htmlspecialchars($currentUser, ENT_QUOTES | ENT_SUBSTITUTE) ?>
            </span>
        </button>
        <div class="profile-menu" id="profileMenu">
            <button type="button" onclick="window.location.href='modifMDP.php'">
                Modifier mon mot de passe
            </button>
            <button type="button"
                    onclick="window.location.href='logout.php?from=<?= urlencode($_SERVER['REQUEST_URI']) ?>'">
                Se déconnecter
            </button>
        </div>
    </div>
</header>

<p class="user-info">
    Connecté en tant que
    <strong><?= htmlspecialchars($currentUser, ENT_QUOTES | ENT_SUBSTITUTE) ?></strong>
    <?= $isTuteur ? '— tuteur' : '— étudiant' ?>
</p>

<script>
    const profileToggle = document.getElementById('profileToggle');
    const profileMenu   = document.getElementById('profileMenu');

    if (profileToggle && profileMenu) {
        profileToggle.addEventListener('click', () => {
            profileMenu.classList.toggle('profile-menu-open');
        });

        document.addEventListener('click', (e) => {
            if (!profileMenu.contains(e.target) && !profileToggle.contains(e.target)) {
                profileMenu.classList.remove('profile-menu-open');
            }
        });
    }
</script>
