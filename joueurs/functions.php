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

    function modifJoueur(PDO $linkpdo, int $licence, string $nom, string $prenom, string $naissance, int $taille, float $poids, string $commentaire, string $statut) {
        $update = $linkpdo->prepare("UPDATE Joueur SET Commentaire=:c, Nom=:nom, Prénom=:prenom, Date_de_naissance=:naissance, Taille=:taille, Poids=:poids, Statut=:statut WHERE Numéro_de_license=:l");
        $update->execute([
            ':c' => $commentaire,
            ':nom' => $nom,
            ':prenom' => $prenom,
            ':naissance' => $naissance,
            ':taille' => $taille,
            ':poids' => $poids,
            ':statut' => $statut,
            ':l' => $licence
        ]);
    }

?>