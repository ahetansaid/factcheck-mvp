<?php
/**
 * API de l'outil de saisie du gold set.
 *
 *   GET  api.php?a=clips&lang=fon    -> liste des clips + état de saisie (JSON)
 *   GET  api.php?a=audio&id=<clip>   -> flux WAV du clip
 *   POST api.php?a=save              -> {id, reference, status} enregistré
 */
declare(strict_types=1);
require __DIR__ . '/lib.php';
require_token();

$action = $_GET['a'] ?? '';

function json_out(array $data): never
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

switch ($action) {
    case 'clips':
        $lang = (string) ($_GET['lang'] ?? '');
        if (!isset(LANGS[$lang])) {
            http_response_code(400);
            json_out(['error' => 'langue inconnue']);
        }
        $clips = clips_for($lang);
        $done = 0;
        foreach ($clips as $c) {
            if ($c['status'] !== 'todo') {
                $done++;
            }
        }
        json_out(['lang' => $lang, 'total' => count($clips), 'traites' => $done, 'clips' => $clips]);

    case 'audio':
        $path = audio_path_for((string) ($_GET['id'] ?? ''));
        if ($path === null || !is_file($path)) {
            http_response_code(404);
            exit('clip introuvable');
        }
        header('Content-Type: audio/wav');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: private, max-age=3600');
        readfile($path);
        exit;

    case 'save':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            json_out(['error' => 'POST attendu']);
        }
        $body = json_decode(file_get_contents('php://input') ?: '[]', true) ?: [];
        $id = (string) ($body['id'] ?? '');
        if ($id === '') {
            http_response_code(400);
            json_out(['error' => 'id manquant']);
        }
        save_reference($id, (string) ($body['reference'] ?? ''), (string) ($body['status'] ?? 'done'));
        json_out(['ok' => true]);

    default:
        http_response_code(400);
        json_out(['error' => 'action inconnue']);
}
