-- ============================================================
-- IUSAA - Notes d'un etudiant par semestre (LECTURE SEULE)
-- Base : lmd
-- ============================================================
SET @mat = _utf8mb4'20010225' COLLATE utf8mb4_unicode_ci;

-- 1) DETAIL : une ligne par note saisie
SELECT
    s.Lib_Semestre                         AS semestre,
    aa.ANNEE_ACAD                          AS annee_acad,
    c.Nom_Classe                           AS classe,
    u.codeUe                               AS code_ue,
    u.libelleUe                            AS ue,
    ec.codeEcu                             AS code_ecue,
    ec.libelleEcu                          AS ecue,
    te.libTypEval                          AS type_eval,
    ev.dateEval                            AS date_eval,
    o.Note                                 AS note,
    o.login                                AS saisie_par
FROM lmd.obtenir o
JOIN lmd.evaluation      ev ON ev.numeroEval      = o.numeroEval
JOIN lmd.attr_ue_classe  a  ON a.Id_Attr_ue_Sem   = ev.Id_Attr_ue_Sem
JOIN lmd.classe          c  ON c.id_S_Gr_Spe      = a.id_S_Gr_Spe
JOIN lmd.semestre        s  ON s.numeroSemestre   = c.numeroSemestre
JOIN lmd.anneeacademique aa ON aa.id_Annee_Acad   = a.id_Annee_Acad
JOIN lmd.ue              u  ON u.codeUe           = a.codeUe
JOIN lmd.ecue            ec ON ec.codeEcu         = ev.codeEcu
LEFT JOIN lmd.typeevaluation te ON te.codTypEval  = ev.codTypEval
WHERE o.numeroInscription = @mat
  AND s.numeroSemestre IN (1, 2, 3, 4)
ORDER BY s.numeroSemestre, aa.ANNEE_ACAD, u.codeUe, ec.codeEcu, ev.dateEval;

-- 2) RESUME : nombre de notes par semestre (pour controler qu'il n'en manque pas)
SELECT s.Lib_Semestre AS semestre, aa.ANNEE_ACAD AS annee_acad, COUNT(*) AS nb_notes
FROM lmd.obtenir o
JOIN lmd.evaluation     ev ON ev.numeroEval    = o.numeroEval
JOIN lmd.attr_ue_classe a  ON a.Id_Attr_ue_Sem = ev.Id_Attr_ue_Sem
JOIN lmd.classe         c  ON c.id_S_Gr_Spe    = a.id_S_Gr_Spe
JOIN lmd.semestre       s  ON s.numeroSemestre = c.numeroSemestre
JOIN lmd.anneeacademique aa ON aa.id_Annee_Acad = a.id_Annee_Acad
WHERE o.numeroInscription = @mat
GROUP BY s.numeroSemestre, s.Lib_Semestre, aa.ANNEE_ACAD
ORDER BY s.numeroSemestre;

-- 3) NOTES ORPHELINES : notes de l'etudiant dont l'UE/semestre n'existe plus
--    (invisibles dans les deux requetes ci-dessus). Doit etre vide.
SELECT o.idObtenir, o.numeroEval, ev.codeEcu, ev.Id_Attr_ue_Sem AS attribution_ue_manquante, o.Note
FROM lmd.obtenir o
JOIN lmd.evaluation ev ON ev.numeroEval = o.numeroEval
LEFT JOIN lmd.attr_ue_classe a ON a.Id_Attr_ue_Sem = ev.Id_Attr_ue_Sem
WHERE o.numeroInscription = @mat
  AND a.Id_Attr_ue_Sem IS NULL;