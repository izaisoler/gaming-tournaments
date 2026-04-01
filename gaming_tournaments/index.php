<?php
require_once 'includes/config.php';
require_once 'includes/functions.php'; // <-- ESTA LÍNEA FALTABA
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Torneos Gaming</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/forms.css">
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <a href="index.php" class="logo">🎮 <?php echo SITE_NAME; ?></a>
            <ul class="nav-links">
                <li><a href="index.php">Inicio</a></li>
                <li><a href="dashboard.php">Torneos</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="dashboard.php">Mi Panel</a></li>
                    <li><a href="logout.php">Cerrar Sesión (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a></li>
                <?php else: ?>
                    <li><a href="login.php">Iniciar Sesión</a></li>
                    <li><a href="register.php">Registrarse</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <div class="container">
        <div class="dashboard-header" style="text-align: center; margin: 40px 0;">
            <h1>🎮 Bienvenido a Gaming Tournaments</h1>
            <p>La plataforma definitiva para gestionar tus torneos de videojuegos</p>
            <?php if (!isLoggedIn()): ?>
                <div style="margin-top: 20px;">
                    <a href="register.php" class="btn btn-primary">Comenzar Ahora</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="grid">
            <div class="card">
                <h3>🎯 Torneos Competitivos</h3>
                <p>Organiza y participa en torneos de tus juegos favoritos</p>
            </div>
            <div class="card">
                <h3>⚔️ Sistema de Eliminación</h3>
                <p>Sistema de torneos con rondas y enfrentamientos</p>
            </div>
            <div class="card">
                <h3>🐾 Integración Pokémon API</h3>
                <p>Consulta datos de Pokémon para armar tu equipo</p>
            </div>
        </div>

        <h2 style="margin: 40px 0 20px;">🎮 Próximos Torneos</h2>
        
        <div class="grid">
            <?php
            try {
                $tournaments = getTournaments(3);
                if ($tournaments && count($tournaments) > 0):
                    foreach ($tournaments as $tournament):
            ?>
                        <div class="card">
                            <h3><?php echo htmlspecialchars($tournament['name']); ?></h3>
                            <p><strong>Juego:</strong> <?php echo htmlspecialchars($tournament['game']); ?></p>
                            <p><strong>Inicio:</strong> <?php echo date('d/m/Y', strtotime($tournament['start_date'])); ?></p>
                            <p><strong>Participantes:</strong> <?php echo isset($tournament['participant_count']) ? $tournament['participant_count'] : 0; ?>/<?php echo $tournament['max_participants']; ?></p>
                            <a href="tournaments/view.php?id=<?php echo $tournament['id']; ?>" class="btn btn-primary" style="margin-top: 10px;">Ver Torneo</a>
                        </div>
            <?php 
                    endforeach;
                else:
            ?>
                    <div class="card">
                        <p>No hay torneos disponibles aún. ¡Vuelve pronto!</p>
                    </div>
            <?php 
                endif;
            } catch (Exception $e) {
                echo '<div class="alert alert-error">Error al cargar torneos: ' . $e->getMessage() . '</div>';
            }
            ?>
        </div>

        <div class="card" style="margin-top: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-align: center;">
            <h3>🐾 Explora Pokémon con nuestra API</h3>
            <p>Descubre información de tus Pokémon favoritos para armar tu equipo perfecto</p>
            <a href="dashboard.php" class="btn btn-success" style="margin-top: 15px; background: white; color: #667eea;">Explorar Pokémon</a>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 Gaming Tournaments - Todos los derechos reservados</p>
    </footer>
</body>
</html>