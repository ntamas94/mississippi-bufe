<?php
/**
 * Facebook-hírfolyam betöltő — szerveroldali lekérés + gyorsítótár.
 *
 * A nyilvános Facebook-oldalt crawler user-agenttel kérjük le, a beágyazott
 * JSON-ból kinyerjük a posztokat (szöveg, dátum, kép, link), és a
 * data/fb_feed.json fájlban gyorsítótárazzuk. Hiba esetén a lejárt
 * gyorsítótárat is visszaadjuk, hogy az oldal sose maradjon üresen.
 */

const FB_FEED_TTL   = 21600; // 6 óra
const FB_FEED_LIMIT = 8;

function fb_feed_cache_path(): string {
    return __DIR__ . '/../data/fb_feed.json';
}

/** Posztok kigyűjtése a Facebook-oldal HTML-jéből. */
function fb_feed_parse(string $html, string $pageUrl): array {
    $ids = $times = $msgs = $imgs = [];

    preg_match_all('/"post_id":"(\d+)"/', $html, $m, PREG_OFFSET_CAPTURE);
    foreach ($m[1] as $hit) $ids[] = [$hit[1], $hit[0]];

    preg_match_all('/"creation_time":(\d+)/', $html, $m, PREG_OFFSET_CAPTURE);
    foreach ($m[1] as $hit) $times[] = [$hit[1], (int)$hit[0]];

    preg_match_all('/"message":\{[^{}]{0,600}?"text":"((?:[^"\\\\]|\\\\.)*)"/', $html, $m, PREG_OFFSET_CAPTURE);
    foreach ($m[1] as $hit) $msgs[] = [$hit[1], $hit[0]];

    // Egyképes posztok ("photo_image") + több képes posztok médiablokkjai ("image").
    // A "profile_picture_depth_0" kulcsokra (kommentelők profilképei) nem illeszkedik.
    preg_match_all('/"(?:photo_image|image)":\{"uri":"((?:[^"\\\\]|\\\\.)*)"/', $html, $m, PREG_OFFSET_CAPTURE);
    foreach ($m[1] as $hit) $imgs[] = [$hit[1], $hit[0]];

    if (!$ids) return [];

    // Legközelebbi post_id előfordulás keresése egy offszethez.
    $nearest = function (int $off) use ($ids): string {
        $best = null; $bestDist = PHP_INT_MAX;
        foreach ($ids as [$idOff, $id]) {
            $d = abs($idOff - $off);
            if ($d < $bestDist) { $bestDist = $d; $best = $id; }
        }
        return $best;
    };

    $posts = [];
    foreach ($ids as [, $id]) {
        if (!isset($posts[$id])) $posts[$id] = ['id' => $id, 'time' => 0, 'text' => '', 'image' => '', 'images' => []];
    }
    foreach ($times as [$off, $t]) {
        $id = $nearest($off);
        if (!$posts[$id]['time']) $posts[$id]['time'] = $t;
    }
    foreach ($msgs as [$off, $raw]) {
        $id   = $nearest($off);
        $text = json_decode('"' . $raw . '"') ?? '';
        if ($text !== '' && strlen($text) > strlen($posts[$id]['text'])) $posts[$id]['text'] = $text;
    }
    $firstPostOff = $ids[0][0];
    foreach ($imgs as [$off, $raw]) {
        // Az első poszt előtti képek az oldal fejlécéhez (fotócsík, borító) tartoznak — kihagyjuk.
        if ($off < $firstPostOff - 2000) continue;
        $id  = $nearest($off);
        $uri = json_decode('"' . $raw . '"') ?? '';
        if ($uri === '') continue;
        // A lookaside crawler-kép böngészőből nem tölthető be — saját proxyn át adjuk.
        if (preg_match('~lookaside\.fbsbx\.com/lookaside/crawler/media/\?media_id=(\d+)~', $uri, $mm)) {
            $uri = 'fb-img.php?id=' . $mm[1];
        }
        if (count($posts[$id]['images']) < 4 && !in_array($uri, $posts[$id]['images'], true)) {
            $posts[$id]['images'][] = $uri;
        }
    }

    $out = [];
    foreach ($posts as $p) {
        $p['image'] = $p['images'][0] ?? '';
        if ($p['text'] === '' && !$p['images']) continue;
        // Rendszerszövegek kiszűrése (nem valódi posztok).
        if (preg_match('/hozz\x{00e1}sz\x{00f3}l\x{00e1}s lehet|megv\x{00e1}ltoztatta a|f\x{00e9}nyk\x{00e9}pe$/u', $p['text'])) {
            $p['text'] = '';
            if (!$p['images']) continue;
        }
        $p['link'] = rtrim($pageUrl, '/') . '/posts/' . $p['id'];
        $out[] = $p;
    }

    usort($out, fn($a, $b) => $b['time'] <=> $a['time']);
    return array_slice($out, 0, FB_FEED_LIMIT);
}

/** A Facebook-oldal HTML-jének letöltése crawler user-agenttel. */
function fb_feed_fetch(string $pageUrl): string {
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 10,
            'header'  => "User-Agent: Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)\r\n" .
                         "Accept-Language: hu,en;q=0.8\r\n",
        ],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    return @file_get_contents($pageUrl, false, $ctx) ?: '';
}

/** Posztok gyorsítótárból vagy friss lekéréssel. */
function fb_feed_get(string $pageUrl): array {
    $cacheFile = fb_feed_cache_path();
    $cache     = null;

    if (is_file($cacheFile)) {
        $cache = json_decode((string)file_get_contents($cacheFile), true);
        if (is_array($cache) && ($cache['fetched'] ?? 0) + FB_FEED_TTL > time()) {
            return $cache['posts'] ?? [];
        }
    }

    $html  = fb_feed_fetch($pageUrl);
    $posts = $html !== '' ? fb_feed_parse($html, $pageUrl) : [];

    if ($posts) {
        @file_put_contents($cacheFile, json_encode(
            ['fetched' => time(), 'posts' => $posts],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ), LOCK_EX);
        return $posts;
    }

    // Sikertelen lekérés — lejárt gyorsítótár is jobb a semminél.
    return is_array($cache) ? ($cache['posts'] ?? []) : [];
}
