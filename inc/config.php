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

// Az adminban mentett árak felülírják a fentieket (data/site.json).
$__siteFile = __DIR__ . '/../data/site.json';
if (is_file($__siteFile)) {
    // A kezdő UTF-8 BOM-ot levágjuk (pl. Jegyzettömbből mentett fájl), különben a json_decode elhasal.
    $__site = json_decode(preg_replace('/^\xEF\xBB\xBF/', '', (string) file_get_contents($__siteFile)), true);
    if (is_array($__site)) {
        foreach (($__site['room_prices'] ?? []) as $__g => $__pair) {
            $__g = (int) $__g;
            if (isset($CFG_BASE['room_prices'][$__g]) && is_array($__pair) && count($__pair) === 2) {
                $CFG_BASE['room_prices'][$__g] = [(int) $__pair[0], (int) $__pair[1]];
            }
        }
        if (is_numeric($__site['breakfast_per_person'] ?? null)) {
            $CFG_BASE['breakfast_per_person'] = (int) $__site['breakfast_per_person'];
        }
    }
    unset($__site, $__g, $__pair);
}
unset($__siteFile);

return $CFG_BASE;