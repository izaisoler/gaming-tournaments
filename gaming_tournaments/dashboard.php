<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'api/pokemon_api.php';

requireLogin();

$tournaments = getTournaments();
$userStats = getUserStatistics($_SESSION['user_id']);

// Obtener Pokémon de la API
$pokemonList = getPokemonList(10);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <a href="index.php" class="logo">🎮 <?php echo SITE_NAME; ?></a>
            <ul class="nav-links">
                <li><a href="index.php">Inicio</a></li>
                <li><a href="dashboard.php">Dashboard</a></li>
                <?php if (isAdmin()): ?>
                    <li><a href="tournaments/create.php">Crear Torneo</a></li>
                <?php endif; ?>
                <li><a href="logout.php">Cerrar Sesión (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
            </ul>
        </nav>
    </header>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
            <p>Gestiona tus torneos y participaciones</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $userStats['tournaments_joined']; ?></div>
                <div class="stat-label">Torneos Participados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $userStats['matches_won']; ?></div>
                <div class="stat-label">Partidos Ganados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($tournaments); ?></div>
                <div class="stat-label">Torneos Activos</div>
            </div>
        </div>

        <?php if (isAdmin()): ?>
            <div style="margin-bottom: 30px;">
                <a href="tournaments/create.php" class="btn btn-primary">+ Crear Nuevo Torneo</a>
            </div>
        <?php endif; ?>

        <div class="tournament-list">
            <h2>📋 Mis Torneos</h2>
            <?php foreach ($tournaments as $tournament): ?>
                <div class="tournament-item">
                    <div class="tournament-info">
                        <h4><?php echo htmlspecialchars($tournament['name']); ?></h4>
                        <p><?php echo htmlspecialchars($tournament['game']); ?> - Inicio: <?php echo date('d/m/Y', strtotime($tournament['start_date'])); ?></p>
                        <p>Participantes: <?php echo $tournament['participant_count']; ?>/<?php echo $tournament['max_participants']; ?></p>
                    </div>
                    <div>
                        <span class="status-badge status-<?php echo $tournament['status']; ?>">
                            <?php echo ucfirst($tournament['status']); ?>
                        </span>
                        <a href="tournaments/view.php?id=<?php echo $tournament['id']; ?>" class="btn btn-primary" style="margin-left: 10px;">Ver</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Sección de API Externa - Pokémon -->
        <div class="tournament-list" style="margin-top: 30px;">
            <h2>🐾 Pokémon Destacados (API Externa)</h2>
            <div class="grid" style="margin-top: 20px;">
                <?php if ($pokemonList['success']): ?>
                    <?php foreach ($pokemonList['data'] as $pokemon): ?>
                        <div class="card" style="text-align: center;">
                            <h3><?php echo ucfirst($pokemon['name']); ?></h3>
                            <button onclick="getPokemonDetails('<?php echo $pokemon['name']; ?>')" class="btn btn-primary" style="margin-top: 10px;">
                                Ver Detalles
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Error al cargar Pokémon</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    function getPokemonDetails(name) {
        fetch('api/pokemon_api.php?action=details&name=' + name)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Pokémon: ${data.data.name}\nAltura: ${data.data.height}\nPeso: ${data.data.weight}\nTipos: ${data.data.types.join(', ')}`);
                } else {
                    alert('Error al obtener detalles');
                }
            });
    }
    </script>

    <footer class="footer">
        <p>&copy; 2024 Gaming Tournaments - Todos los derechos reservados</p>
    </footer>
</body>
</html>