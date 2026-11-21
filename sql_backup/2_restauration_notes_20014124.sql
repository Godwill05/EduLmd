-- ============================================================
-- IUSAA - ETAPE 2 : RESTAURATION DES NOTES (a lancer apres l'etape 1)
-- N'insere QUE les notes manquantes de ce matricule. Rien n'est ecrase,
-- rien n'est supprime, les autres etudiants et les nouvelles inscriptions
-- ne sont pas touches.
-- ============================================================
SET @mat_prod   = '20014124';
SET @mat_backup = '20014124';

START TRANSACTION;

INSERT INTO lmd.obtenir
    (idObtenir, numeroInscription, numeroEval, Note, login, created_at,
     loginupdate, momentupdate, Observation, version, updated_at)
SELECT b.idObtenir, @mat_prod, b.numeroEval, b.Note, b.login, b.created_at,
       b.loginupdate, b.momentupdate, b.Observation, b.version, b.updated_at
FROM lmd2.obtenir b
WHERE b.numeroInscription COLLATE utf8mb4_unicode_ci = @mat_backup COLLATE utf8mb4_unicode_ci
  -- la note n'existe pas deja dans lmd
  AND NOT EXISTS (SELECT 1 FROM lmd.obtenir p
                  WHERE p.numeroInscription COLLATE utf8mb4_unicode_ci = @mat_prod COLLATE utf8mb4_unicode_ci AND p.numeroEval = b.numeroEval)
  -- l'identifiant n'est pas deja utilise par une autre note
  AND NOT EXISTS (SELECT 1 FROM lmd.obtenir p2 WHERE p2.idObtenir = b.idObtenir)
  -- l'evaluation existe bien dans lmd
  AND EXISTS     (SELECT 1 FROM lmd.evaluation e WHERE e.numeroEval = b.numeroEval);

SELECT ROW_COUNT() AS notes_restaurees;

-- Verification : apres restauration, lmd doit contenir 69 notes (68 dans lmd2 + 1 deja presente dans lmd).
SELECT 'lmd'  AS base, COUNT(*) AS nb_notes FROM lmd.obtenir  WHERE numeroInscription COLLATE utf8mb4_unicode_ci = @mat_prod COLLATE utf8mb4_unicode_ci
UNION ALL
SELECT 'lmd2' AS base, COUNT(*) AS nb_notes FROM lmd2.obtenir WHERE numeroInscription COLLATE utf8mb4_unicode_ci = @mat_backup COLLATE utf8mb4_unicode_ci;

COMMIT;
-- Pour annuler a la place : remplace COMMIT par ROLLBACK.
