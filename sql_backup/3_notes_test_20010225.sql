-- ============================================================
-- BASE DE TEST UNIQUEMENT
-- Remplace les notes existantes inferieures a 10 par une note
-- aleatoire entre 12.00 et 18.00 pour ce matricule, semestres 1 a 4.
-- Tous les types d'evaluation sont concernes.
-- ============================================================
SET @mat = _utf8mb4'20010225' COLLATE utf8mb4_unicode_ci;

-- Apercu des notes qui seront modifiees.
SELECT
    o.idObtenir,
    s.Lib_Semestre AS semestre,
    aa.ANNEE_ACAD AS annee_acad,
    ec.codeEcu AS code_ecue,
    te.libTypEval AS type_eval,
    ev.dateEval AS date_eval,
    o.Note AS note_actuelle
FROM lmd.obtenir o
JOIN lmd.evaluation ev ON ev.numeroEval = o.numeroEval
JOIN lmd.attr_ue_classe a ON a.Id_Attr_ue_Sem = ev.Id_Attr_ue_Sem
JOIN lmd.classe c ON c.id_S_Gr_Spe = a.id_S_Gr_Spe
JOIN lmd.semestre s ON s.numeroSemestre = c.numeroSemestre
JOIN lmd.anneeacademique aa ON aa.id_Annee_Acad = a.id_Annee_Acad
JOIN lmd.ecue ec ON ec.codeEcu = ev.codeEcu
LEFT JOIN lmd.typeevaluation te ON te.codTypEval = ev.codTypEval
WHERE o.numeroInscription COLLATE utf8mb4_unicode_ci = @mat COLLATE utf8mb4_unicode_ci
  AND s.numeroSemestre IN (1, 2, 3, 4)
  AND o.Note < 10
ORDER BY s.numeroSemestre, aa.ANNEE_ACAD, ev.dateEval, ec.codeEcu;

START TRANSACTION;

UPDATE lmd.obtenir o
JOIN lmd.evaluation ev ON ev.numeroEval = o.numeroEval
JOIN lmd.attr_ue_classe a ON a.Id_Attr_ue_Sem = ev.Id_Attr_ue_Sem
JOIN lmd.classe c ON c.id_S_Gr_Spe = a.id_S_Gr_Spe
JOIN lmd.semestre s ON s.numeroSemestre = c.numeroSemestre
SET o.Note = ROUND(12 + FLOOR(RAND() * 601) / 100, 2)
WHERE o.numeroInscription COLLATE utf8mb4_unicode_ci = @mat COLLATE utf8mb4_unicode_ci
  AND s.numeroSemestre IN (1, 2, 3, 4)
  AND o.Note < 10;

SELECT ROW_COUNT() AS notes_modifiees;

-- Controle des notes du matricule dans la plage demandee.
SELECT
    o.idObtenir,
    s.Lib_Semestre AS semestre,
    ev.codTypEval AS type_eval,
    o.Note
FROM lmd.obtenir o
JOIN lmd.evaluation ev ON ev.numeroEval = o.numeroEval
JOIN lmd.attr_ue_classe a ON a.Id_Attr_ue_Sem = ev.Id_Attr_ue_Sem
JOIN lmd.classe c ON c.id_S_Gr_Spe = a.id_S_Gr_Spe
JOIN lmd.semestre s ON s.numeroSemestre = c.numeroSemestre
WHERE o.numeroInscription COLLATE utf8mb4_unicode_ci = @mat COLLATE utf8mb4_unicode_ci
  AND s.numeroSemestre IN (1, 2, 3, 4)
  AND o.Note BETWEEN 12 AND 18
ORDER BY s.numeroSemestre, ev.codTypEval, o.idObtenir;

COMMIT;
-- Pour annuler les modifications, remplace COMMIT par ROLLBACK avant l'execution.
