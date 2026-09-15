# Mississippi Büfé & Motel Missouri — weboldal

Többnyelvű (HU / EN / DE) PHP-oldal, keretrendszer nélkül. Nincs build lépés,
nincs Composer, nincs adatbázis. Bármilyen PHP 8.1+ tárhelyen fut.

## Mappák, fájlok

| Hely | Mi van benne |
|---|---|
| `index.php`, `etlap.php`, `motel.php`, `galeria.php`, `kapcsolat.php` | Az öt oldal |
| `foglalas.php` | A foglalási űrlap feldolgozója (e-mail + napló) |
| `inc/config.php` | **Központi adatok**: telefon, cím, nyitvatartás, szobaárak, galéria lista, e-mail cím |
| `inc/i18n.php` | Nyelvválasztás, session, segédfüggvények |
| `inc/head.php`, `inc/footer.php` | Közös fejléc / lábléc |
| `inc/booking-form.php` | Az űrlap HTML-je (motel és kapcsolat oldalon) |
| `lang/hu.php`, `lang/en.php`, `lang/de.php` | Minden szöveg nyelvenként, **az étlap is itt van** |
| `assets/app.css`, `assets/app.js` | Stílus (világos + sötét téma) és interakciók |
| `images/` | Fotók |
| `data/` | Ide kerül a `foglalasok.log` (a szerver hozza létre, `.htaccess` védi) |
| `_regi-html/` | A korábbi statikus HTML változat — törölhető |

## Amit még ki kell tölteni

1. **E-mail cím** — `inc/config.php`: `mail_to` (ide jönnek a foglalások) és `mail_from`
   (a saját domainen lévő feladó, különben sok tárhely nem küldi el).
2. **Étlap árak** — `lang/hu.php`, `lang/en.php`, `lang/de.php` → `menu.groups`, minden tételhez
   `'price' => 2490` formában. Ha megvan, töröld a `menu.todo` sárga dobozt (etlap.php, 25. sor környéke).
3. **Szobaárak ellenőrzése** — `inc/config.php` → `room_prices` (a régi weboldalról származnak).
4. **Fotók** — a tulajdonos saját, friss fotói (2026. szeptember; JPEG q 72–74, progresszív,
   EXIF/GPS nélkül, egyenként ≤ 350 KB):

   | Fájl | Méret | Mi látszik | Hol van használva |
   |---|---|---|---|
   | `to-tukrozodes.jpg` | 1920×1440 | A piros büfé a kerti tóban tükröződve, alkonyatkor | hero háttér (`app.css`), galéria 1. (széles) |
   | `bufe-tabla.jpg` | 1600×1200 | Büfé + az út menti „Mississipi Büfé – Pizza és Babgulyás – Missouri Panzió” tábla | OG-kép (`inc/head.php`), galéria |
   | `terasz-to.jpg` | 1600×1200 | Padok Kőbányai-ernyők alatt, mögötte a tó és a büfé bejárata | főoldal terasz nagy kép, galéria |
   | `kert-to.jpg` | 1600×1200 | Kert banánfával, tóval, fa lugassal | főoldal terasz kis kép, galéria |
   | `bufe-to.jpg` | 1050×1400 (álló) | A büfé tóparti sarka | galéria (ne legyen az 1. vagy 6. széles helyen) |
   | `motel-epulet.jpg` | 1600×900 | A szálláshely épülete: 6 számozott szoba tornáccal, napelemek, aszfaltos parkoló | főoldal szállás-kártya, `szallas.php`, galéria 6. (széles) |
   | `terasz-jatszoter.jpg` | 1600×1200 | Térkövezett terasz padokkal, mögötte játszótér (csúszda, hinta) | főoldal terasz kis kép, galéria |
   | `kerti-to-vizeses.jpg` | 1600×1200 | A tó közelről: kis vízesés, béka szobor, halak | galéria |

   Mellettük a régebbi képek: a Facebook-oldal nyilvános fotói (épület, terasz, pizza, büfé homlokzat)
   és az értékelőoldalak kollázsaiból kivágott kisebb képek (belső, szoba, fürdő, bejárat, udvar —
   ezeket érdemes saját, nagyobb fotóra cserélni ugyanazon a néven).
   Új kép: fájl az `images` mappába, felvétel az `inc/config.php` → `gallery` listába, felirat a
   `lang/*.php` `gallery` részében. A galéria 1. és 6. képe dupla széles, 16:9-re vágva — oda fekvő
   kép kerüljön. Rendszámot, arcot ne hagyj a képen.

