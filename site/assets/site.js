/* Монолит-Вест: меню, заявки, выбор цвета, калькулятор, фильтры, цели Метрики */
(function () {
  'use strict';
  var MW = window.MW || {}, $ = function (s, r) { return (r || document).querySelector(s); }, $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var fmt = function (n) { return Math.round(n).toLocaleString('ru-RU'); };
  var t0 = performance.now();
  var reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

  function goal(name, params) { try { if (MW.metrika && window.ym) ym(MW.metrika, 'reachGoal', name, params || {}); } catch (e) {} }
  document.addEventListener('click', function (e) { var g = e.target.closest('[data-goal]'); if (g) goal(g.getAttribute('data-goal')); });

  /* UTM и источник: запоминаем на время визита */
  try {
    var qs = new URLSearchParams(location.search), src = JSON.parse(sessionStorage.getItem('mw_src') || '{}');
    ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'].forEach(function (k) { if (qs.get(k)) src[k] = qs.get(k); });
    if (!src.ref && document.referrer && document.referrer.indexOf(location.host) < 0) src.ref = document.referrer.slice(0, 200);
    sessionStorage.setItem('mw_src', JSON.stringify(src));
  } catch (e) {}

  /* шапка и меню */
  var hdr = $('.hdr'), burger = $('.burger'), menu = $('#menu');
  function onScroll() { if (hdr) hdr.classList.toggle('sc', scrollY > 10); }
  addEventListener('scroll', onScroll, { passive: true }); onScroll();
  if (burger) burger.addEventListener('click', function () {
    var open = burger.getAttribute('aria-expanded') !== 'true';
    document.documentElement.style.setProperty('--hh', hdr.getBoundingClientRect().bottom + 'px');
    burger.setAttribute('aria-expanded', open); menu.classList.toggle('open', open);
    document.body.style.overflow = open ? 'hidden' : '';
  });

  /* маска телефона */
  function maskPhone(inp) {
    inp.addEventListener('input', function () {
      var d = inp.value.replace(/\D/g, ''); if (d[0] === '8') d = '7' + d.slice(1); if (d && d[0] !== '7') d = '7' + d; d = d.slice(0, 11);
      var r = '+7'; if (d.length > 1) r += ' (' + d.slice(1, 4); if (d.length >= 4) r += ') ' + d.slice(4, 7); if (d.length >= 7) r += '-' + d.slice(7, 9); if (d.length >= 9) r += '-' + d.slice(9, 11);
      inp.value = d ? r : '';
    });
  }

  /* окно заявки: одно на все кнопки, поля подставляются из data-атрибутов */
  var dlg = $('#lead'), form = dlg && $('[data-lead-form]', dlg);
  if (form) maskPhone(form.phone);
  function openLead(o) {
    if (!dlg) return;
    form.reset();
    ['product', 'form', 'color', 'thickness', 'area', 'total', 'project', 'link', 'shot'].forEach(function (k) { if (form[k]) form[k].value = o[k] || ''; });
    form.type.value = o.type || 'callback';
    $('[data-lead-title]', dlg).textContent = o.title || (o.type === 'product' ? 'Заявка на товар' : 'Оставить заявку');
    $('[data-lead-sub]', dlg).textContent = o.sub || 'Перезвоним в течение 15 минут в рабочее время и посчитаем стоимость с доставкой.';
    var what = [o.product, o.form && ('Брусчатка «' + o.form + '»'), o.color, o.thickness && (o.thickness + ' мм'), o.area && (o.area + ' м²'), o.total && ('≈ ' + fmt(o.total) + ' ₽')].filter(Boolean).join(' · ');
    var w = $('[data-lead-what]', dlg); w.hidden = !what; w.textContent = what;
    $('[data-qty]', dlg).hidden = o.type !== 'product';
    if (o.qty && o.type === 'product') form.qty.value = '';
    $('[data-err]', dlg).hidden = true;
    $('[data-submit]', dlg).textContent = o.type === 'product' ? 'Отправить заявку' : o.type === 'project3d' ? 'Отправить проект' : 'Получить расчёт';
    if (dlg.showModal) dlg.showModal(); else dlg.setAttribute('open', '');
    setTimeout(function () { form.phone.focus(); }, 60);
    goal('lead_open', { type: form.type.value });
  }
  window.mwLead = openLead;
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-lead]');
    if (b) { e.preventDefault(); openLead({ type: b.getAttribute('data-lead'), title: b.getAttribute('data-title'), sub: b.getAttribute('data-sub'), product: b.getAttribute('data-product'), qty: b.getAttribute('data-qty') }); }
    if (e.target.closest('[data-close]')) dlg.close();
  });
  if (dlg) dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });

  if (form) form.addEventListener('submit', function (e) {
    e.preventDefault();
    var err = $('[data-err]', dlg), digits = form.phone.value.replace(/\D/g, '');
    function show(m) { err.textContent = m; err.hidden = false; }
    if (digits.length !== 11) { show('Проверьте номер телефона: нужно 11 цифр.'); form.phone.focus(); return; }
    if (!form.consent.checked) { show('Отметьте согласие на обработку данных.'); return; }
    var fd = new FormData(form);
    fd.append('elapsed', Math.round(performance.now() - t0));
    fd.append('page', location.pathname.replace(MW.base, '') + location.search);
    try { var src = JSON.parse(sessionStorage.getItem('mw_src') || '{}'); Object.keys(src).forEach(function (k) { fd.append(k, src[k]); }); } catch (x) {}
    form.classList.add('busy');
    fetch(form.action, { method: 'POST', body: fd, headers: { Accept: 'application/json' } })
      .then(function (r) { return r.json().then(function (j) { return { r: r, j: j }; }); })
      .then(function (x) {
        form.classList.remove('busy');
        if (!x.j.ok) { show(x.j.error || 'Не удалось отправить. Позвоните нам.'); return; }
        goal('lead_' + form.type.value);
        try { sessionStorage.setItem('mw_sent', $('[data-lead-what]', dlg).textContent || ''); } catch (y) {}
        location.href = MW.base + '/spasibo/';
      })
      .catch(function () { form.classList.remove('busy'); show('Нет связи. Проверьте интернет или позвоните нам.'); });
  });

  var tw = $('[data-thanks-what]');
  if (tw) { try { var s = sessionStorage.getItem('mw_sent'); if (s) { tw.textContent = 'Вы отправили: ' + s; tw.hidden = false; } } catch (e) {} }

  /* страница формы брусчатки: цвет, толщина, цена, фото, мини-расчёт */
  var pf = $('[data-pf]');
  if (pf) {
    var D = JSON.parse($('[data-pf-data]', pf).textContent), cur = { c: D.colors[0], t: null };
    var want = new URLSearchParams(location.search).get('cvet');
    D.colors.forEach(function (c) { if (c.slug === want) cur.c = c; });
    function variant() { var v = cur.c.v.filter(function (x) { return x.t === cur.t; })[0]; return v || cur.c.v[0]; }
    var imgBox = $('[data-pf-img]', pf), cache = {};
    function load(src) { if (!cache[src]) cache[src] = new Promise(function (ok, no) { var i = new Image(); i.decoding = 'async'; i.onload = function () { ok(i); }; i.onerror = no; i.src = src; }); return cache[src]; }
    function showImg(src, alt) {
      var tok = (imgBox._t = (imgBox._t || 0) + 1);
      var slow = setTimeout(function () { if (tok === imgBox._t) imgBox.classList.add('swap'); }, 120);   // затемняем, только если грузится заметно долго
      load(src).then(function () {
        if (tok !== imgBox._t) return;
        imgBox.innerHTML = '<img src="' + src + '" alt="' + alt.replace(/"/g, '&quot;') + '" width="800" height="600">';
      }).catch(function () {}).then(function () { clearTimeout(slow); if (tok === imgBox._t) imgBox.classList.remove('swap'); });
    }
    function preloadAll() { D.colors.forEach(function (c) { c.v.forEach(function (x) { if (x.img) load(x.img.webp).catch(function () {}); }); }); }
    if ('requestIdleCallback' in window) requestIdleCallback(preloadAll, { timeout: 3000 }); else setTimeout(preloadAll, 1500);
    function renderPf(swapImg) {
      if (!cur.t || !cur.c.v.some(function (x) { return x.t === cur.t; })) cur.t = cur.c.v[0].t;
      var v = variant();
      $$('[data-color]', pf).forEach(function (b) { b.setAttribute('aria-pressed', b.getAttribute('data-color') === cur.c.slug); });
      $('[data-pf-name]', pf).textContent = cur.c.name;
      $('[data-pf-cval]', pf).innerHTML = '<b>' + cur.c.name + '</b> · ' + (cur.c.grp === 'mix' ? 'колормикс' : 'одноцветная');
      $('[data-pf-price]', pf).textContent = v.p ? fmt(v.p) + ' ₽' : 'по запросу';
      var thw = $('[data-pf-th-wrap]', pf), th = $('[data-pf-th]', pf);
      thw.hidden = cur.c.v.length < 2;
      th.innerHTML = cur.c.v.map(function (x) { return '<button type="button" data-t="' + x.t + '" aria-pressed="' + (x.t === cur.t) + '">' + x.t + ' мм</button>'; }).join('');
      if (swapImg && v.img) showImg(v.img.webp, 'Брусчатка ' + D.form + ' ' + cur.c.name);
      var a3 = $('[data-pf-3d]', pf); if (a3) a3.href = MW.base + '/3d/?forma=' + D.slug + '&cvet=' + cur.c.slug;
      try { var u = new URL(location.href); u.searchParams.set('cvet', cur.c.slug); history.replaceState(null, '', u); } catch (e) {}
      calcPf();
    }
    function calcPf() {
      var v = variant(), area = parseFloat(String($('[data-pf-area]', pf).value).replace(',', '.')) || 0, m2 = area * 1.07;
      $('[data-pf-m2]', pf).textContent = (Math.round(m2 * 10) / 10).toLocaleString('ru-RU') + ' м²';
      $('[data-pf-pal]', pf).textContent = D.m2 ? Math.ceil(m2 / D.m2) : '—';
      $('[data-pf-total]', pf).textContent = v.p ? '≈ ' + fmt(Math.round(m2 * v.p / 100) * 100) + ' ₽' : 'по запросу';
    }
    pf.addEventListener('click', function (e) {
      var b = e.target.closest('[data-color]');
      if (b) { D.colors.forEach(function (c) { if (c.slug === b.getAttribute('data-color')) cur.c = c; }); renderPf(true); }
      var t = e.target.closest('[data-t]');
      if (t) { cur.t = +t.getAttribute('data-t'); renderPf(false); }
    });
    $('[data-pf-area]', pf).addEventListener('input', calcPf);
    $('[data-pf-lead]', pf).addEventListener('click', function () {
      var v = variant(), area = parseFloat($('[data-pf-area]', pf).value) || 0;
      openLead({ type: 'paving', title: 'Расчёт брусчатки «' + D.form + '»', form: D.form, color: cur.c.name, thickness: v.t, area: area || '', total: v.p && area ? Math.round(area * 1.07 * v.p / 100) * 100 : '' });
    });
    renderPf(want && cur.c.slug === want && cur.c !== D.colors[0]);
  }

  /* калькулятор: форма, цвет, толщина, запас и площадь выбираются касанием */
  $$('[data-calc]').forEach(function (box) {
    var D = JSON.parse($('[data-c-data]', box).textContent), st = { f: 0, c: 0, t: null, r: 7, a: 40 };
    var range = $('[data-c-area]', box), num = $('[data-c-area-num]', box);
    var colors = function () { return D[st.f].colors; };
    var v = function () { var c = colors()[st.c], x = c.v.filter(function (q) { return q.t === st.t; })[0]; return x || c.v[0]; };
    var plural = function (n, a, b, c) { n = Math.abs(n) % 100; var n1 = n % 10; return n > 10 && n < 20 ? c : n1 > 1 && n1 < 5 ? b : n1 === 1 ? a : c; };
    function press(sel, attr, val) { $$(sel, box).forEach(function (b) { b.setAttribute('aria-pressed', String(b.getAttribute(attr)) === String(val)); }); }
    function drawColors() {
      var list = colors(), prev = null, h = '';
      list.forEach(function (c, i) {
        if (prev && prev !== c.grp) h += '<span class="sep" aria-hidden="true"></span>';
        prev = c.grp;
        h += '<button type="button" class="dot-b" data-ci="' + i + '" aria-pressed="' + (i === st.c) + '" title="' + c.name + '" aria-label="' + c.name + '" style="background:' + c.sw + '"></button>';
      });
      $('[data-c-colors]', box).innerHTML = h;
      var c = list[st.c];
      if (!c.v.some(function (q) { return q.t === st.t; })) st.t = c.v[0].t;
      $('[data-c-th]', box).innerHTML = c.v.map(function (q) { return '<button type="button" data-ct="' + q.t + '" aria-pressed="' + (q.t === st.t) + '">' + q.t + ' мм</button>'; }).join('');
    }
    function calc() {
      var f = D[st.f], c = colors()[st.c], x = v(), m2 = st.a * (1 + st.r / 100), pal = f.m2 ? Math.ceil(m2 / f.m2) : 0;
      $('[data-c-cname]', box).innerHTML = '<b>' + c.name + '</b> · ' + (x.p ? fmt(x.p) + ' ₽/м²' : 'цена по запросу') + ' · ' + (c.grp === 'mix' ? 'колормикс' : 'одноцветная');
      $('[data-c-m2]', box).textContent = (Math.round(m2 * 10) / 10).toLocaleString('ru-RU') + ' м²';
      $('[data-c-pal]', box).textContent = pal ? pal + ' ' + plural(pal, 'поддон', 'поддона', 'поддонов') : '—';
      $('[data-c-kg]', box).textContent = f.kg ? (Math.round(pal * f.kg / 100) / 10).toLocaleString('ru-RU') + ' т' : '—';
      $('[data-c-total]', box).textContent = x.p ? '≈ ' + fmt(Math.round(m2 * x.p / 500) * 500) + ' ₽' : 'по запросу';
      return { m2: m2, x: x };
    }
    function setArea(val, from) {
      st.a = Math.max(1, Math.min(2000, Math.round(+val) || 1));
      if (from !== 'num') num.value = st.a;
      if (from !== 'range') range.value = Math.min(400, st.a);
      calc();
    }
    box.addEventListener('click', function (e) {
      var t;
      if ((t = e.target.closest('[data-fi]'))) { st.f = +t.getAttribute('data-fi'); st.c = 0; press('[data-fi]', 'data-fi', st.f); drawColors(); calc(); }
      else if ((t = e.target.closest('[data-ci]'))) { st.c = +t.getAttribute('data-ci'); drawColors(); calc(); }
      else if ((t = e.target.closest('[data-ct]'))) { st.t = +t.getAttribute('data-ct'); drawColors(); calc(); }
      else if ((t = e.target.closest('[data-r]'))) { st.r = +t.getAttribute('data-r'); press('[data-r]', 'data-r', st.r); calc(); }
    });
    range.addEventListener('input', function () { setArea(range.value, 'range'); });
    num.addEventListener('input', function () { if (num.value !== '') setArea(num.value, 'num'); });
    num.addEventListener('blur', function () { setArea(num.value || 1); });
    $('[data-c-lead]', box).addEventListener('click', function () {
      var r = calc(), f = D[st.f], c = colors()[st.c];
      openLead({ type: 'calc', title: 'Точный расчёт с доставкой', form: f.name, color: c.name, thickness: r.x.t, area: st.a, total: r.x.p ? Math.round(r.m2 * r.x.p / 500) * 500 : '' });
    });
    drawColors(); calc();
  });

  /* миниатюры фото товара */
  $$('[data-thumbs]').forEach(function (t) {
    var main = t.parentNode.querySelector('.prod-img');
    t.addEventListener('click', function (e) {
      var b = e.target.closest('[data-src]'); if (!b || !main) return;
      $$('[data-src]', t).forEach(function (x) { x.setAttribute('aria-pressed', x === b); });
      var alt = (b.querySelector('img') || {}).alt || '';
      main.innerHTML = '<img src="' + b.getAttribute('data-src') + '" alt="' + alt.replace(/"/g, '&quot;') + '" width="800" height="1000">';
    });
  });

  /* фильтр колец по диаметру */
  var flt = $('[data-filter]');
  if (flt) flt.addEventListener('click', function (e) {
    var b = e.target.closest('[data-d]'); if (!b) return;
    var d = b.getAttribute('data-d');
    $$('button', flt).forEach(function (x) { x.setAttribute('aria-pressed', x === b); });
    $$('.prow[data-diam]').forEach(function (r) { r.hidden = d !== '' && r.getAttribute('data-diam') !== d; });
  });

  /* карта: загружаем, только когда до неё доскроллили */
  var map = $('[data-map]');
  if (map && 'IntersectionObserver' in window) {
    var mo = new IntersectionObserver(function (es) {
      if (!es[0].isIntersecting) return; mo.disconnect();
      var lat = map.getAttribute('data-lat'), lng = map.getAttribute('data-lng'), f = document.createElement('iframe');
      f.src = 'https://yandex.ru/map-widget/v1/?ll=' + lng + '%2C' + lat + '&z=16&pt=' + lng + '%2C' + lat + '%2Cpm2rdm';
      f.title = 'Карта: площадка Монолит-Вест'; f.allowFullscreen = true; f.width = '100%'; f.height = '100%';
      map.innerHTML = ''; map.appendChild(f);
    }, { rootMargin: '200px' });
    mo.observe(map);
  }

  /* появление блоков при прокрутке */
  if (!reduce && 'IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (es) { es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } }); }, { rootMargin: '0px 0px -5% 0px', threshold: .05 });
    $$('.sec h2, .sec .lead, .card-p, .cat, .steps li, .feat > div, .gal figure, .form-row, .ptable, details, .final, .try3d, .calc-box, .visit').forEach(function (el) {
      if (el.closest('.hero') || el.closest('.page-top.pf')) return;
      var sib = el.parentNode ? Array.prototype.indexOf.call(el.parentNode.children, el) : 0;
      el.classList.add('rv'); el.style.setProperty('--d', Math.min(sib, 6) * 0.07 + 's'); io.observe(el);
    });
  }
})();
