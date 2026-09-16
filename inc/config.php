<?php
/**
 * Központi adatok. Ha változik a telefonszám, az ár vagy a nyitvatartás,
 * elég ezt a fájlt átírni — az egész oldal ebből dolgozik.
 */

$CFG_BASE = [
    // Figyelem: a "motel" megnevezés jogszabályban rögzített kategória,
    // az oldalon ezért "szálláshely" szerepel. A Google-cégadatlap neve
    // (maps_query) maradhat a bejegyzett Google-listázás szerint.
    'name'      => 'Mississippi Büfé & Missouri Szálláshely',
    'short'     => 'Mississippi',
    'street'    => 'Alkotmány utca 38.',
    'city'      => 'Egyházasrádóc',
    'zip'       => '9783',
    'country'   => 'HU',
    'phone'     => '+36 94 950 348',
    'phone_raw' => '+3694950348',
    'mobile'     => '+36 30 693 4875',
    'mobile_raw' => '+36306934875',

    // Ide érkeznek a foglalási kérések. ÍRD ÁT a valós címre!
    'mail_to'   => 'info@mississippibufe.hu',
    // A tárhely saját domainjén lévő feladó — sok szolgáltató csak így enged levelet küldeni.
    'mail_from' => 'noreply@mississippibufe.hu',

    'facebook'  => 'https://www.facebook.com/Szipiszupi23/',
    'maps_query' => 'Mississippi Büfé & Motel Missouri, Egyházasrádóc',

    // 0 = vasárnap … 6 = szombat, [nyitás, zárás] percben számolva óra:perc alapján
    'hours' => [
        1 => ['10:00', '22:00'],
        2 => ['10:00', '22:00'],
        3 => ['10:00', '22:00'],
        4 => ['10:00', '22:00'],
        5 => ['10:00', '22:00'],
        6 => ['16:00', '22:00'],
        0 => ['16:00', '21:00'],
    ],

    // Szobaárak forintban, egy éjszaka (18:00–10:00): fő => [reggeli nélkül, reggelivel]
    // Reggeli 3000 Ft/fő a büfében 8–10 között (nem kötelező). Klíma külön: 3500 Ft/éjszaka.
    'room_prices' => [
        1 => [12000, 15000],
        2 => [18000, 24000],
        3 => [24000, 33000],
        4 => [30000, 42000],
    ],
    'breakfast_per_person' => 3000,

    'rooms_total' => 6,
    'capacity'    => 25,

    'gallery' => [
        ['file' => 'to-tukrozodes.jpg',    'key' => 'g_pond_house'],
        ['file' => 'bufe-tabla.jpg',       'key' => 'g_sign'],
        ['file' => 'terasz-to.jpg',        'key' => 'g_terrace_pond'],
        ['file' => 'kert-to.jpg',          'key' => 'g_garden'],
        ['file' => 'bufe-to.jpg',          'key' => 'g_pond_corner'],
        ['file' => 'motel-epulet.jpg',     'key' => 'g_motel_front'],
        ['file' => 'terasz-jatszoter.jpg', 'key' => 'g_terrace_play'],
        ['file' => 'kerti-to-vizeses.jpg', 'key' => 'g_waterfall'],
        ['file' => 'epulet.jpg',          'key' => 'g_building'],
        ['file' => 'terasz.jpg',          'key' => 'g_terrace'],
        ['file' => 'pizza.jpg',           'key' => 'g_pizza'],
        ['file' => 'bufe-epulet.jpg',     'key' => 'g_buffet'],
        ['file' => 'terasz-asztalok.jpg', 'key' => 'g_terrace_tables'],
        ['file' => 'motel-udvar.jpg',     'key' => 'g_motel'],
        ['file' => 'belso.jpg',           'key' => 'g_inside'],
        ['file' => 'szoba-1.jpg',         'key' => 'g_room'],
        ['file' => 'szoba-2.jpg',         'key' => 'g_room2'],
        ['file' => 'furdo.jpg',           'key' => 'g_bath'],
        ['file' => 'bufe-este.jpg',       'key' => 'g_buffet_evening'],
        ['file' => 'ejszaka.jpg',         'key' => 'g_night'],
        ['file' => 'terasz-pad.jpg',      'key' => 'g_terrace_bench'],
        ['file' => 'bejarat.jpg',         'key' => 'g_entrance'],
        ['file' => 'tel.jpg',             'key' => 'g_winter'],
        ['file' => 'erkezes.jpg',         'key' => 'g_arrival'],
    ],

    // interaktív utcakép a Google Térképen (kulcs nélküli hivatalos link)
    'street_view' => 'https://www.google.com/maps/@?api=1&map_action=pano&pano=_SbSJYeIh1C1OgcZ2ylGeA',
];

