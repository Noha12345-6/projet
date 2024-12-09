<?php
include 'db.php';
header('Content-Type: application/json');


function getUserFromApi($user_id) {
    $url = "https://670ed5b73e7151861655eaa3.mockapi.io/Stagiaire/" . $user_id;
    $response = @file_get_contents($url);
    return $response ? json_decode($response, true) : null;
}


$uriSegments = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
$lastSegment = end($uriSegments); 
$id = is_numeric($lastSegment) ? (int)$lastSegment : null;


$data = json_decode(file_get_contents("php://input"), true);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $data['user_id'] ?? null;
    $titre = $data['titre'] ?? null;
    $description = $data['description'] ?? null;

    if ($user_id && $titre && $description) {
        $user = getUserFromApi($user_id);

        if ($user) {
            $statut = 'en attente'; 
            $sql = "INSERT INTO demandes (user_id, titre, description, statut) VALUES (:user_id, :titre, :description, :statut)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $user_id,
                ':titre' => $titre,
                ':description' => $description,
                ':statut' => $statut
            ]);

            echo json_encode(['message' => 'Demande ajoutée avec succès']);
        } else {
            echo json_encode(['error' => 'Utilisateur non trouvé']);
        }
    } else {
        echo json_encode(['error' => 'Données manquantes']);
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($id) { 
        $sql = "SELECT * FROM demandes WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            echo json_encode($result);
        } else {
            echo json_encode(['error' => 'Demande non trouvée']);
        }
    } else { 
        $sql = "SELECT * FROM demandes";
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($results);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
   
    $id = basename($_SERVER['REQUEST_URI']); 

   
    if (!$id || !is_numeric($id)) {
        echo json_encode(['error' => 'ID invalide ou manquant']);
        exit;
    }

   
    $data = json_decode(file_get_contents("php://input"), true);

   
    $new_statut = $data['statut'] ?? null;


    $valid_statuts = ['en attente', 'approuvée', 'rejetée'];
    if (!$new_statut || !in_array($new_statut, $valid_statuts)) {
        echo json_encode(['error' => 'Statut invalide. Les valeurs valides sont : en attente, approuvée, rejetée.']);
        exit;
    }

    
    $sql = "UPDATE demandes SET statut = :statut WHERE id = :id";
    $stmt = $pdo->prepare($sql);

    try {
      
        $stmt->execute([
            ':statut' => $new_statut,
            ':id' => $id
        ]);

      
        if ($stmt->rowCount() > 0) {
            echo json_encode(['message' => 'Statut mis à jour avec succès']);
        } else {
            echo json_encode(['error' => 'Aucune mise à jour effectuée. Vérifiez que l\'ID existe.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Erreur lors de la mise à jour : ' . $e->getMessage()]);
    }
} // Action : DELETE (Supprimer une demande)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if (!$id) {
        echo json_encode(['error' => 'ID invalide ou manquant']);
        exit;
    }

    $sql = "DELETE FROM demandes WHERE id = :id";
    $stmt = $pdo->prepare($sql);

    try {
        $stmt->execute([':id' => $id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['message' => 'Demande supprimée avec succès']);
        } else {
            echo json_encode(['error' => 'Demande non trouvée ou déjà supprimée']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Erreur lors de la suppression : ' . $e->getMessage()]);
    }
}
?>