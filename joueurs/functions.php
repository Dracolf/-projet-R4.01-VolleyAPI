<?php

    function getJoueur(PDO $linkpdo, int $id) : array {
        $q = $linkpdo->prepare("SELECT * FROM Joueur WHERE IdJoueur = :id ORDER BY Statut='Absent' ASC, Statut='Blessé' ASC, Statut='Actif' ASC, Nom ASC");
        $q->execute(['id' => $id]);
        return $q->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    function getAllJoueurs(PDO $linkpdo) : array {
        $q = $linkpdo->prepare("SELECT * FROM Joueur ORDER BY Statut='Absent' ASC, Statut='Blessé' ASC, Statut='Actif' ASC, Nom ASC");
        $q->execute();
        return $q->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    function addJoueur(PDO $linkpdo, int $licence, string $nom, string $prenom, string $naissance, int $taille, float $poids, ?string $commentaire, string $statut) {
        $insertQuery = $linkpdo->prepare("
            INSERT INTO Joueur (
                Numéro_de_license, Nom, Prénom, Date_de_naissance, Taille, Poids, Commentaire, Statut
            ) VALUES (
                :license, :nom, :prenom, :dnaiss, :taille, :poids, :comm, :statut
            )
        ");
        $insertQuery->execute([
            ':license' => $licence,
            ':nom' => $nom,
            ':prenom' => $prenom,
            ':dnaiss' => $naissance,
            ':taille' => $taille,
            ':poids' => $poids,
            ':comm' => $commentaire,
            ':statut' => $statut
        ]);
    }

    function modifJoueur(PDO $linkpdo, int $id, int $licence, string $nom, string $prenom, string $naissance, int $taille, float $poids, ?string $commentaire, string $statut) : bool {
        if (getJoueur($linkpdo, $id) ==[]) {
            return false;
        }
        $update = $linkpdo->prepare("UPDATE Joueur SET Commentaire=:c, Nom=:nom, Prénom=:prenom, Date_de_naissance=:naissance, Taille=:taille, Poids=:poids, Statut=:statut, Numéro_de_license=:l WHERE IdJoueur = :id");
        $update->execute([
            ':c' => $commentaire,
            ':nom' => $nom,
            ':prenom' => $prenom,
            ':naissance' => $naissance,
            ':taille' => $taille,
            ':poids' => $poids,
            ':statut' => $statut,
            ':l' => $licence,
            ':id' => $id,
        ]);
        return true;
    }

    function deleteJoueur(PDO $linkpdo, int $id) : bool {
        if (getJoueur($linkpdo, $id) == [] ) {
            return false;
        }
        $delete = $linkpdo->prepare("DELETE FROM Joueur WHERE IdJoueur = :id");
        $delete->execute(['id' => $id]);
        return true;
    }

?>