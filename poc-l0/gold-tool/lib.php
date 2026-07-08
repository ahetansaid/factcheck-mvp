<?php
/**
 * Outil de saisie du gold set — couche partagée.
 *
 * Les transcriptions sont stockées dans gold.sqlite (jamais directement dans
 * manifest.csv) : pas de corruption si deux locuteurs écrivent en même temps.
 * C'est merge_gold.py qui fusionne ensuite la base dans le manifeste.
 */
declare(strict_types=1);

const POC_ROOT = __DIR__ . '/..';
const MANIFEST = POC_ROOT . '/data/manifest.csv';
const DB_PATH  = __DIR__ . '/gold.sqlite';

/** Langues acceptées et leur libellé. */
const LANGS = ['fon' => 'Fon (fongbé)', 'yor' => 'Yoruba (nago)'];

/**
 * Jeton d'accès optionnel.
 * En local : rien à faire. Derrière un tunnel : créer config.php avec
 *   <?php const GOLD_TOKEN = 'un-secret-long';
 * et transmettre le lien ...?t=un-secret-long aux locuteurs.
 */
function require_token(): void
{
    $cfg = __DIR__ . '/config.php';
    if (!is_file($cfg)) {
        return; // pas de config => accès libre (usage local)
    }
    require_once $cfg;
    if (!defined('GOLD_TOKEN') || GOLD_TOKEN === '') {
        return;
    }
    $given = $_GET['t'] ?? $_POST['t'] ?? '';
    if (!hash_equals(GOLD_TOKEN, (string) $given)) {
        http_response_code(403);
        exit('Accès refusé : jeton manquant ou invalide.');
    }
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA journal_mode=WAL');  // écritures concurrentes sûres
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS gold (
            id         TEXT PRIMARY KEY,
            lang       TEXT NOT NULL,
            reference  TEXT NOT NULL DEFAULT "",
            status     TEXT NOT NULL DEFAULT "todo",  -- todo | done | skipped
            updated_at TEXT NOT NULL
        )'
    );
    return $pdo;
}

/**
 * Lit le manifeste : [id => ['audio_path' => ..., 'lang' => ...]].
 * Source de vérité de la liste des clips (la base ne stocke que les saisies).
 */
function manifest(): array
{
    static $rows = null;
    if ($rows !== null) {
        return $rows;
    }
    $rows = [];
    $fh = fopen(MANIFEST, 'r');
    if ($fh === false) {
        http_response_code(500);
        exit('manifest.csv introuvable : ' . MANIFEST);
    }
    $head = fgetcsv($fh);
    if ($head === false) {
        fclose($fh);
        return $rows;
    }
    $head[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $head[0]); // BOM
    while (($r = fgetcsv($fh)) !== false) {
        if (count($r) < count($head)) {
            continue;
        }
        $row = array_combine($head, array_slice($r, 0, count($head)));
        if (!isset($row['id'], $row['lang'], $row['audio_path'])) {
            continue;
        }
        $rows[$row['id']] = ['audio_path' => $row['audio_path'], 'lang' => $row['lang']];
    }
    fclose($fh);
    return $rows;
}

/** Clips d'une langue, avec l'état de saisie, dans l'ordre du manifeste. */
function clips_for(string $lang): array
{
    $saved = [];
    foreach (db()->query('SELECT id, reference, status FROM gold') as $r) {
        $saved[$r['id']] = $r;
    }
    $out = [];
    foreach (manifest() as $id => $m) {
        if ($m['lang'] !== $lang) {
            continue;
        }
        $s = $saved[$id] ?? null;
        $out[] = [
            'id'        => $id,
            'reference' => $s['reference'] ?? '',
            'status'    => $s['status'] ?? 'todo',
        ];
    }
    return $out;
}

/** Chemin absolu du WAV d'un clip, ou null si l'id est inconnu / hors dossier. */
function audio_path_for(string $id): ?string
{
    $m = manifest()[$id] ?? null;
    if ($m === null) {
        return null;
    }
    $full = realpath(POC_ROOT . '/' . $m['audio_path']);
    $base = realpath(POC_ROOT . '/data/prepared');
    // Anti-traversée : le fichier doit être sous data/prepared/
    if ($full === false || $base === false || !str_starts_with($full, $base)) {
        return null;
    }
    return $full;
}

function save_reference(string $id, string $reference, string $status): void
{
    if (!isset(manifest()[$id])) {
        http_response_code(400);
        exit('id inconnu');
    }
    if (!in_array($status, ['todo', 'done', 'skipped'], true)) {
        $status = 'done';
    }
    // Normalisation NFC : les marques tonales combinantes sont recomposées si possible,
    // pour que la comparaison avec la sortie ASR soit stable.
    if (class_exists('Normalizer')) {
        $reference = \Normalizer::normalize($reference, \Normalizer::FORM_C) ?: $reference;
    }
    $lang = manifest()[$id]['lang'];
    $st = db()->prepare(
        'INSERT INTO gold (id, lang, reference, status, updated_at)
         VALUES (:id, :lang, :ref, :st, :now)
         ON CONFLICT(id) DO UPDATE SET
            reference = :ref, status = :st, updated_at = :now'
    );
    $st->execute([
        ':id' => $id, ':lang' => $lang, ':ref' => trim($reference),
        ':st' => $status, ':now' => gmdate('c'),
    ]);
}
