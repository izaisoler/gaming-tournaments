<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../api/pokemon_api.php';

if (!isset($_GET['id'])) {
    header('Location: ../dashboard.php');
    exit;
}

$tournament = getTournamentById($_GET['id']);
if (!$tournament) {
    header('Location: ../dashboard.php');
    exit;
}

$participants = getParticipantsByTournament($tournament['id']);

// Verificar si el usuario ya está inscrito
$isParticipant = false;
$userParticipantId = null;
if (isLoggedIn()) {
    foreach ($participants as $p) {
        if ($p['user_id'] == $_SESSION['user_id']) {
            $isParticipant = true;
            $userParticipantId = $p['id'];
            break;
        }
    }
}

// Procesar inscripción
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['join'])) {
    if (!$isParticipant && count($participants) < $tournament['max_participants']) {
        $pokemonTeam = isset($_POST['pokemon_team_json']) ? $_POST['pokemon_team_json'] : null;
        if (addParticipant($tournament['id'], $_SESSION['user_id'], $pokemonTeam)) {
            header('Location: view.php?id=' . $tournament['id'] . '&msg=inscripcion_exitosa');
            exit;
        }
    }
}

// Procesar actualización de equipo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_team'])) {
    if ($isParticipant) {
        global $pdo;
        $pokemonTeam = isset($_POST['pokemon_team_json']) ? $_POST['pokemon_team_json'] : null;
        $stmt = $pdo->prepare("UPDATE participants SET pokemon_team = ? WHERE id = ?");
        if ($stmt->execute([$pokemonTeam, $userParticipantId])) {
            header('Location: view.php?id=' . $tournament['id'] . '&msg=equipo_actualizado');
            exit;
        }
    }
}

// Obtener mensajes flash
$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

