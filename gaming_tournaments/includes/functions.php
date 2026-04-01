<?php
require_once __DIR__ . '/config.php';

function getTournaments($limit = null) {
    global $pdo;
    
    try {
        $sql = "SELECT t.*, u.username as creator_name,
                (SELECT COUNT(*) FROM participants WHERE tournament_id = t.id) as participant_count
                FROM tournaments t
                LEFT JOIN users u ON t.created_by = u.id
                ORDER BY t.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . intval($limit);
        }
        
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error en getTournaments: " . $e->getMessage());
        return [];
    }
}

function getTournamentById($id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT t.*, u.username as creator_name
                               FROM tournaments t
                               LEFT JOIN users u ON t.created_by = u.id
                               WHERE t.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error en getTournamentById: " . $e->getMessage());
        return null;
    }
}

function createTournament($data) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO tournaments (name, game, description, start_date, end_date, max_participants, created_by)
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $result = $stmt->execute([
            $data['name'],
            $data['game'],
            $data['description'],
            $data['start_date'],
            $data['end_date'],
            $data['max_participants'],
            $_SESSION['user_id']
        ]);
        
        return $result;
    } catch (PDOException $e) {
        error_log("Error en createTournament: " . $e->getMessage());
        return false;
    }
}

function updateTournament($id, $data) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE tournaments 
                               SET name = ?, game = ?, description = ?, start_date = ?, end_date = ?, max_participants = ?
                               WHERE id = ?");
        
        return $stmt->execute([
            $data['name'],
            $data['game'],
            $data['description'],
            $data['start_date'],
            $data['end_date'],
            $data['max_participants'],
            $id
        ]);
    } catch (PDOException $e) {
        error_log("Error en updateTournament: " . $e->getMessage());
        return false;
    }
}

function deleteTournament($id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM tournaments WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        error_log("Error en deleteTournament: " . $e->getMessage());
        return false;
    }
}

function getParticipantsByTournament($tournament_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT p.*, u.username, u.email
                               FROM participants p
                               JOIN users u ON p.user_id = u.id
                               WHERE p.tournament_id = ?
                               ORDER BY p.registration_date");
        $stmt->execute([$tournament_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error en getParticipantsByTournament: " . $e->getMessage());
        return [];
    }
}

function addParticipant($tournament_id, $user_id, $pokemon_team = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO participants (tournament_id, user_id, pokemon_team)
                               VALUES (?, ?, ?)");
        return $stmt->execute([$tournament_id, $user_id, $pokemon_team]);
    } catch (PDOException $e) {
        error_log("Error en addParticipant: " . $e->getMessage());
        return false;
    }
}

function getUserStatistics($user_id) {
    global $pdo;
    
    $stats = [
        'tournaments_joined' => 0,
        'matches_won' => 0
    ];
    
    try {
        // Torneos participados
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM participants WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $stats['tournaments_joined'] = $stmt->fetchColumn();
        
        // Partidos ganados
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM matches WHERE winner_id IN 
                               (SELECT id FROM participants WHERE user_id = ?)");
        $stmt->execute([$user_id]);
        $stats['matches_won'] = $stmt->fetchColumn();
        
        return $stats;
    } catch (PDOException $e) {
        error_log("Error en getUserStatistics: " . $e->getMessage());
        return $stats;
    }
}
?>