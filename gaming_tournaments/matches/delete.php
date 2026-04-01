<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireAdmin();

if (!isset($_GET['id'])) {
    header('Location: ../dashboard.php');
    exit;
}

$match_id = $_GET['id'];

// Obtener tournament_id para redirigir
global $pdo;
$stmt = $pdo->prepare("SELECT tournament_id FROM matches WHERE id = ?");
$stmt->execute([$match_id]);
$match = $stmt->fetch();

if (!$match) {
    header('Location: ../dashboard.php');
    exit;
}

$tournament_id = $match['tournament_id'];

if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
    $stmt = $pdo->prepare("DELETE FROM matches WHERE id = ?");
    if ($stmt->execute([$match_id])) {
        header('Location: ../tournaments/view.php?id=' . $tournament_id . '&msg=partido_eliminado');
        exit;
    }
}

// Mostrar confirmación
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar Partido - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/forms.css">
</head>
<body>
    <div class="form-container">
        <h2 class="form-title">Confirmar Eliminación</h2>
        
        <div class="alert alert-error" style="text-align: center;">
            <p>¿Estás seguro de que quieres eliminar este partido?</p>
            <p>Esta acción no se puede deshacer.</p>
        </div>
        
        <div class="form-actions" style="justify-content: center;">
            <a href="delete.php?id=<?php echo $match_id; ?>&confirm=yes" class="btn btn-danger">Sí, Eliminar</a>
            <a href="../tournaments/view.php?id=<?php echo $tournament_id; ?>" class="btn">Cancelar</a>
        </div>
    </div>
</body>
</html>