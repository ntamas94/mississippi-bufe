<?php
/**
 * Facebook-kép proxy — a lookaside crawler-képek böngészőből nem tölthetők
 * be (átirányító HTML-t adnak), ezért szerveroldalon, crawler user-agenttel
 * kérjük le és a data/fbimg mappában gyorsítótárazzuk őket.
 * Kizárólag numerikus media_id-t fogadunk el, így a proxy nem nyitott.
 */

$id = $_GET['id'] ?? '';
if (!preg_match('/^\d{1,20}$/', $id)) { http_response_code(400); exit; }

$dir = __DIR__ . '/data/fbimg';
if (!is_dir($dir)) @mkdir($dir, 0775, true);
$file = $dir . '/' . $id . '.jpg';

if (!is_file($file) || filesize($file) < 100) {
    $ctx = stream_context_create([
        'http' => [
            'timeout'         => 10,
            'follow_location' => 1,
            'header'          => "User-Agent: facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)\r\n",
        ],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    $data = @file_get_contents(
        'https://lookaside.fbsbx.com/lookaside/crawler/media/?media_id=' . $id,
        false,
        $ctx
    );
    // Csak valódi képet mentünk — a hibaválasz HTML-lel kezdődik.
    if ($data === false || strlen($data) < 100 || str_starts_with(ltrim($data), '<')) {
        http_response_code(404);
        exit;
    }
    @file_put_contents($file, $data, LOCK_EX);
}

$mime = function_exists('mime_content_type') ? (@mime_content_type($file) ?: 'image/jpeg') : 'image/jpeg';
header('Content-Type: ' . $mime);
header('Content-Length: ' . (string)filesize($file));
header('Cache-Control: public, max-age=604800, immutable');
readfile($file);
