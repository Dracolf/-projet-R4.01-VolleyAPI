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

    // Récupération du token JWT depuis l'en-tête Authorization
    function get_bearer_token() {
        $headers = apache_request_headers();
        if (!isset($headers['Authorization'])) {
            return null;
        }
        if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            return $matches[1];
        }
        return null;
    }

    $token = get_bearer_token();

    if (!$token) {
        deliver_response(403, "Accès interdit : Aucun token fourni");
        exit();
    }

    // Vérification du token en envoyant une requête GET à l’API d'authentification
    $auth_url = "https://volleycoachpro.alwaysdata.net/authapi/";
    $ch = curl_init($auth_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);

    if ($http_code !== 200 || !isset($data['message'])) {
        deliver_response(403, "Accès interdit : Token invalide");
        exit();
    }

    $http_method = $_SERVER['REQUEST_METHOD'];
    switch ($http_method) {
        case "GET":
            if (isset($_GET['id'])) {
                $data = getJoueur($linkpdo, $_GET['id']);
                if ($data == []) {
                    deliver_response(404, "Joueur inexistant");
                } else {
                    deliver_response(200, "Requête GET réussie", $data);
                }
            } else {
                $data = getAllJoueurs($linkpdo);
                if ($data == []) {
                    deliver_response(404, "Aucun joueur trouvé");
                } else {
                    deliver_response(200, "Requête GET réussie", $data);
                }
            }  
            break;

        case "POST":
            $postedData = file_get_contents('php://input');
            $data = json_decode($postedData, true);
            if (isset($data['licence'], $data['nom'], $data['prenom'], $data['naissance'], $data['taille'], $data['poids'], $data['statut'])) {
                $result = addJoueur($linkpdo, $data['licence'], $data['nom'], $data['prenom'], $data['naissance'], $data['taille'], $data['poids'], $data['commentaire'] ?? null, $data['statut']);
                deliver_response(200, "Joueur ajouté");
            } else {
                deliver_response(400, "Paramètres manquants dans la requête");
            }
            break;

        case "PUT":
            if (isset($_GET['id'])) {
                $postedData = file_get_contents('php://input');
                $data = json_decode($postedData, true);
                if (isset($data['licence'], $data['nom'], $data['prenom'], $data['naissance'], $data['taille'], $data['poids'], $data['statut'])) {
                    $result = modifJoueur($linkpdo, $_GET['id'],$data['licence'], $data['nom'], $data['prenom'], $data['naissance'], $data['taille'], $data['poids'], $data['commentaire'] ?? null, $data['statut']);
                    if ($result) {
                        deliver_response(200, "Joueur modifié");
                    } else {
                        deliver_response(404, "Joueur inexistant");
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
                $data = deleteJoueur($linkpdo, $_GET['id']);
                if ($data) {
                    deliver_response(200, "Joueur d'ID " . $_GET['id'] . " supprimé");
                } else {
                    deliver_response(404, "Joueur inexistant");
                }
            } else {
                deliver_response(400, "ID manquant");
            }
            
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
