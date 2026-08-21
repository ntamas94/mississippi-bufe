<?php
/**
 * Központi adatok. Ha változik a telefonszám, az ár vagy a nyitvatartás,
 * elég ezt a fájlt átírni — az egész oldal ebből dolgozik.
 */

return [
    'name'      => 'Mississippi Büfé & Motel Missouri',
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

    // Szobaárak forintban: fő => [reggeli nélkül, reggelivel]
    // A reggeli 500 Ft/fő (kenyér, tea, sonka, vaj, dzsem) — nem kötelező.
    'room_prices' => [
        1 => [4500, 5000],
        2 => [7800, 8800],
        3 => [9200, 10700],
        4 => [10500, 12500],
    ],
    'breakfast_per_person' => 500,

    'rooms_total' => 6,
    'capacity'    => 25,

    'gallery' => [
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
