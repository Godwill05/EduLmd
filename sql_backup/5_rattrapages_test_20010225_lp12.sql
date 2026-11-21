-- ============================================================
-- BASE DE TEST UNIQUEMENT
-- Ajoute des notes aleatoires entre 12.00 et 18.00 aux evaluations
-- RATTR existantes mais sans note pour l'etudiant 20010225,
-- LP1/LP2-GC, annee academique 2024-2025.
-- Les ECUE cibles sont ceux en echec indiques dans le releve.
-- Aucune evaluation n'est creee et aucune note existante n'est modifiee.
-- ============================================================
SET @mat = _utf8mb4'20010225' COLLATE utf8mb4_unicode_ci;

-- Apercu des notes qui seront ajoutees.
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
WHERE aa.ANNEE_ACAD = '2024-2025'
  AND c.Nom_Classe IN ('LP1-GC', 'LP2-GC')
  AND ev.codTypEval = 'RATTR'
  AND ec.codeEcu IN ('THM', 'ANG2', 'TOP')
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
WHERE aa.ANNEE_ACAD = '2024-2025'
  AND c.Nom_Classe IN ('LP1-GC', 'LP2-GC')
  AND ev.codTypEval = 'RATTR'
  AND ec.codeEcu IN ('THM', 'ANG2', 'TOP')
  AND NOT EXISTS (
      SELECT 1
      FROM lmd.obtenir o
      WHERE o.numeroInscription COLLATE utf8mb4_unicode_ci = @mat COLLATE utf8mb4_unicode_ci
        AND o.numeroEval = ev.numeroEval
  );

SELECT ROW_COUNT() AS notes_rattrapage_ajoutees;

-- Controle des notes de rattrapage pour les ECUE cibles.
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
  AND aa.ANNEE_ACAD = '2024-2025'
  AND c.Nom_Classe IN ('LP1-GC', 'LP2-GC')
  AND ev.codTypEval = 'RATTR'
  AND ec.codeEcu IN ('THM', 'ANG2', 'TOP')
ORDER BY c.Nom_Classe, ec.codeEcu, ev.dateEval, ev.numeroEval;

COMMIT;
-- Pour annuler les insertions, remplace COMMIT par ROLLBACK avant l'execution.