// Obtener partidos del torneo
global $pdo;
$stmt = $pdo->prepare("SELECT m.*, 
                       p1.username as player1_name, p2.username as player2_name,
                       w.username as winner_name
                       FROM matches m
                       LEFT JOIN participants p1p ON m.participant1_id = p1p.id
                       LEFT JOIN users p1 ON p1p.user_id = p1.id
                       LEFT JOIN participants p2p ON m.participant2_id = p2p.id
                       LEFT JOIN users p2 ON p2p.user_id = p2.id
                       LEFT JOIN participants wp ON m.winner_id = wp.id
                       LEFT JOIN users w ON wp.user_id = w.id
                       WHERE m.tournament_id = ?
                       ORDER BY m.round, m.match_date");
$stmt->execute([$tournament['id']]);
$matches = $stmt->fetchAll();

// Obtener Pokémon de la API para el formulario
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

// Obtener equipo actual del participante si está inscrito
$currentPokemonTeam = [];
if ($isParticipant) {
    $stmt = $pdo->prepare("SELECT pokemon_team FROM participants WHERE id = ?");
    $stmt->execute([$userParticipantId]);
    $participantData = $stmt->fetch();
    if ($participantData && $participantData['pokemon_team']) {
        $currentPokemonTeam = json_decode($participantData['pokemon_team'], true);
        if (!is_array($currentPokemonTeam)) {
            $currentPokemonTeam = [];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tournament['name']); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
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
        .match-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 15px;
            margin-bottom: 15px;
        }
        .match-vs {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 10px 0;
        }
        .match-player {
            flex: 1;
            text-align: center;
        }
        .match-score {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
        }
        .match-winner {
            background: #48bb78;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            display: inline-block;
            font-size: 12px;
        }
        .action-buttons {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        .participant-pokemon {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <a href="../index.php" class="logo">🎮 <?php echo SITE_NAME; ?></a>
            <ul class="nav-links">
                <li><a href="../index.php">Inicio</a></li>
                <li><a href="../dashboard.php">Dashboard</a></li>
                <?php if (isAdmin()): ?>
                    <li><a href="../tournaments/create.php">Crear Torneo</a></li>
                    <li><a href="../tournaments/edit.php?id=<?php echo $tournament['id']; ?>">Editar Torneo</a></li>
                <?php endif; ?>
                <?php if (isLoggedIn()): ?>
                <li><a href="../logout.php">Cerrar Sesión (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
                <?php else: ?>
                <li><a href="../login.php">Iniciar Sesión</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <div class="dashboard-container">
        <!-- Mensajes de alerta -->
        <?php if ($msg == 'inscripcion_exitosa'): ?>
            <div class="alert alert-success">✅ ¡Te has inscrito correctamente al torneo!</div>
        <?php endif; ?>
        <?php if ($msg == 'equipo_actualizado'): ?>
            <div class="alert alert-success">✅ ¡Tu equipo ha sido actualizado!</div>
        <?php endif; ?>
        <?php if ($msg == 'participante_eliminado'): ?>
            <div class="alert alert-success">✅ Participante eliminado correctamente</div>
        <?php endif; ?>
        <?php if ($msg == 'partido_creado'): ?>
            <div class="alert alert-success">✅ Partido creado correctamente</div>
        <?php endif; ?>
        <?php if ($msg == 'partido_eliminado'): ?>
            <div class="alert alert-success">✅ Partido eliminado correctamente</div>
        <?php endif; ?>
        <?php if ($error == 'ya_inscrito'): ?>
            <div class="alert alert-error">❌ Ya estás inscrito en este torneo</div>
        <?php endif; ?>
        <?php if ($error == 'torneo_lleno'): ?>
            <div class="alert alert-error">❌ El torneo está lleno, no hay cupos disponibles</div>
        <?php endif; ?>

        <!-- Header del Torneo -->
        <div class="dashboard-header">
            <h1><?php echo htmlspecialchars($tournament['name']); ?></h1>
            <p><?php echo htmlspecialchars($tournament['game']); ?></p>
            <div class="status-badge status-<?php echo $tournament['status']; ?>" style="display: inline-block; margin-top: 10px;">
                <?php 
                $estados = [
                    'pending' => '⏳ Pendiente',
                    'active' => '🔥 Activo',
                    'completed' => '🏆 Completado',
                    'cancelled' => '❌ Cancelado'
                ];
                echo $estados[$tournament['status']] ?? ucfirst($tournament['status']);
                ?>
            </div>
        </div>

        <!-- Información del Torneo -->
        <div class="card">
            <h3>📝 Descripción</h3>
            <p><?php echo nl2br(htmlspecialchars($tournament['description'])); ?></p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
                <div>
                    <strong>📅 Fecha de Inicio:</strong><br>
                    <?php echo date('d/m/Y H:i', strtotime($tournament['start_date'])); ?>
                </div>
                <div>
                    <strong>🏁 Fecha de Fin:</strong><br>
                    <?php echo date('d/m/Y H:i', strtotime($tournament['end_date'])); ?>
                </div>
                <div>
                    <strong>👥 Participantes:</strong><br>
                    <?php echo count($participants); ?>/<?php echo $tournament['max_participants']; ?>
                </div>
                <div>
                    <strong>👑 Creado por:</strong><br>
                    <?php echo htmlspecialchars($tournament['creator_name'] ?? 'Admin'); ?>
                </div>
            </div>
        </div>

        <!-- Sección de Inscripción / Editar Equipo -->
        <?php if (isLoggedIn()): ?>
            <?php if (!$isParticipant && count($participants) < $tournament['max_participants'] && $tournament['status'] == 'pending'): ?>
                <!-- Formulario de Inscripción -->
                <div class="card">
                    <h3>🎮 Inscribirse al Torneo</h3>
                    <p>Completa tu inscripción seleccionando tu equipo Pokémon (opcional)</p>
                    <form method="POST" action="" id="joinForm">
                        <div class="form-group">
                            <label><strong>Selecciona tu equipo Pokémon (máximo 3)</strong></label>
                            <p class="max-3">Puedes seleccionar hasta 3 Pokémon para tu equipo de batalla</p>
                            <div class="pokemon-grid" id="pokemonGridJoin">
                                <?php foreach ($pokemonList as $pokemon): 
                                    $urlParts = explode('/', rtrim($pokemon['url'], '/'));
                                    $pokemonId = end($urlParts);
                                ?>
                                    <div class="pokemon-option" data-name="<?php echo $pokemon['name']; ?>">
                                        <input type="checkbox" name="pokemon_join[]" value="<?php echo $pokemon['name']; ?>" class="pokemon-checkbox-join">
                                        <img src="https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/<?php echo $pokemonId; ?>.png" alt="<?php echo $pokemon['name']; ?>">
                                        <span><?php echo ucfirst($pokemon['name']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="selected-team" id="selectedTeamJoin" style="display: none;">
                            <strong>Tu equipo seleccionado:</strong>
                            <div id="teamListJoin"></div>
                        </div>
                        <input type="hidden" name="pokemon_team_json" id="pokemonTeamJsonJoin">
                        <div class="form-actions" style="margin-top: 20px;">
                            <button type="submit" name="join" class="btn btn-primary" id="submitJoinBtn">Inscribirse</button>
                            <a href="../dashboard.php" class="btn">Cancelar</a>
                        </div>
                    </form>
                </div>
            <?php elseif ($isParticipant && $tournament['status'] == 'pending'): ?>
                <!-- Formulario para editar equipo -->
                <div class="card">
                    <h3>🎮 Mi Equipo Pokémon</h3>
                    <p>Puedes modificar tu equipo hasta que comience el torneo</p>
                    
                    <?php if (!empty($currentPokemonTeam)): ?>
                        <div class="selected-team" style="display: block; margin-bottom: 15px; background: #e8f5e9;">
                            <strong>Equipo actual:</strong>
                            <?php foreach ($currentPokemonTeam as $pokemon): ?>
                                <span class="pokemon-tag"><?php echo ucfirst($pokemon); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" id="updateTeamForm">
                        <div class="form-group">
                            <label><strong>Modificar equipo Pokémon (máximo 3)</strong></label>
                            <div class="pokemon-grid" id="pokemonGridUpdate">
                                <?php foreach ($pokemonList as $pokemon): 
                                    $urlParts = explode('/', rtrim($pokemon['url'], '/'));
                                    $pokemonId = end($urlParts);
                                ?>
                                    <div class="pokemon-option">
                                        <input type="checkbox" name="pokemon_update[]" value="<?php echo $pokemon['name']; ?>" 
                                            class="pokemon-checkbox-update"
                                            <?php echo in_array($pokemon['name'], $currentPokemonTeam) ? 'checked' : ''; ?>>
                                        <img src="https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/<?php echo $pokemonId; ?>.png" alt="<?php echo $pokemon['name']; ?>">
                                        <span><?php echo ucfirst($pokemon['name']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="selected-team" id="selectedTeamUpdate" <?php echo empty($currentPokemonTeam) ? 'style="display: none;"' : ''; ?>>
                            <strong>Tu equipo seleccionado:</strong>
                            <div id="teamListUpdate">
                                <?php foreach ($currentPokemonTeam as $pokemon): ?>
                                    <span class="pokemon-tag"><?php echo ucfirst($pokemon); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <input type="hidden" name="pokemon_team_json" id="pokemonTeamJsonUpdate" value='<?php echo json_encode($currentPokemonTeam); ?>'>
                        <div class="form-actions" style="margin-top: 20px;">
                            <button type="submit" name="update_team" class="btn btn-primary">Actualizar Equipo</button>
                        </div>
                    </form>
                </div>
            <?php elseif ($isParticipant): ?>
                <div class="alert alert-success">
                    ✅ Ya estás inscrito en este torneo
                    <?php if (!empty($currentPokemonTeam)): ?>
                        <br><strong>Tu equipo:</strong> <?php foreach ($currentPokemonTeam as $p): ?> <?php echo ucfirst($p); ?> <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php elseif (count($participants) >= $tournament['max_participants']): ?>
                <div class="alert alert-error">❌ El torneo está lleno (máximo <?php echo $tournament['max_participants']; ?> participantes)</div>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-error">
                🔐 <a href="../login.php" style="color: #667eea;">Inicia sesión</a> para inscribirte en este torneo
            </div>
        <?php endif; ?>

        <!-- Lista de Participantes -->
        <div class="tournament-list">
            <h3>👥 Participantes Inscritos (<?php echo count($participants); ?>/<?php echo $tournament['max_participants']; ?>)</h3>
            <?php if (count($participants) > 0): ?>
                <div class="grid">
                    <?php foreach ($participants as $participant): 
                        $participantPokemon = $participant['pokemon_team'] ? json_decode($participant['pokemon_team'], true) : [];
                    ?>
                        <div class="card">
                            <h4><?php echo htmlspecialchars($participant['username']); ?></h4>
                            <p><small>Inscrito: <?php echo date('d/m/Y', strtotime($participant['registration_date'])); ?></small></p>
                            <p class="participant-pokemon">
                                <strong>Estado:</strong> 
                                <span class="status-badge status-<?php echo $participant['status']; ?>">
                                    <?php echo ucfirst($participant['status']); ?>
                                </span>
                            </p>
                            <?php if (!empty($participantPokemon)): ?>
                                <div class="participant-pokemon">
                                    <strong>Equipo:</strong><br>
                                    <?php foreach ($participantPokemon as $poke): ?>
                                        <span class="pokemon-tag"><?php echo ucfirst($poke); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <?php if (isAdmin()): ?>
                                <div class="action-buttons">
                                    <a href="../participants/edit.php?id=<?php echo $participant['id']; ?>" class="btn btn-primary btn-sm">Editar</a>
                                    <a href="../participants/delete.php?id=<?php echo $participant['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar este participante?')">Eliminar</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No hay participantes inscritos aún. ¡Sé el primero!</p>
            <?php endif; ?>
        </div>

        <!-- Sección de Partidos -->
        <div class="tournament-list" style="margin-top: 30px;">
            <h3>⚔️ Partidos del Torneo</h3>
            
            <?php if (count($matches) > 0): ?>
                <div class="grid">
                    <?php foreach ($matches as $match): ?>
                        <div class="match-card">
                            <div style="text-align: center; margin-bottom: 10px;">
                                <span class="status-badge status-<?php echo $match['status']; ?>">
                                    <?php 
                                    $matchStatus = [
                                        'pending' => '⏳ Pendiente',
                                        'in_progress' => '🎮 En Progreso',
                                        'completed' => '✅ Completado'
                                    ];
                                    echo $matchStatus[$match['status']] ?? ucfirst($match['status']);
                                    ?>
                                </span>
                                <span style="margin-left: 10px;">Ronda <?php echo $match['round']; ?></span>
                            </div>
                            
                            <div class="match-vs">
                                <div class="match-player">
                                    <strong><?php echo htmlspecialchars($match['player1_name'] ?? 'Por definir'); ?></strong>
                                    <?php if ($match['winner_name'] == $match['player1_name']): ?>
                                        <div class="match-winner">🏆 Ganador</div>
                                    <?php endif; ?>
                                </div>
                                <div class="match-score">
                                    <?php echo $match['score1']; ?> - <?php echo $match['score2']; ?>
                                </div>
                                <div class="match-player">
                                    <strong><?php echo htmlspecialchars($match['player2_name'] ?? 'Por definir'); ?></strong>
                                    <?php if ($match['winner_name'] == $match['player2_name']): ?>
                                        <div class="match-winner">🏆 Ganador</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($match['match_date']): ?>
                                <p style="text-align: center; font-size: 12px; color: #666;">
                                    📅 <?php echo date('d/m/Y H:i', strtotime($match['match_date'])); ?>
                                </p>
                            <?php endif; ?>
                            
                            <?php if (isAdmin()): ?>
                                <div class="action-buttons">
                                    <a href="../matches/edit.php?id=<?php echo $match['id']; ?>" class="btn btn-primary btn-sm">Editar</a>
                                    <a href="../matches/delete.php?id=<?php echo $match['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar este partido?')">Eliminar</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No hay partidos creados aún.</p>
            <?php endif; ?>
            
            <?php if (isAdmin() && ($tournament['status'] == 'active' || $tournament['status'] == 'pending')): ?>
                <div style="margin-top: 20px;">
                    <a href="../matches/create.php?tournament_id=<?php echo $tournament['id']; ?>" class="btn btn-primary">+ Crear Partido</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Botones de acción para admin -->
        <?php if (isAdmin()): ?>
            <div style="margin-top: 30px; display: flex; gap: 10px; justify-content: center;">
                <a href="edit.php?id=<?php echo $tournament['id']; ?>" class="btn btn-primary">✏️ Editar Torneo</a>
                <a href="delete.php?id=<?php echo $tournament['id']; ?>&confirm=yes" class="btn btn-danger" onclick="return confirm('¿Estás seguro de que quieres eliminar este torneo? Se eliminarán todos los participantes y partidos asociados.')">🗑️ Eliminar Torneo</a>
                <a href="../dashboard.php" class="btn">← Volver al Dashboard</a>
            </div>
        <?php else: ?>
            <div style="margin-top: 30px; text-align: center;">
                <a href="../dashboard.php" class="btn">← Volver al Dashboard</a>
            </div>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <p>&copy; 2024 Gaming Tournaments - Todos los derechos reservados</p>
        <p style="font-size: 12px;">🐾 Datos de Pokémon proporcionados por <a href="https://pokeapi.co/" target="_blank">PokeAPI</a></p>
    </footer>

    <!-- Scripts para manejo de selección de Pokémon -->
    <script>
    // Función para manejar la selección de Pokémon en cualquier formulario
    function setupPokemonSelection(checkboxClass, teamListId, teamJsonId, selectedTeamDivId, maxLimit = 3) {
        const checkboxes = document.querySelectorAll('.' + checkboxClass);
        const teamListDiv = document.getElementById(teamListId);
        const teamJsonInput = document.getElementById(teamJsonId);
        const selectedTeamDiv = document.getElementById(selectedTeamDivId);
        let selectedPokemon = [];
        
        // Inicializar selección actual
        checkboxes.forEach(cb => {
            if (cb.checked) {
                selectedPokemon.push(cb.value);
            }
        });
        
        function updateDisplay() {
            if (selectedPokemon.length > 0) {
                if (selectedTeamDiv) selectedTeamDiv.style.display = 'block';
                if (teamListDiv) {
                    teamListDiv.innerHTML = selectedPokemon.map(name => 
                        `<span class="pokemon-tag">${name.charAt(0).toUpperCase() + name.slice(1)}</span>`
                    ).join('');
                }
            } else {
                if (selectedTeamDiv) selectedTeamDiv.style.display = 'none';
                if (teamListDiv) teamListDiv.innerHTML = '';
            }
            teamJsonInput.value = JSON.stringify(selectedPokemon);
        }
        
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const pokemonName = this.value;
                
                if (this.checked) {
                    if (selectedPokemon.length >= maxLimit) {
                        this.checked = false;
                        alert(`Solo puedes seleccionar hasta ${maxLimit} Pokémon para tu equipo`);
                        return;
                    }
                    selectedPokemon.push(pokemonName);
                } else {
                    const index = selectedPokemon.indexOf(pokemonName);
                    if (index > -1) {
                        selectedPokemon.splice(index, 1);
                    }
                }
                
                updateDisplay();
            });
        });
        
        updateDisplay();
    }
    
    // Configurar los diferentes formularios si existen
    if (document.querySelector('.pokemon-checkbox-join')) {
        setupPokemonSelection('pokemon-checkbox-join', 'teamListJoin', 'pokemonTeamJsonJoin', 'selectedTeamJoin');
    }
    
    if (document.querySelector('.pokemon-checkbox-update')) {
        setupPokemonSelection('pokemon-checkbox-update', 'teamListUpdate', 'pokemonTeamJsonUpdate', 'selectedTeamUpdate');
    }
    </script>
</body>
</html>
