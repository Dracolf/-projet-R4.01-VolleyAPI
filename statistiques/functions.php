<?php

    date_default_timezone_set('Europe/Paris');
    $dateToday = date("Y-m-d H:i:s");

    function getNbMatchsJoues(PDO $linkpdo): array {
        global $dateToday;
        $rencontresAvecScore = $linkpdo->prepare("
        SELECT COUNT(*) as nb 
        FROM Rencontre 
        WHERE (Set1_equipe + Set2_equipe + Set3_equipe + Set4_equipe + Set5_equipe) > 0 
          AND (Set1_adverse + Set2_adverse + Set3_adverse + Set4_adverse + Set5_adverse) > 0
          AND Date_rencontre < :dateToday ;
        ");
        $rencontresAvecScore->execute(['dateToday' => $dateToday]);
        return $rencontresAvecScore->fetch(PDO::FETCH_ASSOC) ?: []; // Retourne un tableau vide si aucun résultat
    }

    function getStats(PDO $linkpdo): array {
        global $dateToday;
        $MEGAREQUETE = $linkpdo->prepare("
            SELECT 
            J.Nom, 
            J.Prénom,
            J.Statut,
            COALESCE((
                SELECT P1.Rôle 
                FROM Participer P1
                WHERE P1.IdJoueur = J.IdJoueur
                AND P1.Titulaire_ou_remplacant = 'Titulaire'
                GROUP BY P1.Rôle
                ORDER BY COUNT(*) DESC, P1.Rôle ASC
                LIMIT 1
            ), '/') AS PostePréféré,
            COUNT(CASE WHEN P.Titulaire_ou_remplacant = 'Titulaire' AND Date_rencontre < :dateToday THEN 1 END) AS NbTitularisations,
            COUNT(CASE WHEN P.Titulaire_ou_remplacant = 'Remplaçant' AND Date_rencontre < :dateToday THEN 1 END) AS NbRemplacements,
            COALESCE(ROUND(AVG(P.Note), 2), '/') AS NoteMoyenne,
            CASE 
                WHEN COUNT(CASE WHEN Date_rencontre < :dateToday THEN 1 END) = 0 THEN '/'
                ELSE ROUND(
                    (SUM(
                        CASE 
                            WHEN (
                                (Set1_equipe > Set1_adverse) + 
                                (Set2_equipe > Set2_adverse) + 
                                (Set3_equipe > Set3_adverse) + 
                                (Set4_equipe > Set4_adverse) + 
                                (Set5_equipe > Set5_adverse)
                            ) >= 3 
                            AND P.IdJoueur IS NOT NULL 
                            AND Date_rencontre < :dateToday
                        THEN 1 ELSE 0 END
                    ) * 100.0) 
                    / COUNT(
                        CASE 
                            WHEN P.IdJoueur IS NOT NULL 
                            AND Date_rencontre < :dateToday 
                            THEN 1 ELSE NULL END
                    ), 
                    2
                )
            END AS PourcentageVictoires
            FROM 
                Joueur J
            LEFT JOIN 
                Participer P ON J.IdJoueur = P.IdJoueur
            LEFT JOIN 
                Rencontre R ON P.IdRencontre = R.IdRencontre
            GROUP BY 
                J.IdJoueur, J.Nom, J.Prénom, J.Statut
            ORDER BY 
                J.Statut = 'Absent' ASC, J.Statut = 'Blessé' ASC, J.Statut = 'Actif' ASC, J.Nom ASC
        ");
        $MEGAREQUETE->execute(['dateToday' => $dateToday, 'nbMatchsJoues' => getNbMatchsJoues($linkpdo)['nb']]);
        return $MEGAREQUETE->fetchAll(PDO::FETCH_ASSOC);
    }

    function getNbMatchsWin(PDO $linkpdo): array {
        global $dateToday;
        $reqMatchsWin = $linkpdo->prepare("SELECT COUNT(*) as nb FROM Rencontre WHERE (
        (Set1_equipe > Set1_adverse) + 
        (Set2_equipe > Set2_adverse) + 
        (Set3_equipe > Set3_adverse) + 
        (Set4_equipe > Set4_adverse) + 
        (Set5_equipe > Set5_adverse)
        ) >= 3 AND Date_rencontre < :dateToday");
        $reqMatchsWin->execute(['dateToday' => $dateToday]);
        return $reqMatchsWin->fetch(PDO::FETCH_ASSOC) ?: []; // Retourne un tableau vide si aucun résultat
    }

    function getNbMatchsLoose(PDO $linkpdo): array {
        global $dateToday;
        $reqMatchsLoose = $linkpdo->prepare("SELECT COUNT(*) as nb FROM Rencontre WHERE (
        (Set1_equipe < Set1_adverse) + 
        (Set2_equipe < Set2_adverse) + 
        (Set3_equipe < Set3_adverse) + 
        (Set4_equipe < Set4_adverse) + 
        (Set5_equipe < Set5_adverse)
        ) >= 3 AND Date_rencontre < :dateToday");
        $reqMatchsLoose->execute(['dateToday' => $dateToday]);
        return $reqMatchsLoose->fetch(PDO::FETCH_ASSOC) ?: []; // Retourne un tableau vide si aucun résultat
    }

    function getAllStats(PDO $linkpdo) : array {
        $allData = ['nbMatchs' => getNbMatchsJoues($linkpdo)['nb'], 'nbWins' => getNbMatchsWin($linkpdo)['nb'], 'nbLooses' => getNbMatchsLoose($linkpdo)['nb'], 'joueursStats' => getStats($linkpdo)];
        return $allData;
    }

?>