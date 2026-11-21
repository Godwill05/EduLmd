-- ============================================================
-- IUSAA - ETAPE 1 : DIAGNOSTIC (lecture seule, aucune modification)
-- Base de production : lmd     | Sauvegarde : lmd2
-- ============================================================
SET @mat_prod   = '20014124';  -- matricule conserve dans lmd
SET @mat_backup = '20014124';  -- matricule qui porte les vraies notes dans lmd2
                               -- (si le doublon supprime avait un autre matricule, mets-le ici)

-- 1) L'etudiant existe-t-il dans les deux bases ?
SELECT 'lmd'  AS base, MATRICULE, nomEtu, prenomsEtu, dateNaisEtu FROM lmd.etudiant  WHERE MATRICULE COLLATE utf8mb4_unicode_ci = @mat_prod COLLATE utf8mb4_unicode_ci
UNION ALL
SELECT 'lmd2' AS base, MATRICULE, nomEtu, prenomsEtu, dateNaisEtu FROM lmd2.etudiant WHERE MATRICULE COLLATE utf8mb4_unicode_ci = @mat_backup COLLATE utf8mb4_unicode_ci;

-- 2) Nombre de notes dans chaque base
SELECT 'lmd'  AS base, COUNT(*) AS nb_notes FROM lmd.obtenir  WHERE numeroInscription COLLATE utf8mb4_unicode_ci = @mat_prod COLLATE utf8mb4_unicode_ci
UNION ALL
SELECT 'lmd2' AS base, COUNT(*) AS nb_notes FROM lmd2.obtenir WHERE numeroInscription COLLATE utf8mb4_unicode_ci = @mat_backup COLLATE utf8mb4_unicode_ci;

-- 3) Notes presentes dans lmd2 et ABSENTES de lmd (= celles a restaurer)
SELECT b.idObtenir, b.numeroEval, b.Note, b.login, b.created_at
FROM lmd2.obtenir b
WHERE b.numeroInscription COLLATE utf8mb4_unicode_ci = @mat_backup COLLATE utf8mb4_unicode_ci
  AND NOT EXISTS (SELECT 1 FROM lmd.obtenir p
                  WHERE p.numeroInscription COLLATE utf8mb4_unicode_ci = @mat_prod COLLATE utf8mb4_unicode_ci AND p.numeroEval = b.numeroEval)
ORDER BY b.numeroEval;

-- 4) Controle A : evaluations de lmd2 inexistantes dans lmd  -> DOIT ETRE VIDE
SELECT b.idObtenir, b.numeroEval
FROM lmd2.obtenir b
WHERE b.numeroInscription COLLATE utf8mb4_unicode_ci = @mat_backup COLLATE utf8mb4_unicode_ci
  AND NOT EXISTS (SELECT 1 FROM lmd.evaluation e WHERE e.numeroEval = b.numeroEval);

-- 5) Controle B : idObtenir deja pris dans lmd par une AUTRE note -> DOIT ETRE VIDE
SELECT b.idObtenir AS id_en_conflit, p.numeroInscription AS pris_par_matricule, p.numeroEval
FROM lmd2.obtenir b
JOIN lmd.obtenir p ON p.idObtenir = b.idObtenir
WHERE b.numeroInscription COLLATE utf8mb4_unicode_ci = @mat_backup COLLATE utf8mb4_unicode_ci
  AND NOT (p.numeroInscription COLLATE utf8mb4_unicode_ci = @mat_prod COLLATE utf8mb4_unicode_ci AND p.numeroEval = b.numeroEval);

-- 6) Controle C : une meme evaluation a-t-elle une note differente dans les deux bases ?
SELECT b.numeroEval, b.Note AS note_lmd2, p.Note AS note_lmd
FROM lmd2.obtenir b
JOIN lmd.obtenir p ON p.numeroInscription COLLATE utf8mb4_unicode_ci = @mat_prod COLLATE utf8mb4_unicode_ci AND p.numeroEval = b.numeroEval
WHERE b.numeroInscription COLLATE utf8mb4_unicode_ci = @mat_backup COLLATE utf8mb4_unicode_ci AND b.Note <> p.Note;
