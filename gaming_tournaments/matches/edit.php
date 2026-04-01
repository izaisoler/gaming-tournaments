<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireAdmin();

if (!isset($_GET['id'])) {
    header('Location: ../dashboard.php');
    exit;
}

$match_id = $_GET['id'];

// Obtener datos del partido
global $pdo;
$stmt = $pdo->prepare("SELECT m.*, t.name as tournament_name, t.id as tournament_id,
                       p1.username as player1_name, p2.username as player2_name,
                       w.username as winner_name
                       FROM matches m
                       JOIN tournaments t ON m.tournament_id = t.id
                       LEFT JOIN participants p1p ON m.participant1_id = p1p.id
                       LEFT JOIN users p1 ON p1p.user_id = p1.id
                       LEFT JOIN participants p2p ON m.participant2_id = p2p.id
                       LEFT JOIN users p2 ON p2p.user_id = p2.id
                       LEFT JOIN participants wp ON m.winner_id = wp.id
                       LEFT JOIN users w ON wp.user_id = w.id
                       WHERE m.id = ?");
$stmt->execute([$match_id]);
$match = $stmt->fetch();

if (!$match) {
    header('Location: ../dashboard.php');
    exit;
}

// Obtener participantes del torneo
$participants = getParticipantsByTournament($match['tournament_id']);
$activeParticipants = array_filter($participants, function($p) {
    return $p['status'] == 'confirmed' || $p['status'] == 'registered';
});

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $round = $_POST['round'];
    $participant1_id = $_POST['participant1_id'];
    $participant2_id = $_POST['participant2_id'];
    $winner_id = $_POST['winner_id'] ?: null;
    $score1 = $_POST['score1'] ?: 0;
    $score2 = $_POST['score2'] ?: 0;
    $match_date = $_POST['match_date'] ?: null;
    $status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE matches 
                           SET round = ?, participant1_id = ?, participant2_id = ?, winner_id = ?,
                               score1 = ?, score2 = ?, match_date = ?, status = ?
                           WHERE id = ?");
    
    if ($stmt->execute([$round, $participant1_id, $participant2_id, $winner_id, $score1, $score2, $match_date, $status, $match_id])) {
        $success = 'Partido actualizado correctamente';
        // Recargar datos
        $stmt = $pdo->prepare("SELECT m.*, t.name as tournament_name, t.id as tournament_id,
                               p1.username as player1_name, p2.username as player2_name,
                               w.username as winner_name
                               FROM matches m
                               JOIN tournaments t ON m.tournament_id = t.id
                               LEFT JOIN participants p1p ON m.participant1_id = p1p.id
                               LEFT JOIN users p1 ON p1p.user_id = p1.id
                               LEFT JOIN participants p2p ON m.participant2_id = p2p.id
                               LEFT JOIN users p2 ON p2p.user_id = p2.id
                               LEFT JOIN participants wp ON m.winner_id = wp.id
                               LEFT JOIN users w ON wp.user_id = w.id
                               WHERE m.id = ?");
        $stmt->execute([$match_id]);
        $match = $stmt->fetch();
    } else {
        $error = 'Error al actualizar el partido';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Partido - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/forms.css">
</head>
<body>
    <div class="form-container">
        <h2 class="form-title">Editar Partido</h2>
        <h3 style="text-align: center; margin-bottom: 20px;"><?php echo htmlspecialchars($match['tournament_name']); ?></h3>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="round">Ronda</label>
                <input type="number" id="round" name="round" class="form-control" value="<?php echo $match['round']; ?>" min="1" required>
            </div>
            
            <div class="form-group">
                <label for="participant1_id">Participante 1</label>
                <select id="participant1_id" name="participant1_id" class="form-control" required>
                    <option value="">Seleccionar participante</option>
                    <?php foreach ($activeParticipants as $participant): ?>
                        <option value="<?php echo $participant['id']; ?>" <?php echo $match['participant1_id'] == $participant['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($participant['username']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="participant2_id">Participante 2</label>
                <select id="participant2_id" name="participant2_id" class="form-control" required>
                    <option value="">Seleccionar participante</option>
                    <?php foreach ($activeParticipants as $participant): ?>
                        <option value="<?php echo $participant['id']; ?>" <?php echo $match['participant2_id'] == $participant['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($participant['username']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="score1">Puntuación Participante 1</label>
                <input type="number" id="score1" name="score1" class="form-control" value="<?php echo $match['score1']; ?>" min="0">
            </div>
            
            <div class="form-group">
                <label for="score2">Puntuación Participante 2</label>
                <input type="number" id="score2" name="score2" class="form-control" value="<?php echo $match['score2']; ?>" min="0">
            </div>
            
            <div class="form-group">
                <label for="winner_id">Ganador</label>
                <select id="winner_id" name="winner_id" class="form-control">
                    <option value="">Seleccionar ganador</option>
                    <?php foreach ($activeParticipants as $participant): ?>
                        <option value="<?php echo $participant['id']; ?>" <?php echo $match['winner_id'] == $participant['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($participant['username']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="match_date">Fecha del Partido</label>
                <input type="datetime-local" id="match_date" name="match_date" class="form-control" 
                       value="<?php echo $match['match_date'] ? date('Y-m-d\TH:i', strtotime($match['match_date'])) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="status">Estado</label>
                <select id="status" name="status" class="form-control" required>
                    <option value="pending" <?php echo $match['status'] == 'pending' ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="in_progress" <?php echo $match['status'] == 'in_progress' ? 'selected' : ''; ?>>En Progreso</option>
                    <option value="completed" <?php echo $match['status'] == 'completed' ? 'selected' : ''; ?>>Completado</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Actualizar Partido</button>
                <a href="../tournaments/view.php?id=<?php echo $match['tournament_id']; ?>" class="btn">Volver al Torneo</a>
            </div>
        </form>
    </div>
    
    <script>
        const p1Select = document.getElementById('participant1_id');
        const p2Select = document.getElementById('participant2_id');
        const winnerSelect = document.getElementById('winner_id');
        
        function updateOptions() {
            const p1Value = p1Select.value;
            const p2Value = p2Select.value;
            
            // Actualizar opciones de participante 2
            for (let i = 0; i < p2Select.options.length; i++) {
                const option = p2Select.options[i];
                if (option.value === p1Value && p1Value !== '') {
                    option.disabled = true;
                } else {
                    option.disabled = false;
                }
            }
            
            // Actualizar opciones de participante 1
            for (let i = 0; i < p1Select.options.length; i++) {
                const option = p1Select.options[i];
                if (option.value === p2Value && p2Value !== '') {
                    option.disabled = true;
                } else {
                    option.disabled = false;
                }
            }
            
            // Actualizar opciones de ganador
            for (let i = 0; i < winnerSelect.options.length; i++) {
                const option = winnerSelect.options[i];
                if (option.value !== '' && option.value !== p1Value && option.value !== p2Value) {
                    option.disabled = true;
                } else {
                    option.disabled = false;
                }
            }
        }
        
        p1Select.addEventListener('change', updateOptions);
        p2Select.addEventListener('change', updateOptions);
        updateOptions();
    </script>
</body>
</html>