<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireAdmin();

if (isset($_GET['id']) && isset($_GET['confirm'])) {
    if (deleteTournament($_GET['id'])) {
        header('Location: ../dashboard.php?msg=torneo_eliminado');
        exit;
    }
}

header('Location: ../dashboard.php');
exit;
?>