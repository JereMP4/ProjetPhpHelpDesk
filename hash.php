<?php
$mdp = 'maxence'; // le mot de passe que tu veux hasher
echo password_hash($mdp, PASSWORD_DEFAULT);
?>