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
4. **Fotók** — 12 kép van bekötve: a Facebook-oldal nyilvános fotói (épület, terasz ×3, pizza,
   büfé homlokzat — ezek jó felbontásúak) + az értékelőoldalak kollázsaiból kivágott kisebb képek
   (belső, szoba, fürdő, bejárat, udvar — ezeket érdemes saját, nagyobb fotóra cserélni ugyanazon a néven).
   Új kép: fájl az `images` mappába, felvétel az `inc/config.php` → `gallery` listába, felirat a
   `lang/*.php` `gallery` részében. Rendszámot, arcot ne hagyj a képen.

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