// Az adminban mentett adatok felülírják a fentieket (data/site.json):
// szobaárak, elérhetőségek, nyitvatartás, galéria-módosítások.
$__siteFile = __DIR__ . '/../data/site.json';
if (is_file($__siteFile)) {
    // A kezdő UTF-8 BOM-ot levágjuk (pl. Jegyzettömbből mentett fájl), különben a json_decode elhasal.
    $__site = json_decode(preg_replace('/^\xEF\xBB\xBF/', '', (string) file_get_contents($__siteFile)), true);
    if (is_array($__site)) {
        // szobaárak
        foreach (($__site['room_prices'] ?? []) as $__g => $__pair) {
            $__g = (int) $__g;
            if (isset($CFG_BASE['room_prices'][$__g]) && is_array($__pair) && count($__pair) === 2) {
                $CFG_BASE['room_prices'][$__g] = [(int) $__pair[0], (int) $__pair[1]];
            }
        }
        if (is_numeric($__site['breakfast_per_person'] ?? null)) {
            $CFG_BASE['breakfast_per_person'] = (int) $__site['breakfast_per_person'];
        }

        // elérhetőségek
        foreach (['phone', 'mobile', 'mail_to', 'facebook', 'street', 'city', 'zip'] as $__k) {
            if (isset($__site[$__k]) && is_string($__site[$__k]) && trim($__site[$__k]) !== '') {
                $CFG_BASE[$__k] = trim($__site[$__k]);
            }
        }

        // nyitvatartás: csak a nyitva tartott napok szerepelnek, ["ÓÓ:PP", "ÓÓ:PP"] párral
        if (isset($__site['hours']) && is_array($__site['hours'])) {
            $__h = [];
            foreach ($__site['hours'] as $__d => $__pair) {
                $__d = (int) $__d;
                if ($__d < 0 || $__d > 6 || !is_array($__pair) || count($__pair) !== 2) {
                    continue;
                }
                [$__o, $__c] = array_values($__pair);
                if (is_string($__o) && is_string($__c)
                    && preg_match('/^\d{1,2}:\d{2}$/', $__o) && preg_match('/^\d{1,2}:\d{2}$/', $__c)) {
                    $__h[$__d] = [$__o, $__c];
                }
            }
            if ($__h) {
                $CFG_BASE['hours'] = $__h;
            }
        }

        // galéria: adminból feltöltött új képek, elrejtett képek, sorrend
        foreach (($__site['gallery_extra'] ?? []) as $__g) {
            if (is_array($__g) && isset($__g['file'], $__g['key'])
                && preg_match('/^[a-z0-9_-]+\.(jpe?g|png|webp)$/i', (string) $__g['file'])
                && preg_match('/^g_[a-z0-9_]+$/', (string) $__g['key'])) {
                $CFG_BASE['gallery'][] = ['file' => $__g['file'], 'key' => $__g['key']];
            }
        }
        // (az admin képkezelője a CFG_KEEP_HIDDEN konstanssal a rejtetteket is látja)
        if (!defined('CFG_KEEP_HIDDEN') && !empty($__site['gallery_hidden']) && is_array($__site['gallery_hidden'])) {
            $__hid = array_flip(array_map('strval', $__site['gallery_hidden']));
            $CFG_BASE['gallery'] = array_values(array_filter(
                $CFG_BASE['gallery'],
                fn($s) => !isset($__hid[$s['key']])
            ));
        }
        if (!empty($__site['gallery_order']) && is_array($__site['gallery_order'])) {
            $__pos = array_flip(array_values(array_map('strval', $__site['gallery_order'])));
            usort($CFG_BASE['gallery'], fn($a, $b) => ($__pos[$a['key']] ?? 9999) <=> ($__pos[$b['key']] ?? 9999));
        }
    }
    unset($__site, $__g, $__pair, $__k, $__h, $__d, $__o, $__c, $__hid, $__pos);
}
unset($__siteFile);

// A hívógombhoz használt "nyers" szám mindig a megjelenített számból készül.
$CFG_BASE['phone_raw']  = preg_replace('/[^+\d]/', '', $CFG_BASE['phone']);
$CFG_BASE['mobile_raw'] = preg_replace('/[^+\d]/', '', $CFG_BASE['mobile']);

return $CFG_BASE;