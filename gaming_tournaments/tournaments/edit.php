<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireAdmin();

if (!isset($_GET['id'])) {
    header('Location: ../dashboard.php');
    exit;
}

$tournament = getTournamentById($_GET['id']);
if (!$tournament) {
    header('Location: ../dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (updateTournament($_GET['id'], $_POST)) {
        $success = 'Torneo actualizado correctamente';
        $tournament = getTournamentById($_GET['id']);
    } else {
        $error = 'Error al actualizar el torneo';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Torneo - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/forms.css">
</head>
<body>
    <div class="form-container">
        <h2 class="form-title">Editar Torneo</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Nombre del Torneo</label>
                <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($tournament['name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="game">Juego</label>
                <input type="text" id="game" name="game" class="form-control" value="<?php echo htmlspecialchars($tournament['game']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="description">Descripción</label>
                <textarea id="description" name="description" class="form-control" required><?php echo htmlspecialchars($tournament['description']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="start_date">Fecha de Inicio</label>
                <input type="datetime-local" id="start_date" name="start_date" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($tournament['start_date'])); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="end_date">Fecha de Fin</label>
                <input type="datetime-local" id="end_date" name="end_date" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($tournament['end_date'])); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="max_participants">Máximo de Participantes</label>
                <input type="number" id="max_participants" name="max_participants" class="form-control" value="<?php echo $tournament['max_participants']; ?>" min="2" max="64" required>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Actualizar</button>
                <a href="view.php?id=<?php echo $tournament['id']; ?>" class="btn">Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>