## Funkciók

- Nyelvváltó (URL `?lang=`, sütiben megjegyzi, böngészőnyelvből is felismeri), `hreflang` linkek
- Élő „Most nyitva / zárva” jelzés a fejlécben, percenként frissül (magyar idő szerint)
- Sötét / világos téma kapcsoló, rendszerbeállítást követi, villanásmentes
- Görgetési animációk, számláló, parallax hero, folyamatjelző, vissza-a-tetejére gomb
- Étlap kategória-szűrő (ragadós chipek)
- Képnéző (lightbox): nyilak, Esc, ujjhúzás mobilon
- Foglalási űrlap: élő árbecslés, kliens+szerver oldali ellenőrzés, honeypot + időzítés + CSRF
  spam-védelem, e-mail küldés `mail()`-lel, minden kérés naplóban is (`data/foglalasok.log`)
- Mobil hívósáv (Hívás / Foglalás) kis képernyőn
- Google Térkép beágyazás, útvonaltervezés link
- Schema.org (Restaurant + Motel) strukturált adat, OG meták
- Csökkentett mozgás (`prefers-reduced-motion`) tiszteletben tartva, nyomtatási stílus

## Helyi futtatás

```bash
php -S localhost:8791 -t mississippi-bufe
```

Windows / winget PHP esetén a teljes út:
`C:\Users\ntama\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.4_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe`

Helyben `mail()` nem küld, de a kérés a `data/foglalasok.log`-ba kerül.

## Futtatás Dockerrel

A repó tartalmaz `Dockerfile`-t és `docker-compose.yml`-t (PHP 8.4 + Apache).

```bash
docker compose up -d --build
```

Az oldal ezután a szerver **8080-as portján** fut (`http://SZERVER-IP:8080`).
Másik port: a `docker-compose.yml`-ben a `"8080:80"` sor első felét írd át.

**Foglalási e-mail:** a `docker-compose.yml` `SMTP_*` változóiba írd be a
levelezőszolgáltató adatait (pl. a tárhely SMTP-je vagy a Gmail app-jelszóval).
Üresen hagyva e-mail nem megy ki, de minden kérés megőrződik a
`data/foglalasok.log` fájlban (named volume, újraindítást túlél):

```bash
docker compose exec web cat /var/www/html/data/foglalasok.log
```

## Publikálás

1. A mappa teljes tartalmát töltsd fel a tárhely webgyökerébe (`public_html`, `www`, stb.).
2. Ellenőrizd, hogy PHP 8.1 vagy újabb fut.
3. Írd át az e-mail címeket az `inc/config.php`-ben.
4. Küldj egy próbafoglalást — ha nem jön levél, nézd meg a `data/foglalasok.log`-ot és a
   tárhely SMTP-beállítását (egyes szolgáltatóknál a `mail_from`-nak a saját domainen kell lennie).

## Külső hivatkozások

Google Fonts (Inter, Playfair Display) és a Google Térkép iframe. Minden más helyben van.
Ha a betűtípusokat is helyben szeretnéd, töltsd le őket az `assets/fonts` mappába és
cseréld a `<link>` sorokat `@font-face` deklarációkra az `app.css` elején.

## Admin szövegszerkesztő

A `/admin/` címen böngészőből átírható az oldal **minden szövege és ára**
(étlap, szobaárak, mindhárom nyelven), fájlszerkesztés nélkül.

- **Első megnyitáskor** a `/admin/login.php` jelszóbeállítást kér — ezt a
  jelszót te adod meg, és csak a hash-e tárolódik (`data/admin.php`).
- A módosítások a `data/overrides.<nyelv>.json` és `data/site.json` fájlokba
  kerülnek; a `lang/*.php` és `inc/config.php` alapfájlok érintetlenek maradnak.
  Ha egy mezőt visszaírsz az eredetire, a felülírás magától törlődik.
- A `data/` mappát az Apache (Dockerfile: `protect-data.conf`, illetve
  `data/.htaccess`) kívülről letiltja; Docker alatt a `booking-data` volume
  miatt a mentések a konténer újraindítását is túlélik.

## Események oldal (Facebook)

Az `esemenyek.php` a Facebook-oldal idővonalát mutatja. Adatvédelmi okból az
idővonal **csak a látogató kattintására** töltődik be — addig a Facebook
semmilyen adatot nem kap a látogatóról.
