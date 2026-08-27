<?php
/** Facebook-hírfolyam JSON-végpont — a saját betöltő innen kéri a posztokat. */
require __DIR__ . '/inc/config.php';
require __DIR__ . '/inc/fb_feed.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: public, max-age=1800');

echo json_encode(
    ['posts' => fb_feed_get($CFG_BASE['facebook'])],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
