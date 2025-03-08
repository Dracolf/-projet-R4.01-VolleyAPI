<?php
    function getDBConnection() {
        $server = 'sql312.infinityfree.com';
        $login = 'if0_37676623';
        $mdp = 'theadmin31';
        $db = 'if0_37676623_gestionvolley';

        try {
            $pdo = new PDO("mysql:host=$server;dbname=$db;charset=utf8mb4", $login, $mdp);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }
?>
