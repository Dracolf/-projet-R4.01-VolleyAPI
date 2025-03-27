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

    function getMatchEquipe(PDO $linkpdo, int $id) : array {
        $query = $linkpdo->prepare("SELECT * FROM Participer WHERE IdRencontre = :id");
        $query->execute(['id' => $id]);
        return $query->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    function validateVolleyballScores(array $scores): array {
        // Fonction de validation d'un set
        $validateSet = function($teamScore, $oppScore, $isTieBreak = false) {
            $maxPoints = $isTieBreak ? 15 : 25;
            
            // Scores négatifs
            if ($teamScore < 0 || $oppScore < 0) {
                return [false, "Les scores ne peuvent pas être négatifs"];
            }
            
            // Scores absolument trop élevés
            if ($teamScore > $maxPoints + 2 || $oppScore > $maxPoints + 2) {
                return [false, "Un score ne peut dépasser ".($maxPoints + 2)." points"];
            }
            
            // Si un score dépasse le maximum
            if ($teamScore > $maxPoints || $oppScore > $maxPoints) {
                if (abs($teamScore - $oppScore) != 2) {
                    return [false, "Il faut exactement 2 points d'écart quand un score dépasse $maxPoints"];
                }
                if ($teamScore > $maxPoints && $oppScore > $maxPoints) {
                    return [false, "Un seul score peut dépasser $maxPoints"];
                }
            }
            
            return [true, ""];
        };

        // Validation des sets 1-4
        for ($i = 1; $i <= 4; $i++) {
            [$valid, $message] = $validateSet($scores["s{$i}e"], $scores["s{$i}a"], false);
            if (!$valid) {
                return [false, "Set $i: $message"];
            }
        }

        // Validation du set 5
        [$valid, $message] = $validateSet($scores['s5e'], $scores['s5a'], true);
        if (!$valid) {
            return [false, "Set 5: $message"];
        }

        // Vérification du nombre de sets gagnés
        $countSetsWon = function($teamScore, $oppScore, $isTieBreak = false) {
            $winningPoints = $isTieBreak ? 15 : 25;
            return ($teamScore >= $winningPoints && ($teamScore - $oppScore) >= 2) ? 1 : 0;
        };

        $teamSetsWon = $countSetsWon($scores['s1e'], $scores['s1a'], false) 
                    + $countSetsWon($scores['s2e'], $scores['s2a'], false)
                    + $countSetsWon($scores['s3e'], $scores['s3a'], false)
                    + $countSetsWon($scores['s4e'], $scores['s4a'], false)
                    + $countSetsWon($scores['s5e'], $scores['s5a'], true);

        $oppSetsWon = $countSetsWon($scores['s1a'], $scores['s1e'], false)
                    + $countSetsWon($scores['s2a'], $scores['s2e'], false)
                    + $countSetsWon($scores['s3a'], $scores['s3e'], false)
                    + $countSetsWon($scores['s4a'], $scores['s4e'], false)
                    + $countSetsWon($scores['s5a'], $scores['s5e'], true);

        if ($teamSetsWon > 3 || $oppSetsWon > 3) {
            return [false, "Une équipe ne peut pas gagner plus de 3 sets"];
        }

        // Vérification de la cohérence du match terminé
        if (($teamSetsWon == 3 || $oppSetsWon == 3) && 
            ($teamSetsWon + $oppSetsWon) < 5 && 
            ($scores['s5e'] > 0 || $scores['s5a'] > 0)) {
            return [false, "Incohérence: un 5ème set est renseigné alors que le match était déjà terminé"];
        }

        return [true, "Scores valides"];
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

        // Récupération des scores
        $scores = [
            's1e' => $s1e ?? 0,
            's1a' => $s1a ?? 0,
            's2e' => $s2e ?? 0,
            's2a' => $s2a ?? 0,
            's3e' => $s3e ?? 0,
            's3a' => $s3a ?? 0,
            's4e' => $s4e ?? 0,
            's4a' => $s4a ?? 0,
            's5e' => $s5e ?? 0,
            's5a' => $s5a ?? 0
        ];

        // Validation des scores
        [$valid, $message] = validateVolleyballScores($scores);
        if (!$valid) {
            throw new Exception($message);
        }
    
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
                    $titulaire = (strpos($role, 'remp') === false) ? 'Titulaire' : 'Remplaçant';
    
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
            return "Erreur : " . $e->getMessage();
        }
    }

    function deleteMatch(PDO $linkpdo, int $id) {
        if (getMatch($linkpdo, $id)==[]) {
            return false;
        }
        $supprParticiper = $linkpdo->prepare("DELETE FROM Participer WHERE IdRencontre = :id");
        $supprRencontre = $linkpdo->prepare("DELETE FROM Rencontre WHERE IdRencontre = :id");
        $supprParticiper->execute(['id' => $id]);
        $supprRencontre->execute(['id' => $id]);
        return true;
    }

    function updateMatchInfos(PDO $linkpdo, int $id, string $date, string $adversaire, string $domext) {
        if (getMatch($linkpdo, $id)==[]) {
            return false;
        }
        try {
            $query = $linkpdo->prepare("UPDATE Rencontre SET Date_rencontre = :dateR, Nom_équipe = :adversaire, Domicile_ou_exterieur = :domext WHERE IdRencontre = :id");
            $query->execute(['dateR' => $date, 'adversaire' => $adversaire, 'domext' => $domext, 'id' => $id]);
            return true;

        } catch (Exception $e) {
            // En cas d'erreur, annuler la transaction
            $linkpdo->rollBack();
            return "Erreur : " . $e->getMessage();
        }
    }

    function updateMatchSetsEtNotes(PDO $linkpdo, int $idRencontre, array $data) 
    {
        if (getMatch($linkpdo, $idRencontre) == []) {
            return false;
        }

        // 1) ---- Récupération des données ----
        // On considère que $data contient les clés suivantes :
        // [id, s1e, s1a, s2e, s2a, s3e, s3a, s4e, s4a, s5e, s5a,
        //  noteAVG, noteAVC, noteAVD, noteARG, noteARD, noteLIB,
        //  noteR1, noteR2, noteR3, noteR4, noteR5, noteR6]

        // Scores
        $s1e = isset($data['s1e']) ? (int)$data['s1e'] : 0;
        $s1a = isset($data['s1a']) ? (int)$data['s1a'] : 0;
        $s2e = isset($data['s2e']) ? (int)$data['s2e'] : 0;
        $s2a = isset($data['s2a']) ? (int)$data['s2a'] : 0;
        $s3e = isset($data['s3e']) ? (int)$data['s3e'] : 0;
        $s3a = isset($data['s3a']) ? (int)$data['s3a'] : 0;
        $s4e = isset($data['s4e']) ? (int)$data['s4e'] : 0;
        $s4a = isset($data['s4a']) ? (int)$data['s4a'] : 0;
        $s5e = isset($data['s5e']) ? (int)$data['s5e'] : 0;
        $s5a = isset($data['s5a']) ? (int)$data['s5a'] : 0;

        // Récupération des scores
        $scores = [
            's1e' => $s1e ?? 0,
            's1a' => $s1a ?? 0,
            's2e' => $s2e ?? 0,
            's2a' => $s2a ?? 0,
            's3e' => $s3e ?? 0,
            's3a' => $s3a ?? 0,
            's4e' => $s4e ?? 0,
            's4a' => $s4a ?? 0,
            's5e' => $s5e ?? 0,
            's5a' => $s5a ?? 0
        ];

        // Validation des scores
        [$valid, $message] = validateVolleyballScores($scores);
        if (!$valid) {
            throw new Exception($message);
        }

        // Notes : on associe chaque rôle à la note reçue
        // (ici, on suppose 12 rôles possibles : AVG, AVC, AVD, ARG, ARD, LIB, R1..R6)
        // La table "Participer" stocke la note dans un seul champ "Note" 
        // et identifie le joueur par "Rôle" (ou un IdJoueur + Rôle).
        
        // On regroupe les notes dans un tableau clé => valeur
        // pour faire un traitement en boucle ensuite.
        $notesParRole = [
            'avant_gauche' => isset($data['noteAVG']) ? (int)$data['noteAVG'] : 0,
            'avant_centre' => isset($data['noteAVC']) ? (int)$data['noteAVC'] : 0,
            'avant_droit' => isset($data['noteAVD']) ? (int)$data['noteAVD'] : 0,
            'arriere_gauche' => isset($data['noteARG']) ? (int)$data['noteARG'] : 0,
            'arriere_droit' => isset($data['noteARD']) ? (int)$data['noteARD'] : 0,
            'libero' => isset($data['noteLIB']) ? (int)$data['noteLIB'] : 0,
            'remp1'  => isset($data['noteR1'])  ? (int)$data['noteR1']  : 0,
            'remp2'  => isset($data['noteR2'])  ? (int)$data['noteR2']  : 0,
            'remp3'  => isset($data['noteR3'])  ? (int)$data['noteR3']  : 0,
            'remp4'  => isset($data['noteR4'])  ? (int)$data['noteR4']  : 0,
            'remp5'  => isset($data['noteR5'])  ? (int)$data['noteR5']  : 0,
            'remp6'  => isset($data['noteR6'])  ? (int)$data['noteR6']  : 0
        ];

        // Vérification de l’ID de rencontre
        if ($idRencontre <= 0) {
            throw new Exception("ID de rencontre invalide.");
        }

        // 3) ---- Mise à jour en base ----
        // On utilise une transaction pour être sûr que si quelque chose échoue (scores ou notes),
        // rien n’est enregistré en base.

        try {
            $linkpdo->beginTransaction();

            // ---- a) Mise à jour des scores de sets dans "Rencontre" ----
            // Adapte selon tes colonnes / structure
            $sqlRencontre = "
                UPDATE Rencontre
                SET Set1_equipe   = :s1e,
                    Set1_adverse  = :s1a,
                    Set2_equipe   = :s2e,
                    Set2_adverse  = :s2a,
                    Set3_equipe   = :s3e,
                    Set3_adverse  = :s3a,
                    Set4_equipe   = :s4e,
                    Set4_adverse  = :s4a,
                    Set5_equipe   = :s5e,
                    Set5_adverse  = :s5a
                WHERE IdRencontre   = :idRencontre
            ";
            $stmt = $linkpdo->prepare($sqlRencontre);
            $stmt->bindValue(':s1e', $s1e, PDO::PARAM_INT);
            $stmt->bindValue(':s1a', $s1a, PDO::PARAM_INT);
            $stmt->bindValue(':s2e', $s2e, PDO::PARAM_INT);
            $stmt->bindValue(':s2a', $s2a, PDO::PARAM_INT);
            $stmt->bindValue(':s3e', $s3e, PDO::PARAM_INT);
            $stmt->bindValue(':s3a', $s3a, PDO::PARAM_INT);
            $stmt->bindValue(':s4e', $s4e, PDO::PARAM_INT);
            $stmt->bindValue(':s4a', $s4a, PDO::PARAM_INT);
            $stmt->bindValue(':s5e', $s5e, PDO::PARAM_INT);
            $stmt->bindValue(':s5a', $s5a, PDO::PARAM_INT);
            $stmt->bindValue(':idRencontre', $idRencontre, PDO::PARAM_INT);

            if (!$stmt->execute()) {
                throw new Exception("Échec lors de la mise à jour des scores de la rencontre.");
            }

            // ---- b) Mise à jour des notes dans "Participer" ----
            // On met à jour, pour chaque rôle, la colonne "Note".
            // On suppose que l’enregistrement existe déjà dans "Participer" (sinon, il faudrait l’insérer).

            $sqlNote = "
                UPDATE Participer
                SET Note = :note
                WHERE IdRencontre = :idRencontre
                AND Rôle        = :role
            ";
            $stmtNote = $linkpdo->prepare($sqlNote);

            foreach ($notesParRole as $role => $note) {
                if ($note >= 0 && $note <= 5) {
                    // On applique l’UPDATE pour chaque rôle
                    $stmtNote->bindValue(':note',        $note,         PDO::PARAM_INT);
                    $stmtNote->bindValue(':idRencontre', $idRencontre,  PDO::PARAM_INT);
                    $stmtNote->bindValue(':role',        $role,         PDO::PARAM_STR);

                    if (!$stmtNote->execute()) {
                        throw new Exception("Échec lors de la mise à jour de la note pour le rôle '$role'.");
                    }
                } else {
                    throw new Exception("Une note ne peut être comprise qu'entre 0 et 5 inclus");
                }
                
            }

            // Tout s’est bien passé : on valide la transaction
            $linkpdo->commit();

        } catch (Exception $e) {
            // En cas d’erreur, on annule la transaction et on relance l’exception
            $linkpdo->rollBack();
            throw $e;
        }

        // 4) ---- Retour en cas de succès ----
        return true;
    }

    function updateMatchEquipe(PDO $linkpdo, int $id, int $avg, int $avc, int $avd, int $arg, int $ard, int $lib, int $r1, int $r2, int $r3, int $r4, int $r5, int $r6) {
        if (getMatch($linkpdo, $id) == []) {
            return false;
        }
        try {
            $linkpdo->beginTransaction();

            $supprParticiper = $linkpdo->prepare("DELETE FROM Participer WHERE IdRencontre = :id");
            $supprParticiper->execute(['id' => $id]);
            $joueurs = [
                $avg => 'avant_gauche',
                $avc => 'avant_centre',
                $avd => 'avant_droit',
                $arg => 'arriere_gauche',
                $ard => 'arriere_droit',
                $lib => 'libero',
                $r1  => 'remp1',
                $r2  => 'remp2',
                $r3  => 'remp3',
                $r4  => 'remp4',
                $r5  => 'remp5',
                $r6  => 'remp6'
            ];
            foreach($joueurs as $idJ => $role) {
                $titulaire = (strpos($role, 'remp') === false) ? 'Titulaire' : 'Remplaçant';
                $newJoueur = $linkpdo->prepare("INSERT INTO Participer(IdJoueur, IdRencontre, Rôle, Titulaire_ou_remplacant, Note) VALUES 
                (:idJ, :id, :role, :titu, 0)");
                $newJoueur->execute(['idJ' => $idJ, 'id' => $id, 'role' => $role, 'titu' => $titulaire]);
            }

            $linkpdo->commit();
            return true;
        } catch (Exception $e) {
            // En cas d’erreur, on annule la transaction et on relance l’exception
            $linkpdo->rollBack();
            return "Erreur : " . $e->getMessage();
        }
        
    }

    
    

?>
