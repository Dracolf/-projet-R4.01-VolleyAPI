<?php

    function getMatch(PDO $linkpdo, int $id) : array {
        $query = $linkpdo->prepare("SELECT * FROM Rencontre WHERE IdRencontre = :id ORDER BY Date_rencontre DESC");
        $query->execute(['id' => $id]);
        return $query->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    function getAllMatchs(PDO $linkpdo) : array {
        $query = $linkpdo->prepare("SELECT * FROM Rencontre ORDER BY Date_rencontre DESC");
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

?>