<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $result = registerUser($_POST['username'], $_POST['email'], $_POST['password']);
    if ($result['success']) {
        $success = $result['message'];
        // Redirigir al login después de 2 segundos
        header("refresh:2;url=login.php");
    } else {
        $error = $result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrarse - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/forms.css">
</head>
<body>
    <div class="form-container">
        <h2 class="form-title">Crear Cuenta</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?> Redirigiendo al login...</div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Usuario</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Registrarse</button>
                <a href="login.php" class="btn">Ya tengo cuenta</a>
            </div>
        </form>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="index.php">← Volver al inicio</a>
        </div>
    </div>
</body>
</html>