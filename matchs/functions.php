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

    function addMatch(PDO $linkpdo, array $data) {
        $date = $data['date'];
        $adversaire = $data['adversaire'];
        $domext = $data['domext'];
        $s1e = $data['s1e'] ?? 0;
        $s1a = $data['s1a'] ?? 0;
        $s2e = $data['s2e'] ?? 0;
        $s2a = $data['s2a'] ?? 0;
        $s3e = $data['s3e'] ?? 0;
        $s3a = $data['s3a'] ?? 0;
        $s4e = $data['s4e'] ?? 0;
        $s4a = $data['s4a'] ?? 0;
        $s5e = $data['s5e'] ?? 0;
        $s5a = $data['s5a'] ?? 0;
    
        try {
            // Démarrer une transaction
            $linkpdo->beginTransaction();
    
            // Insertion du match
            $matchQuery = $linkpdo->prepare("
                INSERT INTO Rencontre (Date_rencontre, Nom_équipe, Domicile_ou_exterieur, 
                                      Set1_equipe, Set1_adverse, Set2_equipe, Set2_adverse, 
                                      Set3_equipe, Set3_adverse, Set4_equipe, Set4_adverse, 
                                      Set5_equipe, Set5_adverse) 
                VALUES (:dateM, :adversaire, :domext, 
                        :s1e, :s1a, :s2e, :s2a, 
                        :s3e, :s3a, :s4e, :s4a, 
                        :s5e, :s5a)");
    
            $matchQuery->execute([
                'dateM' => $date, 'adversaire' => $adversaire, 'domext' => $domext, 
                's1e' => $s1e, 's1a' => $s1a, 's2e' => $s2e, 's2a' => $s2a, 
                's3e' => $s3e, 's3a' => $s3a, 's4e' => $s4e, 's4a' => $s4a, 
                's5e' => $s5e, 's5a' => $s5a
            ]);
    
            // Récupération de l'ID du match fraîchement inséré
            $idMatch = $linkpdo->lastInsertId();
    
            // Associer chaque rôle avec l'ID du joueur correspondant
            $joueurs = [
                'avant_droit' => $data['avd'],
                'avant_centre' => $data['avc'],
                'avant_gauche' => $data['avg'],
                'arriere_droit' => $data['ard'],
                'arriere_gauche' => $data['arg'],
                'libero' => $data['lib'],
                'remp1' => $data['r1'],
                'remp2' => $data['r2'],
                'remp3' => $data['r3'],
                'remp4' => $data['r4'],
                'remp5' => $data['r5'],
                'remp6' => $data['r6']
            ];
    
            // Insertion des participations des joueurs
            foreach ($joueurs as $role => $idJoueur) {
                if (!empty($idJoueur)) { // Vérifie si l'ID joueur est bien défini
                    $titulaire = (strpos($role, 'remp') === false) ? 'titulaire' : 'remplaçant';
    
                    $participQuery = $linkpdo->prepare("
                        INSERT INTO Participer (IdJoueur, IdRencontre, Rôle, Titulaire_ou_remplacant, Note) 
                        VALUES (:idJoueur, :idMatch, :role, :titulaire, :note)");
    
                    $participQuery->execute([
                        'idJoueur' => $idJoueur,
                        'idMatch' => $idMatch,
                        'role' => $role,
                        'titulaire' => $titulaire,
                        'note' => null
                    ]);
                }
            }
    
            // Valider la transaction
            $linkpdo->commit();
    
            return "Match et participations ajoutés avec succès !";
        } catch (Exception $e) {
            // En cas d'erreur, annuler la transaction
            $linkpdo->rollBack();
            echo "Transaction annulée !\n";
            return "Erreur : " . $e->getMessage();
        }
    }
    
    

?>