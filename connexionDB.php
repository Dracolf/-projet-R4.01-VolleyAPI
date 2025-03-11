<?php
    function getDBConnection() {
        $server = "mysql-volleycoachpro.alwaysdata.net";
        $login = "403542";
        $mdp = "Iutinfo!";
        $db = "volleycoachpro_bd";

        try {
            $pdo = new PDO("mysql:host=$server;dbname=$db;charset=utf8mb4", $login, $mdp);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }
?>
