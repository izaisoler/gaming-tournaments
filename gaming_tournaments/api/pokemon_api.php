<?php
require_once __DIR__ . '/../includes/config.php';

function getPokemonList($limit = 20) {
    $url = "https://pokeapi.co/api/v2/pokemon?limit=" . $limit;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        return ['success' => true, 'data' => $data['results']];
    }
    
    return ['success' => false, 'message' => 'Error al obtener Pokémon'];
}

function getPokemonDetails($name) {
    $url = "https://pokeapi.co/api/v2/pokemon/" . strtolower($name);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $pokemon = json_decode($response, true);
        return [
            'success' => true,
            'data' => [
                'name' => $pokemon['name'],
                'image' => $pokemon['sprites']['front_default'],
                'types' => array_map(function($type) {
                    return $type['type']['name'];
                }, $pokemon['types']),
                'height' => $pokemon['height'],
                'weight' => $pokemon['weight']
            ]
        ];
    }
    
    return ['success' => false, 'message' => 'Pokémon no encontrado'];
}

// Si se llama directamente con AJAX
if (basename($_SERVER['PHP_SELF']) == 'pokemon_api.php' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    if ($_GET['action'] == 'list') {
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
        echo json_encode(getPokemonList($limit));
    } elseif ($_GET['action'] == 'details' && isset($_GET['name'])) {
        echo json_encode(getPokemonDetails($_GET['name']));
    }
}
?>