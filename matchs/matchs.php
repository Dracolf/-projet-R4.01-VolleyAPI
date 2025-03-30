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

    // Stockage du login de l'utilisateur dans une variable globale
    $userLogin = null;
    if (isset($data['login'])) {
        $userLogin = $data['login'];
    } else {
        // Si l'API d'authentification ne renvoie pas le login, on peut essayer de le décoder depuis le token
        $tokenParts = explode('.', $token);
        if (count($tokenParts) === 3) {
            $payload = json_decode(base64_decode($tokenParts[1]), true);
            if (isset($payload['login'])) {
                $userLogin = $payload['login'];
            }
        }
    }

    if (!$userLogin) {
        deliver_response(403, "Accès interdit : Impossible de déterminer l'utilisateur");
        exit();
    }

    $http_method = $_SERVER['REQUEST_METHOD'];
    switch ($http_method) {
        case "GET":
            if (isset($_GET['id'])) {
                if (isset($_GET['team'])) {
                    $data = getMatchEquipe($linkpdo, $_GET['id']);
                    if ($data == []) {
                        deliver_response(404, "Match inexistant");
                    } else {
                        deliver_response(200, "Requête GET réussie", $data);
                    }
                } else {
                    $data = getMatch($linkpdo, $_GET['id']);
                    if ($data == []) {
                        deliver_response(404, "Match inexistant");
                    } else {
                        deliver_response(200, "Requête GET réussie", $data);
                    }
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
            if ($userLogin == "coach" || $userLogin == "Coach") {
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
            } else {
                deliver_response(403, "Seul le coach peut ajouter une nouvelle rencontre");
            }
            
            break;

        case "PUT":
            if ($userLogin == "coach" || $userLogin == "Coach") {
                if (isset($_GET['id'])) {
                    $postedData = file_get_contents('php://input');
                    $data = json_decode($postedData, true);
                    if (isset($data['date'], $data['adversaire'], $data['domext'])) {
                        $result = updateMatchInfos($linkpdo, $_GET['id'], $data['date'], $data['adversaire'], $data['domext']);
                        if ($result === true) {
                            deliver_response(200, "Informations de la rencontre modifiées");
                        } else if ($result === false) {
                            deliver_response(404, "Match inexistant");
                        } else  {
                            deliver_response(500, $result);
                        }
                    } else if (isset($data['s1e'], $data['s1a'], $data['s2e'], $data['s2a'], $data['s3e'], $data['s3a'], $data['s4e'], $data['s4a'], $data['s5e'], $data['s5a'],
                    $data['noteAVG'], $data['noteAVC'], $data['noteAVD'], $data['noteARG'], $data['noteARD'], $data['noteLIB'], $data['noteR1'], $data['noteR2'], $data['noteR3'], 
                    $data['noteR4'], $data['noteR5'], $data['noteR6'])) {
                        $result = updateMatchSetsEtNotes($linkpdo, $_GET['id'], $data);
                        if($result) {
                            deliver_response(200, "Sets et notes modifiés");
                        } else {
                            deliver_response(404, "Match inexistant");
                        }
                    } else if (isset($data['avg'], $data['avc'], $data['avd'], $data['arg'], $data['ard'], $data['lib'], $data['r1'], $data['r2'], $data['r3'], $data['r4'], $data['r5'], $data['r6'] )) {
                        $result = updateMatchEquipe($linkpdo, $_GET['id'], $data['avg'], $data['avc'], $data['avd'], $data['arg'], $data['ard'], $data['lib'], $data['r1'], $data['r2'], 
                        $data['r3'], $data['r4'], $data['r5'], $data['r6']);
                        if ($result === false) {
                            deliver_response(404, "Match inexistant");
                        } else if ($result === true) {
                            deliver_response(200, "Equipe modifiée");
                        } else {
                            deliver_response(500, $result);
                        }
                    } else {
                        deliver_response(400, "Paramètres manquants dans la requête");
                    }
                } else {
                    deliver_response(400, "ID manquant");
                }
            } else {
                deliver_response(403, "Seul le coach peut modifier une rencontre");
            }
            break;

        case "DELETE":
            if ($userLogin == "coach" || $userLogin == "Coach") {
                if (isset($_GET['id'])) {
                    $result = deleteMatch($linkpdo, $_GET['id']);
                    if ($result) {
                        deliver_response(200, "Rencontre d'ID " . $_GET['id'] . " supprimée");
                    } else {
                        deliver_response(404, "Rencontre inexistante");
                    }
                } else {
                    deliver_response(400, "ID manquant");
                }
            } else {
                deliver_response(403, "Seul le coach peut supprimer une rencontre");
            }
            
            break;

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
