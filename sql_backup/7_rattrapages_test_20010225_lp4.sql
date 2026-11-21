-- ============================================================
-- BASE DE TEST UNIQUEMENT
-- Cree les evaluations RATTR manquantes et ajoute une note de test
-- pour les trois ECUE encore non valides du releve LP4-GC 2025-2026.
-- Les notes generees sont strictement superieures a 12 et au plus 18.
-- Les evaluations creees ont une date NULL et le login TEST-SEED.
-- ============================================================
USE lmd;

SET @mat = _utf8mb4'20010225' COLLATE utf8mb4_unicode_ci;

DROP TEMPORARY TABLE IF EXISTS tmp_lp4_rattr_targets;
CREATE TEMPORARY TABLE tmp_lp4_rattr_targets (
    Id_Attr_ue_Sem INT NOT NULL,
    codeEcu VARCHAR(20) NOT NULL,
    PRIMARY KEY (Id_Attr_ue_Sem, codeEcu)
);

INSERT INTO tmp_lp4_rattr_targets (Id_Attr_ue_Sem, codeEcu)
VALUES
    (483, '1CTO1405'),
    (482, '1DAO1404'),
    (487, '1TGC1409');

-- Apercu avant modification : une ligne par RATTR absent ou sans note.
SELECT
    t.codeEcu AS code_ecue,
    ev.numeroEval,
    ev.dateEval,
    o.idObtenir,
    o.Note AS note_actuelle,
    CASE
        WHEN ev.numeroEval IS NULL THEN 'Evaluation RATTR a creer'
        WHEN o.idObtenir IS NULL THEN 'Note RATTR a ajouter'
        ELSE 'Note deja presente : aucune modification'
    END AS action_prevue
FROM tmp_lp4_rattr_targets t
JOIN lmd.attr_ecue_ue ae
  ON ae.Id_Attr_ue_Sem = t.Id_Attr_ue_Sem
 AND ae.codeEcu = t.codeEcu
LEFT JOIN lmd.evaluation ev
  ON ev.Id_Attr_ue_Sem = t.Id_Attr_ue_Sem
 AND ev.codeEcu = t.codeEcu
 AND ev.codTypEval = 'RATTR'
LEFT JOIN lmd.obtenir o
  ON o.numeroEval = ev.numeroEval
 AND o.numeroInscription COLLATE utf8mb4_unicode_ci = @mat COLLATE utf8mb4_unicode_ci
ORDER BY t.codeEcu, ev.numeroEval;

START TRANSACTION;

INSERT INTO lmd.evaluation
    (dateEval, Id_Attr_ue_Sem, codeEcu, codTypEval, login)
SELECT
    NULL,
    t.Id_Attr_ue_Sem,
    t.codeEcu,
    'RATTR',
    'TEST-SEED'
FROM tmp_lp4_rattr_targets t
JOIN lmd.attr_ecue_ue ae
  ON ae.Id_Attr_ue_Sem = t.Id_Attr_ue_Sem
 AND ae.codeEcu = t.codeEcu
WHERE NOT EXISTS (
    SELECT 1
    FROM lmd.evaluation ev
    WHERE ev.Id_Attr_ue_Sem = t.Id_Attr_ue_Sem
      AND ev.codeEcu = t.codeEcu
      AND ev.codTypEval = 'RATTR'
);

SELECT ROW_COUNT() AS evaluations_rattrapage_creees;

INSERT INTO lmd.obtenir (numeroInscription, numeroEval, Note, login)
SELECT
    @mat,
    ev.numeroEval,
    ROUND(12.01 + FLOOR(RAND() * 600) / 100, 2),
    'TEST-SEED'
FROM lmd.evaluation ev
JOIN tmp_lp4_rattr_targets t
  ON t.Id_Attr_ue_Sem = ev.Id_Attr_ue_Sem
 AND t.codeEcu = ev.codeEcu
WHERE ev.codTypEval = 'RATTR'
  AND NOT EXISTS (
      SELECT 1
      FROM lmd.obtenir o
      WHERE o.numeroInscription COLLATE utf8mb4_unicode_ci = @mat COLLATE utf8mb4_unicode_ci
        AND o.numeroEval = ev.numeroEval
  );

SELECT ROW_COUNT() AS notes_rattrapage_ajoutees;

SELECT
    t.codeEcu AS code_ecue,
    ev.numeroEval,
    o.Note,
    o.Note > 12 AS note_superieure_a_12
FROM tmp_lp4_rattr_targets t
JOIN lmd.evaluation ev
  ON ev.Id_Attr_ue_Sem = t.Id_Attr_ue_Sem
 AND ev.codeEcu = t.codeEcu
 AND ev.codTypEval = 'RATTR'
JOIN lmd.obtenir o
  ON o.numeroEval = ev.numeroEval
 AND o.numeroInscription COLLATE utf8mb4_unicode_ci = @mat COLLATE utf8mb4_unicode_ci
ORDER BY t.codeEcu, ev.numeroEval;

COMMIT;
-- Pour annuler les modifications, remplace COMMIT par ROLLBACK avant l'execution.
