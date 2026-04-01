<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireAdmin();

if (!isset($_GET['tournament_id'])) {
    header('Location: ../dashboard.php');
    exit;
}

$tournament_id = $_GET['tournament_id'];
$tournament = getTournamentById($tournament_id);

if (!$tournament) {
    header('Location: ../dashboard.php');
    exit;
}

// Obtener participantes del torneo
$participants = getParticipantsByTournament($tournament_id);
$activeParticipants = array_filter($participants, function($p) {
    return $p['status'] == 'confirmed' || $p['status'] == 'registered';
});

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    global $pdo;
    
    $round = $_POST['round'];
    $participant1_id = $_POST['participant1_id'];
    $participant2_id = $_POST['participant2_id'];
    $match_date = $_POST['match_date'] ?: null;
    
    $stmt = $pdo->prepare("INSERT INTO matches (tournament_id, round, participant1_id, participant2_id, match_date, status)
                           VALUES (?, ?, ?, ?, ?, 'pending')");
    
    if ($stmt->execute([$tournament_id, $round, $participant1_id, $participant2_id, $match_date])) {
        header('Location: ../tournaments/view.php?id=' . $tournament_id . '&msg=partido_creado');
        exit;
    } else {
        $error = 'Error al crear el partido';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Partido - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/forms.css">
</head>
<body>
    <div class="form-container">
        <h2 class="form-title">Crear Nuevo Partido</h2>
        <h3 style="text-align: center; margin-bottom: 20px;"><?php echo htmlspecialchars($tournament['name']); ?></h3>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (count($activeParticipants) < 2): ?>
            <div class="alert alert-error">
                Se necesitan al menos 2 participantes confirmados para crear un partido.
                <br>Participantes actuales: <?php echo count($activeParticipants); ?>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="round">Ronda</label>
                    <input type="number" id="round" name="round" class="form-control" min="1" value="1" required>
                </div>
                
                <div class="form-group">
                    <label for="participant1_id">Participante 1</label>
                    <select id="participant1_id" name="participant1_id" class="form-control" required>
                        <option value="">Seleccionar participante</option>
                        <?php foreach ($activeParticipants as $participant): ?>
                            <option value="<?php echo $participant['id']; ?>">
                                <?php echo htmlspecialchars($participant['username']); ?>
                                <?php if ($participant['pokemon_team']): ?>
                                    (Equipo: <?php echo implode(', ', json_decode($participant['pokemon_team'], true)); ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="participant2_id">Participante 2</label>
                    <select id="participant2_id" name="participant2_id" class="form-control" required>
                        <option value="">Seleccionar participante</option>
                        <?php foreach ($activeParticipants as $participant): ?>
                            <option value="<?php echo $participant['id']; ?>">
                                <?php echo htmlspecialchars($participant['username']); ?>
                                <?php if ($participant['pokemon_team']): ?>
                                    (Equipo: <?php echo implode(', ', json_decode($participant['pokemon_team'], true)); ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="match_date">Fecha del Partido (opcional)</label>
                    <input type="datetime-local" id="match_date" name="match_date" class="form-control">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Crear Partido</button>
                    <a href="../tournaments/view.php?id=<?php echo $tournament_id; ?>" class="btn">Cancelar</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
    
    <script>
        const p1Select = document.getElementById('participant1_id');
        const p2Select = document.getElementById('participant2_id');
        
        function updateOptions() {
            const p1Value = p1Select.value;
            const p2Value = p2Select.value;
            
            for (let i = 0; i < p2Select.options.length; i++) {
                const option = p2Select.options[i];
                if (option.value === p1Value && p1Value !== '') {
                    option.disabled = true;
                } else {
                    option.disabled = false;
                }
            }
            
            for (let i = 0; i < p1Select.options.length; i++) {
                const option = p1Select.options[i];
                if (option.value === p2Value && p2Value !== '') {
                    option.disabled = true;
                } else {
                    option.disabled = false;
                }
            }
        }
        
        p1Select.addEventListener('change', updateOptions);
        p2Select.addEventListener('change', updateOptions);
    </script>
</body>
</html>