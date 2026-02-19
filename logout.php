<?php
session_start();


// détruire la session
$_SESSION = [];
session_unset();
session_destroy();

// rediriger vers login avec info de retour
header('Location: index.php');
exit;
