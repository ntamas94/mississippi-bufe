/* Mississippi Büfé & Motel Missouri — oldal-interakciók */
(function () {
  'use strict';

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------- téma (világos / sötét) ---------- */
  var themeBtn = document.querySelector('.theme-toggle');
  var applyTheme = function (theme) {
    document.documentElement.setAttribute('data-theme', theme);
    if (themeBtn) themeBtn.setAttribute('aria-pressed', String(theme === 'dark'));
  };
  var savedTheme = null;
  try { savedTheme = localStorage.getItem('theme'); } catch (err) { /* privát mód */ }
  applyTheme(savedTheme || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'));
  if (themeBtn) {
    themeBtn.addEventListener('click', function () {
      var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      applyTheme(next);
      try { localStorage.setItem('theme', next); } catch (err) { /* nem baj */ }
    });
  }

  /* ---------- görgetési folyamatjelző + vissza a tetejére + hero parallax ---------- */
  var progress = document.querySelector('.progress');
  var toTop = document.querySelector('.to-top');
  var heroBg = document.querySelector('.hero-bg');
  var ticking = false;

  // a mobil menü a fejléc alatt nyílik — annak magasságát CSS-változóban tartjuk
  var headerEl = document.getElementById('site-header');
  var setHeaderVar = function () {
    if (headerEl) document.documentElement.style.setProperty('--header-h', headerEl.offsetHeight + 'px');
  };
  setHeaderVar();

  var onFrame = function () {
    ticking = false;
    setHeaderVar();
    var y = window.scrollY;
    var max = document.documentElement.scrollHeight - window.innerHeight;
    if (progress) progress.style.transform = 'scaleX(' + (max > 0 ? Math.min(1, y / max) : 0) + ')';
    if (toTop) toTop.classList.toggle('is-visible', y > 600);
    if (heroBg && !reduceMotion && y < window.innerHeight * 1.2) {
      heroBg.style.transform = 'translate3d(0,' + (y * 0.28) + 'px,0) scale(1.06)';
    }
  };
  var requestFrame = function () {
    if (!ticking) { ticking = true; requestAnimationFrame(onFrame); }
  };
  window.addEventListener('scroll', requestFrame, { passive: true });
  window.addEventListener('resize', requestFrame);
  onFrame();

  if (toTop) {
    toTop.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
    });
  }

  /* ---------- számlálók ---------- */
  var counters = document.querySelectorAll('[data-count]');
  if (counters.length && !reduceMotion && 'IntersectionObserver' in window) {
    var countIo = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        var el = entry.target;
        countIo.unobserve(el);
        // "24/7", "10+", "32 cm", "4,6" — csak a szám elejét animáljuk,
        // az utótag ("/7", "+", " cm") változatlanul a szám után marad
        var raw = String(el.dataset.count);
        var m = raw.match(/^(\d+(?:[.,]\d+)?)([\s\S]*)$/);
        if (!m) return;
        var numPart = m[1];
        var suffix = m[2] || '';
        var target = parseFloat(numPart.replace(',', '.'));
        var decimals = (numPart.split(/[.,]/)[1] || '').length;
        var useComma = numPart.indexOf(',') !== -1;
        var start = null;
        var duration = 1400;
        var step = function (ts) {
          if (!start) start = ts;
          var p = Math.min(1, (ts - start) / duration);
          var eased = 1 - Math.pow(1 - p, 3);
          var val = (target * eased).toFixed(decimals);
          el.textContent = (useComma ? val.replace('.', ',') : val) + suffix;
          if (p < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
      });
    }, { threshold: 0.6 });
    counters.forEach(function (el) { countIo.observe(el); });
  }

  /* ---------- étlap-szűrő ---------- */
  var filter = document.querySelector('.menu-filter');
  if (filter) {
    var groups = document.querySelectorAll('.menu-group[data-group]');
    filter.addEventListener('click', function (ev) {
      var chip = ev.target.closest('.chip');
      if (!chip) return;
      filter.querySelectorAll('.chip').forEach(function (c) { c.classList.remove('is-active'); });
      chip.classList.add('is-active');
      var key = chip.dataset.filter;
      groups.forEach(function (g) {
        g.hidden = key !== 'all' && g.dataset.group !== key;
      });
      if (key !== 'all') {
        var target = document.querySelector('.menu-group[data-group="' + key + '"]');
        if (target) target.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
      }
    });
  }

  /* ---------- mobil menü ---------- */
  var toggle = document.querySelector('.nav-toggle');
  var nav = document.getElementById('site-nav');

  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', String(open));
      document.body.style.overflow = open && window.innerWidth <= 900 ? 'hidden' : '';
    });

    nav.addEventListener('click', function (ev) {
      if (ev.target.closest('a') && window.innerWidth <= 900) {
        nav.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
      }
    });

    document.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape' && nav.classList.contains('is-open')) {
        toggle.click();
        toggle.focus();
      }
    });
  }

  /* ---------- fejléc árnyék görgetéskor ---------- */
  var header = document.getElementById('site-header');
  if (header) {
    var onScroll = function () {
      header.classList.toggle('is-stuck', window.scrollY > 12);
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  /* ---------- beúszó elemek ---------- */
  var revealables = document.querySelectorAll('[data-reveal]');
  if (revealables.length) {
    if (reduceMotion || !('IntersectionObserver' in window)) {
      revealables.forEach(function (el) { el.classList.add('is-visible'); });
    } else {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            io.unobserve(entry.target);
          }
        });
      }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
      revealables.forEach(function (el) { io.observe(el); });
    }
  }

  /* ---------- élő "most nyitva" jelzés ---------- */
  var status = document.querySelector('.status');
  if (status && status.dataset.hours) {
    var hours, labels;
    try {
      hours = JSON.parse(status.dataset.hours);
      labels = JSON.parse(status.dataset.labels);
    } catch (err) {
      hours = null;
    }

    var toMin = function (hm) {
      var parts = hm.split(':');
      return parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
    };

    // A látogató saját órája helyett a magyar időt nézzük — külföldi vendégnek is ez a mérvadó.
    var budapestNow = function () {
      var s = new Date().toLocaleString('en-US', { timeZone: 'Europe/Budapest' });
      return new Date(s);
    };

    var render = function () {
      if (!hours) return;
      var now = budapestNow();
      var day = now.getDay();
      var mins = now.getHours() * 60 + now.getMinutes();
      var today = hours[day];
      var open = false;
      var text = '';

      if (today && mins >= toMin(today[0]) && mins < toMin(today[1])) {
        open = true;
        text = '<strong>' + labels.open + '</strong> · ' + labels.until.replace('%s', today[1]);
      } else {
        var nextDay = day;
        var nextOpen = null;
        if (today && mins < toMin(today[0])) {
          nextOpen = today[0];
        } else {
          for (var i = 1; i <= 7; i++) {
            var d = (day + i) % 7;
            if (hours[d]) { nextDay = d; nextOpen = hours[d][0]; break; }
          }
        }
        text = '<strong>' + labels.closed + '</strong>';
        if (nextOpen) {
          text += ' · ' + (nextDay === day
            ? labels.opens.replace('%s', nextOpen)
            : labels.opensDay.replace('%s', labels.days[nextDay]).replace('%s', nextOpen));
        }
      }

      status.classList.toggle('is-open', open);
      status.classList.toggle('is-closed', !open);
      status.querySelector('.status-text').innerHTML = text;
    };

    render();
    setInterval(render, 60000);
  }

  /* ---------- képnéző ---------- */
  var lightbox = document.getElementById('lightbox');
  var shots = Array.prototype.slice.call(document.querySelectorAll('.shot'));

  if (lightbox && shots.length) {
    var lbImg = lightbox.querySelector('img');
    var lbCap = lightbox.querySelector('figcaption');
    var index = 0;
    var lastFocus = null;

    var show = function (i) {
      index = (i + shots.length) % shots.length;
      var img = shots[index].querySelector('img');
      lbImg.src = img.dataset.full || img.src;
      lbImg.alt = img.alt;
      lbCap.textContent = shots[index].dataset.caption || img.alt;
    };

    var open = function (i) {
      lastFocus = document.activeElement;
      show(i);
      lightbox.hidden = false;
      requestAnimationFrame(function () { lightbox.classList.add('is-open'); });
      document.body.style.overflow = 'hidden';
      lightbox.querySelector('.lightbox-close').focus();
    };

    var close = function () {
      lightbox.classList.remove('is-open');
      document.body.style.overflow = '';
      var done = function () { lightbox.hidden = true; };
      reduceMotion ? done() : setTimeout(done, 220);
      if (lastFocus) lastFocus.focus();
    };

    shots.forEach(function (shot, i) {
      shot.addEventListener('click', function () { open(i); });
    });

    lightbox.querySelector('.lightbox-close').addEventListener('click', close);
    lightbox.querySelector('.lightbox-prev').addEventListener('click', function () { show(index - 1); });
    lightbox.querySelector('.lightbox-next').addEventListener('click', function () { show(index + 1); });
    lightbox.addEventListener('click', function (ev) {
      if (ev.target === lightbox) close();
    });

    document.addEventListener('keydown', function (ev) {
      if (lightbox.hidden) return;
      if (ev.key === 'Escape') close();
      if (ev.key === 'ArrowLeft') show(index - 1);
      if (ev.key === 'ArrowRight') show(index + 1);
    });

    // ujjhúzás mobilon
    var startX = null;
    lightbox.addEventListener('touchstart', function (ev) { startX = ev.touches[0].clientX; }, { passive: true });
    lightbox.addEventListener('touchend', function (ev) {
      if (startX === null) return;
      var dx = ev.changedTouches[0].clientX - startX;
      if (Math.abs(dx) > 50) show(index + (dx < 0 ? 1 : -1));
      startX = null;
    });
  }

  /* ---------- foglalási űrlap ---------- */
  var form = document.getElementById('booking-form');
  if (form) {
    var msgBox = form.querySelector('.form-msg');
    var submit = form.querySelector('button[type="submit"]');
    var prices = JSON.parse(form.dataset.prices || '{}');
    var texts = JSON.parse(form.dataset.texts || '{}');
    var amountEl = form.querySelector('.estimate .amount');

    var setError = function (field, message) {
      var wrap = field.closest('.field');
      if (!wrap) return;
      wrap.classList.toggle('has-error', Boolean(message));
      var slot = wrap.querySelector('.error');
      if (slot) slot.textContent = message || '';
    };

    var validate = function () {
      var ok = true;
      ['name', 'email', 'arrival'].forEach(function (key) {
        var field = form.elements[key];
        if (!field) return;
        var value = field.value.trim();
        if (!value) {
          setError(field, key === 'arrival' ? texts.badDate : texts.required);
          ok = false;
        } else if (key === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value)) {
          setError(field, texts.badEmail);
          ok = false;
        } else {
          setError(field, '');
        }
      });
      return ok;
    };

    var updateEstimate = function () {
      if (!amountEl) return;
      var guests = parseInt(form.elements.guests.value, 10);
      var nights = parseInt(form.elements.nights.value, 10);
      var withBreakfast = form.elements.breakfast.checked ? 1 : 0;
      var row = prices[guests];
      if (!row || !nights) {
        amountEl.textContent = '—';
        return;
      }
      var total = row[withBreakfast] * nights;
      amountEl.textContent = total.toLocaleString('hu-HU').replace(/,/g, ' ') + ' Ft';
    };

    ['guests', 'nights', 'breakfast'].forEach(function (key) {
      if (form.elements[key]) form.elements[key].addEventListener('change', updateEstimate);
    });
    updateEstimate();

    form.addEventListener('submit', function (ev) {
      ev.preventDefault();
      if (!validate()) return;

      submit.disabled = true;
      var original = submit.textContent;
      submit.textContent = texts.sending || '…';
      msgBox.hidden = true;

      fetch(form.action, {
        method: 'POST',
        headers: { 'X-Requested-With': 'fetch' },
        body: new FormData(form)
      })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          msgBox.hidden = false;
          msgBox.classList.toggle('is-ok', Boolean(data.ok));
          msgBox.classList.toggle('is-bad', !data.ok);
          msgBox.textContent = data.message || (data.ok ? texts.success : texts.error);
          if (data.ok) form.reset();
          // reset() után állítjuk, mert az a rejtett mezőket is visszaírná
          if (data.csrf && form.elements.csrf) form.elements.csrf.value = data.csrf;
          updateEstimate();
        })
        .catch(function () {
          msgBox.hidden = false;
          msgBox.classList.add('is-bad');
          msgBox.textContent = texts.error;
        })
        .finally(function () {
          submit.disabled = false;
          submit.textContent = original;
        });
    });
  }
})();
