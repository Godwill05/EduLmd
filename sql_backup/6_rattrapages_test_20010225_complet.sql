-- ============================================================
-- BASE DE TEST UNIQUEMENT
-- Complete les rattrapages cibles du matricule 20010225.
-- Ajoute une note strictement superieure a 12 et inferieure ou egale a 18
-- pour les RATTR existants sans note, et cree une evaluation RATTR
-- uniquement lorsqu'elle manque pour l'ECUE cible.
-- Les evaluations creees ont une date NULL et le login TEST-SEED.
-- ============================================================
USE lmd;

SET @mat = _utf8mb4'20010225' COLLATE utf8mb4_unicode_ci;

DROP TEMPORARY TABLE IF EXISTS tmp_test_rattr_targets;
CREATE TEMPORARY TABLE tmp_test_rattr_targets (
    Id_Attr_ue_Sem INT NOT NULL,
    codeEcu VARCHAR(20) NOT NULL,
    annee_acad VARCHAR(9) NOT NULL,
    classe VARCHAR(30) NOT NULL,
    PRIMARY KEY (Id_Attr_ue_Sem, codeEcu)
);

INSERT INTO tmp_test_rattr_targets (Id_Attr_ue_Sem, codeEcu, annee_acad, classe)
VALUES
    (355, 'THM', '2024-2025', 'LP1-GC'),
    (370, 'ANG2', '2024-2025', 'LP2-GC'),
    (365, 'TOP', '2024-2025', 'LP2-GC'),
    (478, '1STA1310', '2025-2026', 'LP3-GC'),
    (477, '1EEB1309', '2025-2026', 'LP3-GC');

-- Apercu des evaluations a creer et des notes RATTR a ajouter.
SELECT
    t.classe,
    t.annee_acad,
    t.codeEcu AS code_ecue,
    ev.numeroEval,
    ev.dateEval,
    CASE
        WHEN ev.numeroEval IS NULL THEN 'Evaluation RATTR a creer'
        ELSE 'Note RATTR a ajouter si absente'
    END AS action_prevue
FROM tmp_test_rattr_targets t
JOIN lmd.attr_ue_classe a
  ON a.Id_Attr_ue_Sem = t.Id_Attr_ue_Sem
JOIN lmd.classe c
  ON c.id_S_Gr_Spe = a.id_S_Gr_Spe
JOIN lmd.anneeacademique aa
  ON aa.id_Annee_Acad = a.id_Annee_Acad
LEFT JOIN lmd.evaluation ev
  ON ev.Id_Attr_ue_Sem = t.Id_Attr_ue_Sem
 AND ev.codeEcu = t.codeEcu
 AND ev.codTypEval = 'RATTR'
WHERE c.Nom_Classe = t.classe
  AND aa.ANNEE_ACAD = t.annee_acad
ORDER BY t.annee_acad, t.classe, t.codeEcu, ev.numeroEval;

START TRANSACTION;

-- Creer seulement les evaluations RATTR absentes pour ces ECUE.
INSERT INTO lmd.evaluation
    (dateEval, Id_Attr_ue_Sem, codeEcu, codTypEval, login)
SELECT
    NULL,
    t.Id_Attr_ue_Sem,
    t.codeEcu,
    'RATTR',
    'TEST-SEED'
FROM tmp_test_rattr_targets t
JOIN lmd.attr_ue_classe a
  ON a.Id_Attr_ue_Sem = t.Id_Attr_ue_Sem
JOIN lmd.classe c
  ON c.id_S_Gr_Spe = a.id_S_Gr_Spe
JOIN lmd.anneeacademique aa
  ON aa.id_Annee_Acad = a.id_Annee_Acad
WHERE c.Nom_Classe = t.classe
  AND aa.ANNEE_ACAD = t.annee_acad
  AND NOT EXISTS (
      SELECT 1
      FROM lmd.evaluation ev
      WHERE ev.Id_Attr_ue_Sem = t.Id_Attr_ue_Sem
        AND ev.codeEcu = t.codeEcu
        AND ev.codTypEval = 'RATTR'
  );

SELECT ROW_COUNT() AS evaluations_rattrapage_creees;

-- Si le script LP1/LP2 precedent a deja ajoute une note exactement egale
-- a 12.00, l'elever au minimum strict de 12.01 dans cette base de test.
UPDATE lmd.obtenir o
JOIN lmd.evaluation ev
  ON ev.numeroEval = o.numeroEval
JOIN tmp_test_rattr_targets t
  ON t.Id_Attr_ue_Sem = ev.Id_Attr_ue_Sem
 AND t.codeEcu = ev.codeEcu
SET o.Note = ROUND(12.01 + FLOOR(RAND() * 600) / 100, 2)
WHERE o.numeroInscription COLLATE utf8mb4_unicode_ci = @mat COLLATE utf8mb4_unicode_ci
  AND ev.codTypEval = 'RATTR'
  AND o.login = 'TEST-SEED'
  AND o.Note <= 12;

-- Ajouter une note uniquement si l'etudiant n'en a pas deja pour ce RATTR.
INSERT INTO lmd.obtenir (numeroInscription, numeroEval, Note, login)
SELECT
    @mat,
    ev.numeroEval,
    ROUND(12.01 + FLOOR(RAND() * 600) / 100, 2),
    'TEST-SEED'
FROM lmd.evaluation ev
JOIN tmp_test_rattr_targets t
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

-- Controle : toutes les notes RATTR ciblees doivent etre strictement > 12.
SELECT
    t.classe,
    t.annee_acad,
    t.codeEcu AS code_ecue,
    ev.numeroEval,
    o.Note,
    o.Note > 12 AS note_superieure_a_12
FROM tmp_test_rattr_targets t
JOIN lmd.evaluation ev
  ON ev.Id_Attr_ue_Sem = t.Id_Attr_ue_Sem
 AND ev.codeEcu = t.codeEcu
 AND ev.codTypEval = 'RATTR'
JOIN lmd.obtenir o
  ON o.numeroEval = ev.numeroEval
 AND o.numeroInscription COLLATE utf8mb4_unicode_ci = @mat COLLATE utf8mb4_unicode_ci
ORDER BY t.annee_acad, t.classe, t.codeEcu, ev.numeroEval;

COMMIT;
-- Pour annuler les modifications, remplace COMMIT par ROLLBACK avant l'execution.
