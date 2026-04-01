<?php
require_once __DIR__ . '/config.php';

function registerUser($username, $email, $password) {
    global $pdo;
    
    // Validar datos
    if (empty($username) || empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Todos los campos son obligatorios'];
    }
    
    // Verificar si el usuario ya existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->rowCount() > 0) {
        return ['success' => false, 'message' => 'El usuario o email ya existe'];
    }
    
    // Crear usuario
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    
    if ($stmt->execute([$username, $email, $hashedPassword])) {
        return ['success' => true, 'message' => 'Usuario registrado correctamente'];
    }
    
    return ['success' => false, 'message' => 'Error al registrar usuario'];
}

function loginUser($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        return ['success' => true, 'message' => 'Login exitoso'];
    }
    
    return ['success' => false, 'message' => 'Usuario o contraseña incorrectos'];
}

function logoutUser() {
    session_destroy();
    return true;
}
?>