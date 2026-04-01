<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireAdmin();

if (!isset($_GET['id'])) {
    header('Location: ../dashboard.php');
    exit;
}

$participant_id = $_GET['id'];

// Obtener datos del participante
global $pdo;
$stmt = $pdo->prepare("SELECT p.*, u.username, u.email, t.name as tournament_name, t.id as tournament_id
                       FROM participants p
                       JOIN users u ON p.user_id = u.id
                       JOIN tournaments t ON p.tournament_id = t.id
                       WHERE p.id = ?");
$stmt->execute([$participant_id]);
$participant = $stmt->fetch();

if (!$participant) {
    header('Location: ../dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $status = $_POST['status'];
    $pokemon_team = isset($_POST['pokemon_team']) ? $_POST['pokemon_team'] : null;
    
    $stmt = $pdo->prepare("UPDATE participants SET status = ?, pokemon_team = ? WHERE id = ?");
    if ($stmt->execute([$status, $pokemon_team, $participant_id])) {
        $success = 'Participante actualizado correctamente';
        // Recargar datos
        $stmt = $pdo->prepare("SELECT p.*, u.username, u.email, t.name as tournament_name, t.id as tournament_id
                               FROM participants p
                               JOIN users u ON p.user_id = u.id
                               JOIN tournaments t ON p.tournament_id = t.id
                               WHERE p.id = ?");
        $stmt->execute([$participant_id]);
        $participant = $stmt->fetch();
    } else {
        $error = 'Error al actualizar el participante';
    }
}

// Decodificar equipo Pokémon
$pokemonTeam = $participant['pokemon_team'] ? json_decode($participant['pokemon_team'], true) : [];

// Obtener lista de Pokémon de la API
$pokemonList = [];
$apiUrl = "https://pokeapi.co/api/v2/pokemon?limit=50";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

if ($response) {
    $data = json_decode($response, true);
    if (isset($data['results'])) {
        $pokemonList = $data['results'];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Participante - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/forms.css">
    <style>
        .pokemon-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 10px;
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
        }
        .pokemon-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 5px;
            cursor: pointer;
            border-radius: 5px;
        }
        .pokemon-option:hover {
            background: #f0f0f0;
        }
        .pokemon-option input {
            margin: 0;
        }
        .pokemon-option img {
            width: 40px;
            height: 40px;
        }
        .pokemon-tag {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            margin: 5px;
            font-size: 12px;
        }
        .current-team {
            margin-bottom: 20px;
            padding: 15px;
            background: #e8f5e9;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="form-container" style="max-width: 800px;">
        <h2 class="form-title">Editar Participante</h2>
        <h3 style="text-align: center; margin-bottom: 20px;"><?php echo htmlspecialchars($participant['username']); ?> - <?php echo htmlspecialchars($participant['tournament_name']); ?></h3>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="current-team">
            <strong>Estado actual:</strong> 
            <span class="status-badge status-<?php echo $participant['status']; ?>">
                <?php echo ucfirst($participant['status']); ?>
            </span>
            <?php if (!empty($pokemonTeam)): ?>
                <br><strong>Equipo actual:</strong>
                <?php foreach ($pokemonTeam as $pokemon): ?>
                    <span class="pokemon-tag"><?php echo ucfirst($pokemon); ?></span>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="status">Estado del Participante</label>
                <select id="status" name="status" class="form-control" required>
                    <option value="registered" <?php echo $participant['status'] == 'registered' ? 'selected' : ''; ?>>Registrado</option>
                    <option value="confirmed" <?php echo $participant['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmado</option>
                    <option value="disqualified" <?php echo $participant['status'] == 'disqualified' ? 'selected' : ''; ?>>Descalificado</option>
                </select>
            </div>
            
            <div class="form-group">
                <label><strong>Editar equipo Pokémon (máximo 3)</strong></label>
                <div class="pokemon-grid">
                    <?php foreach ($pokemonList as $pokemon): ?>
                        <div class="pokemon-option">
                            <input type="checkbox" name="pokemon_team[]" value="<?php echo $pokemon['name']; ?>" 
                                <?php echo in_array($pokemon['name'], $pokemonTeam) ? 'checked' : ''; ?>>
                            <img src="https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/<?php 
                                $urlParts = explode('/', rtrim($pokemon['url'], '/'));
                                echo end($urlParts);
                            ?>.png" alt="<?php echo $pokemon['name']; ?>">
                            <span><?php echo ucfirst($pokemon['name']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <small>Selecciona hasta 3 Pokémon para el equipo del participante</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Actualizar</button>
                <a href="../tournaments/view.php?id=<?php echo $participant['tournament_id']; ?>" class="btn">Volver al Torneo</a>
            </div>
        </form>
    </div>
    
    <script>
        const checkboxes = document.querySelectorAll('input[name="pokemon_team[]"]');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const checked = document.querySelectorAll('input[name="pokemon_team[]"]:checked');
                if (checked.length > 3) {
                    this.checked = false;
                    alert('Solo puedes seleccionar hasta 3 Pokémon');
                }
            });
        });
    </script>
</body>
</html>