<?php
    require_once '../connexionDB.php'; // Inclusion de la connexion BDD
    require_once 'functions.php'; // Inclusion des fonctions SQL
    $linkpdo = getDBconnection();

    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit();
    }

    $http_method = $_SERVER['REQUEST_METHOD'];
    switch ($http_method) {
        case "GET":
            if (isset($_GET['id'])) {
                $data = getMatch($linkpdo, $_GET['id']);
                if ($data == []) {
                    deliver_response(404, "Match inexistant");
                } else {
                    deliver_response(200, "Requête GET réussie", $data);
                }
            } else {
                $data = getAllMatchs($linkpdo);
                if ($data ==[]) {
                    deliver_response(404, "Aucun match trouvé");
                }
                deliver_response(200, "Requête GET réussie", $data);
            }
            break;
          
        case "POST":
            $postedData = file_get_contents('php://input');
            $data = json_decode($postedData, true);
            if (isset($data['date'], $data['adversaire'], $data['domext'], $data['avd'], $data['avc'], $data['avg'], $data['ard'], $data['arg'], $data['lib'], 
            $data['r1'], $data['r2'], $data['r3'], $data['r4'], $data['r5'], $data['r6'])) {
                $result = addMatch($linkpdo, $data);
                if ($result === "Match et participations ajoutés avec succès !") {
                    deliver_response(201, $result);
                } else {
                    deliver_response(500, $result);
                }
            } else {
                deliver_response(400, "Paramètres manquants dans la requête");
            }
            break;
        /*
        case "PATCH":
            if (isset($_GET['id'])) {
                $postedData = file_get_contents('php://input');
                $data = json_decode($postedData, true);
                $result = patchChuckFact($linkpdo, $_GET['id'], $data['phrase'] ?? null, $data['vote'] ?? null, $data['faute'] ?? null, $data['signalement'] ?? null);
                if ($result == []) {
                    deliver_response(404, "Phrase inexistante");
                } else {
                    deliver_response(200, "Phrase modifiée", $result);
                }
            } else {
                deliver_response(400, "ID manquant");
            }
            break;

        case "PUT":
            if (isset($_GET['id'])) {
                $postedData = file_get_contents('php://input');
                $data = json_decode($postedData, true);
                if (isset($data['phrase'], $data['vote'], $data['faute'], $data['signalement'])) {
                    $result = putChuckFact($linkpdo, $_GET['id'], $data['phrase'], $data['vote'], $data['faute'], $data['signalement']);
                    if ($result == []) {
                        deliver_response(404, "Phrase inexistante");
                    } else {
                        deliver_response(200, "Phrase modifiée", $result);
                    }
                } else {
                    deliver_response(400, "Paramètres manquants dans la requête");
                }
            } else {
                deliver_response(400, "ID manquant");
            }
            break;

        case "DELETE":
            if (isset($_GET['id'])) {
                $result = 
                if ($result) {
                    deliver_response(200, "Phrase d'ID " . $_GET['id'] . " supprimée");
                } else {
                    deliver_response(404, "Phrase inexistante");
                }
            } else {
                deliver_response(400, "ID manquant");
            }
            break;
*/
        default:
            deliver_response(405, "Méthode non autorisée");
            break;
    }

    function deliver_response($status_code, $status_message, $data = null) {
        http_response_code($status_code);
        header("Content-Type: application/json; charset=utf-8");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");

        $response = [
            'status_code' => $status_code,
            'status_message' => $status_message,
            'data' => $data
        ];
        
        echo json_encode($response);
        exit();
    }
?>
