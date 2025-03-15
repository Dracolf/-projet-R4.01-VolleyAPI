<?php

    function getJoueur(PDO $linkpdo, int $id) : array {
        $q = $linkpdo->prepare("SELECT * FROM Joueur WHERE IdJoueur = :id ORDER BY Statut='Absent' ASC, Statut='Blessé' ASC, Statut='Actif' ASC, Nom ASC");
        $q->execute(['id' => $id]);
        return $q->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    function searchJoueurs(PDO $linkpdo, string $query) : array {
        $q = $linkpdo->prepare("
            SELECT * FROM Joueur
            WHERE Numéro_de_license LIKE :query
               OR Nom LIKE :query
               OR Prénom LIKE :query
            ORDER BY Nom ASC
        ");
        $q->execute(['query' => "%$query%"]);
        return $q->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    function getAllJoueurs(PDO $linkpdo) : array {
        $q = $linkpdo->prepare("SELECT * FROM Joueur ORDER BY Statut='Absent' ASC, Statut='Blessé' ASC, Statut='Actif' ASC, Nom ASC");
        $q->execute();
        return $q->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    function addJoueur(PDO $linkpdo, int $licence, string $nom, string $prenom, string $naissance, int $taille, float $poids, ?string $commentaire, string $statut) {
        try {
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
            return true;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { 
                return "Le numéro de licence $licence est déjà utilisé.";
            }
            return "Erreur interne du serveur.";
        }
    }

    function modifJoueur(PDO $linkpdo, int $id, int $licence, string $nom, string $prenom, string $naissance, int $taille, float $poids, ?string $commentaire, string $statut) {
        if (getJoueur($linkpdo, $id) ==[]) {
            return false;
        }

        try {
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
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { 
                return "Le numéro de licence $licence est déjà utilisé.";
            }
            return "Erreur interne du serveur.";
        }
    }

    function deleteJoueur(PDO $linkpdo, int $id) {
        if (getJoueur($linkpdo, $id) == [] ) {
            return false;
        }
        $searchMatchs = $linkpdo->prepare("SELECT COUNT(*) as nbMatchs FROM Participer WHERE IdJoueur = :id");
        $searchMatchs->execute(['id' => $id]);
        $nbMatchs = $searchMatchs->fetch(PDO::FETCH_ASSOC);
        if ($nbMatchs['nbMatchs'] > 0) {
            return "Le joueur ne peut pas être supprimé car il a déjà participé à un match";
        }
        $delete = $linkpdo->prepare("DELETE FROM Joueur WHERE IdJoueur = :id");
        $delete->execute(['id' => $id]);
        return true;
    }

?>