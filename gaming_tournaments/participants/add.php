<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
requireLogin();

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

// Verificar si el usuario ya está inscrito
$participants = getParticipantsByTournament($tournament_id);
$alreadyParticipant = false;
foreach ($participants as $p) {
    if ($p['user_id'] == $_SESSION['user_id']) {
        $alreadyParticipant = true;
        break;
    }
}

if ($alreadyParticipant) {
    header('Location: ../tournaments/view.php?id=' . $tournament_id . '&error=ya_inscrito');
    exit;
}

// Verificar si hay cupo
if (count($participants) >= $tournament['max_participants']) {
    header('Location: ../tournaments/view.php?id=' . $tournament_id . '&error=torneo_lleno');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pokemon_team = isset($_POST['pokemon_team']) ? $_POST['pokemon_team'] : null;
    
    if (addParticipant($tournament_id, $_SESSION['user_id'], $pokemon_team)) {
        header('Location: ../tournaments/view.php?id=' . $tournament_id . '&msg=inscripcion_exitosa');
        exit;
    } else {
        $error = 'Error al inscribirse en el torneo';
    }
}

// Obtener lista de Pokémon desde la API para el formulario
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
    <title>Inscribirse - <?php echo htmlspecialchars($tournament['name']); ?> - <?php echo SITE_NAME; ?></title>
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
            transition: background 0.3s;
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
        .selected-team {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            display: none;
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
        .max-3 {
            color: #e53e3e;
            font-size: 12px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="form-container" style="max-width: 800px;">
        <h2 class="form-title">Inscribirse al Torneo</h2>
        <h3 style="text-align: center; margin-bottom: 20px;"><?php echo htmlspecialchars($tournament['name']); ?></h3>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" id="inscriptionForm">
            <div class="form-group">
                <label><strong>Selecciona tu equipo Pokémon (máximo 3)</strong></label>
                <p class="max-3">Puedes seleccionar hasta 3 Pokémon para tu equipo de batalla</p>
                <div class="pokemon-grid" id="pokemonGrid">
                    <?php foreach ($pokemonList as $pokemon): ?>
                        <div class="pokemon-option" data-name="<?php echo $pokemon['name']; ?>">
                            <input type="checkbox" name="pokemon_team[]" value="<?php echo $pokemon['name']; ?>" class="pokemon-checkbox">
                            <img src="https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/<?php 
                                // Obtener ID del Pokémon desde la URL
                                $urlParts = explode('/', rtrim($pokemon['url'], '/'));
                                $pokemonId = end($urlParts);
                                echo $pokemonId;
                            ?>.png" alt="<?php echo $pokemon['name']; ?>">
                            <span><?php echo ucfirst($pokemon['name']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="selected-team" id="selectedTeam">
                <strong>Tu equipo seleccionado:</strong>
                <div id="teamList"></div>
            </div>
            
            <input type="hidden" name="pokemon_team_json" id="pokemonTeamJson">
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="submitBtn">Inscribirse</button>
                <a href="../tournaments/view.php?id=<?php echo $tournament_id; ?>" class="btn">Cancelar</a>
            </div>
        </form>
    </div>
    
    <script>
        const checkboxes = document.querySelectorAll('.pokemon-checkbox');
        const selectedTeamDiv = document.getElementById('selectedTeam');
        const teamListDiv = document.getElementById('teamList');
        const pokemonTeamJson = document.getElementById('pokemonTeamJson');
        const submitBtn = document.getElementById('submitBtn');
        let selectedPokemon = [];
        
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const pokemonName = this.value;
                
                if (this.checked) {
                    if (selectedPokemon.length >= 3) {
                        this.checked = false;
                        alert('Solo puedes seleccionar hasta 3 Pokémon para tu equipo');
                        return;
                    }
                    selectedPokemon.push(pokemonName);
                } else {
                    const index = selectedPokemon.indexOf(pokemonName);
                    if (index > -1) {
                        selectedPokemon.splice(index, 1);
                    }
                }
                
                updateTeamDisplay();
            });
        });
        
        function updateTeamDisplay() {
            if (selectedPokemon.length > 0) {
                selectedTeamDiv.style.display = 'block';
                teamListDiv.innerHTML = selectedPokemon.map(name => 
                    `<span class="pokemon-tag">${name.charAt(0).toUpperCase() + name.slice(1)}</span>`
                ).join('');
                pokemonTeamJson.value = JSON.stringify(selectedPokemon);
            } else {
                selectedTeamDiv.style.display = 'none';
                pokemonTeamJson.value = '';
            }
        }
        
        document.getElementById('inscriptionForm').addEventListener('submit', function(e) {
            if (selectedPokemon.length === 0) {
                if (!confirm('¿Estás seguro de que quieres inscribirte sin equipo Pokémon?')) {
                    e.preventDefault();
                }
            }
        });
    </script>
</body>
</html>