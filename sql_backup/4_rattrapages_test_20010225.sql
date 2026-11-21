-- ============================================================
-- BASE DE TEST UNIQUEMENT
-- Ajoute des notes aleatoires entre 12.00 et 18.00 aux evaluations
-- RATTR sans note de l'etudiant 20010225, pour les ECUE en echec
-- listes dans le releve LP3/LP4-GC de 2025-2026.
-- Les evaluations RATTR multiples du meme ECUE sont toutes traitees.
-- ============================================================
SET @mat = _utf8mb4'20010225' COLLATE utf8mb4_unicode_ci;

-- Apercu : chaque ligne correspond a une note RATTR qui sera ajoutee.
SELECT
    ev.numeroEval,
    c.Nom_Classe AS classe,
    ec.codeEcu AS code_ecue,
    ec.libelleEcu AS ecue,
    ev.dateEval
FROM lmd.evaluation ev
JOIN lmd.attr_ue_classe a ON a.Id_Attr_ue_Sem = ev.Id_Attr_ue_Sem
JOIN lmd.classe c ON c.id_S_Gr_Spe = a.id_S_Gr_Spe
JOIN lmd.semestre s ON s.numeroSemestre = c.numeroSemestre
JOIN lmd.anneeacademique aa ON aa.id_Annee_Acad = a.id_Annee_Acad
JOIN lmd.ecue ec ON ec.codeEcu = ev.codeEcu
WHERE aa.ANNEE_ACAD = '2025-2026'
  AND c.Nom_Classe IN ('LP3-GC', 'LP4-GC')
  AND ev.codTypEval = 'RATTR'
  AND ec.codeEcu IN (
      '1HYG1306', '1ROU1304',
      '1BEA1402', '1EQB1410', '1HYU1407',
      '1MDC1411', '1MSR1406', '1ROU1403'
  )
  AND NOT EXISTS (
      SELECT 1
      FROM lmd.obtenir o
      WHERE o.numeroInscription COLLATE utf8mb4_unicode_ci = @mat COLLATE utf8mb4_unicode_ci
        AND o.numeroEval = ev.numeroEval
  )
ORDER BY c.Nom_Classe, ec.codeEcu, ev.dateEval, ev.numeroEval;

START TRANSACTION;

INSERT INTO lmd.obtenir (numeroInscription, numeroEval, Note, login)
SELECT
    @mat,
    ev.numeroEval,
    ROUND(12 + FLOOR(RAND() * 601) / 100, 2),
    'TEST-SEED'
FROM lmd.evaluation ev
JOIN lmd.attr_ue_classe a ON a.Id_Attr_ue_Sem = ev.Id_Attr_ue_Sem
JOIN lmd.classe c ON c.id_S_Gr_Spe = a.id_S_Gr_Spe
JOIN lmd.semestre s ON s.numeroSemestre = c.numeroSemestre
JOIN lmd.anneeacademique aa ON aa.id_Annee_Acad = a.id_Annee_Acad
JOIN lmd.ecue ec ON ec.codeEcu = ev.codeEcu
WHERE aa.ANNEE_ACAD = '2025-2026'
  AND c.Nom_Classe IN ('LP3-GC', 'LP4-GC')
  AND ev.codTypEval = 'RATTR'
  AND ec.codeEcu IN (
      '1HYG1306', '1ROU1304',
      '1BEA1402', '1EQB1410', '1HYU1407',
      '1MDC1411', '1MSR1406', '1ROU1403'
  )
  AND NOT EXISTS (
      SELECT 1
      FROM lmd.obtenir o
      WHERE o.numeroInscription COLLATE utf8mb4_unicode_ci = @mat COLLATE utf8mb4_unicode_ci
        AND o.numeroEval = ev.numeroEval
  );

SELECT ROW_COUNT() AS notes_rattrapage_ajoutees;

-- Controle des notes RATTR presentes pour les ECUE cibles.
SELECT
    c.Nom_Classe AS classe,
    ec.codeEcu AS code_ecue,
    ev.numeroEval,
    o.Note
FROM lmd.obtenir o
JOIN lmd.evaluation ev ON ev.numeroEval = o.numeroEval
JOIN lmd.attr_ue_classe a ON a.Id_Attr_ue_Sem = ev.Id_Attr_ue_Sem
JOIN lmd.classe c ON c.id_S_Gr_Spe = a.id_S_Gr_Spe
JOIN lmd.semestre s ON s.numeroSemestre = c.numeroSemestre
JOIN lmd.anneeacademique aa ON aa.id_Annee_Acad = a.id_Annee_Acad
JOIN lmd.ecue ec ON ec.codeEcu = ev.codeEcu
WHERE o.numeroInscription COLLATE utf8mb4_unicode_ci = @mat COLLATE utf8mb4_unicode_ci
  AND aa.ANNEE_ACAD = '2025-2026'
  AND c.Nom_Classe IN ('LP3-GC', 'LP4-GC')
  AND ev.codTypEval = 'RATTR'
  AND ec.codeEcu IN (
      '1HYG1306', '1ROU1304',
      '1BEA1402', '1EQB1410', '1HYU1407',
      '1MDC1411', '1MSR1406', '1ROU1403'
  )
ORDER BY c.Nom_Classe, ec.codeEcu, ev.dateEval, ev.numeroEval;

COMMIT;
-- Pour annuler les insertions, remplace COMMIT par ROLLBACK avant l'execution.
