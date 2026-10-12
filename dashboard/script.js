(function () {
  var root = document.documentElement;
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var darkQuery = window.matchMedia('(prefers-color-scheme: dark)');
  var TAU = Math.PI * 2;
  function clamp(x, a, b) { return Math.max(a, Math.min(b, x)); }
  function ease(x) { x = clamp(x, 0, 1); return x < 0.5 ? 4 * x * x * x : 1 - Math.pow(-2 * x + 2, 3) / 2; }

  /* Start every visit at the top so the film plays. The browser's restored scroll
     position (after a refresh) would otherwise count as "scrolled" and skip it.
     A #link in the address still jumps straight to its section. */
  try { if ('scrollRestoration' in history) history.scrollRestoration = 'manual'; } catch (e) {}
  if (!location.hash && window.scrollY > 0) {
    try { window.scrollTo({ top: 0, left: 0, behavior: 'instant' }); } catch (e) { window.scrollTo(0, 0); }
  }

  /* ---------- page UI ---------- */
  document.querySelectorAll('[data-go]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      var el = document.getElementById(a.getAttribute('data-go'));
      if (!el) return;
      e.preventDefault();
      el.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' });
    });
  });
  document.getElementById('themeBtn').addEventListener('click', function () {
    var attr = root.getAttribute('data-theme');
    var isDark = attr ? attr === 'dark' : darkQuery.matches;
    root.setAttribute('data-theme', isDark ? 'light' : 'dark');
  });
  document.querySelectorAll('.copy').forEach(function (b) {
    var label = b.textContent;
    function flash(t) { b.textContent = t; setTimeout(function () { b.textContent = label; }, 1600); }
    function fallback() {
      var el = document.getElementById(b.getAttribute('data-target'));
      if (el) { var r = document.createRange(); r.selectNodeContents(el); var s = window.getSelection(); s.removeAllRanges(); s.addRange(r); }
      flash('Selected');
    }
    b.addEventListener('click', function () {
      try { navigator.clipboard.writeText(b.getAttribute('data-copy')).then(function () { flash('Copied'); }, fallback); }
      catch (e) { fallback(); }
    });
  });

  /* ---------- service data from the page ---------- */
  var svcItems = [].slice.call(document.querySelectorAll('#svc li'));
  var SV = svcItems.map(function (li) { return { n: li.querySelector('h3').textContent, d: li.querySelector('p').textContent }; });
  var N = SV.length;
  var PRE = 3.8, CH = 1.8, ENDC = PRE + CH * N, END = ENDC + 0.8;

  var capEl = document.getElementById('cap'), capK = document.getElementById('capK'), capT = document.getElementById('capT'), capD = document.getElementById('capD'), cta = document.getElementById('cta');
  var rail = document.getElementById('rail'), railBtns = [];
  var T = reduce ? END : 0, playing = !reduce, waitTop = false, hoverIdx = -1;

  function chapter(t) { if (t < PRE) return -1; if (t < ENDC) return Math.floor((t - PRE) / CH); return N; }

  SV.forEach(function (s, i) {
    var b = document.createElement('button');
    b.type = 'button';
    b.setAttribute('aria-label', 'Go to ' + s.n);
    b.addEventListener('click', function () { window.scrollTo(0, 0); T = PRE + CH * i + 0.05; playing = true; });
    rail.appendChild(b); railBtns.push(b);
  });

  var curCh = -99;
  function setCaption(ch) {
    if (ch === curCh) return;
    curCh = ch;
    var k, t, d;
    if (ch < 0) { k = 'Established 2003'; t = 'Over 20 years of experience'; d = 'Watch what we provide.'; }
    else if (ch < N) { k = 'Service ' + (ch + 1) + ' of ' + N; t = SV[ch].n; d = SV[ch].d; }
    else { k = 'A. Halili Business Aid'; t = 'All-in-one business solutions'; d = 'Registered with SEC and DOLE, serving businesses nationwide.'; }
    capK.textContent = k; capT.textContent = t; capD.textContent = d;
    cta.hidden = ch !== N;
    capEl.classList.remove('swap'); void capEl.offsetWidth;
    if (!reduce) capEl.classList.add('swap');
    railBtns.forEach(function (b, i) { b.classList.toggle('done', i < ch); b.classList.toggle('on', i === ch); });
  }
  function skipFilm() { T = END; playing = false; }
  document.getElementById('skip').addEventListener('click', skipFilm);
  document.getElementById('replay').addEventListener('click', function () {
    T = 0; playing = true;
    if (window.scrollY > 4) { waitTop = true; window.scrollTo({ top: 0, behavior: 'smooth' }); }
  });
  if (reduce) document.getElementById('replay').textContent = 'Play intro';
  svcItems.forEach(function (li, i) {
    li.addEventListener('pointerenter', function () { hoverIdx = i; });
    li.addEventListener('pointerleave', function () { hoverIdx = -1; });
    li.addEventListener('focus', function () { hoverIdx = i; });
    li.addEventListener('blur', function () { hoverIdx = -1; });
  });
  setCaption(chapter(T));

  /* ---------- 3D scene ---------- */
  var canvas = document.getElementById('scene');
  var fallbackEl = document.getElementById('fallback');
  var renderer = null;
  try {
    if (typeof THREE === 'undefined') throw new Error('no three');
    renderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: true });
  } catch (err) { renderer = null; }
  if (!renderer) {
    if (window.console) console.warn('AHBA: the 3D scene did not start. three.js did not load, or WebGL is unavailable. Add /dashboard/three.min.js to fix this.');
    fallbackEl.hidden = false; canvas.style.display = 'none';
    T = END; playing = false; setCaption(N);
    document.getElementById('ctl').hidden = true;
    return;
  }

  function css(name) { return getComputedStyle(root).getPropertyValue(name).trim(); }
  function rng(a) {
    return function () {
      a |= 0; a = a + 0x6D2B79F5 | 0;
      var t = Math.imul(a ^ a >>> 15, 1 | a);
      t = t + Math.imul(t ^ t >>> 7, 61 | t) ^ t;
      return ((t ^ t >>> 14) >>> 0) / 4294967296;
    };
  }
  var rand = rng(2003);
  var narrow = window.innerWidth < 760;

  var scene = new THREE.Scene();
  scene.fog = new THREE.Fog(0xf4f8f2, 55, 130);
  var camera = new THREE.PerspectiveCamera(42, 1, 0.1, 300);
  var hemi = new THREE.HemisphereLight(0xffffff, 0x7f9f86, 0.95); scene.add(hemi);
  var keyL = new THREE.DirectionalLight(0xffffff, 0.95); keyL.position.set(-12, 18, 26); scene.add(keyL);
  var rimL = new THREE.DirectionalLight(0xbfffd0, 0.45); rimL.position.set(16, -6, -10); scene.add(rimL);

  /* ---- the logo, rebuilt as 3D from its own shapes (logo pixel space to world units) ---- */
  var S = 0.011, CX = 1000, CY = 625;
  function Wv(px, py) { return new THREE.Vector2((px - CX) * S, -(py - CY) * S); }
  function Ws(arr) { return arr.map(function (p) { return Wv(p[0], p[1]); }); }
  function mk(color) { return new THREE.MeshStandardMaterial({ color: color, roughness: 0.42, metalness: 0.12, emissive: 0x03300a, emissiveIntensity: 0.6 }); }
  var matBarR = mk(0x087a1c), matBarL = mk(0x0c8f23), matSw = mk(0x16a832), matSw2 = mk(0x1fb83a), matAr = mk(0x0d8a22);
  function solid(shape, depth, mat) {
    var g = new THREE.ExtrudeGeometry(shape, { depth: depth, bevelEnabled: true, bevelThickness: 0.07, bevelSize: 0.07, bevelSegments: 2, curveSegments: 14 });
    g.translate(0, 0, -depth / 2);
    return new THREE.Mesh(g, mat);
  }

  /* right bar of the A */
  var shR = new THREE.Shape();
  var a0 = Wv(655, 150); shR.moveTo(a0.x, a0.y);
  var a1 = Wv(790, 122); shR.lineTo(a1.x, a1.y);
  shR.splineThru(Ws([[880, 270], [950, 410], [985, 560], [975, 700], [940, 800], [913, 872]]));
  shR.splineThru(Ws([[905, 760], [890, 640], [850, 520], [790, 400], [720, 290], [648, 195]]));
  shR.lineTo(a0.x, a0.y);
  var gRight = new THREE.Group(); gRight.add(solid(shR, 1.6, matBarR)); scene.add(gRight);

  /* left bar of the A */
  var shL = new THREE.Shape();
  var b0 = Wv(470, 1000); shL.moveTo(b0.x, b0.y);
  shL.splineThru(Ws([[478, 880], [520, 740], [585, 560], [650, 430], [712, 368]]));
  shL.splineThru(Ws([[690, 440], [655, 540], [625, 650], [600, 760], [582, 880], [640, 1085]]));
  var b1 = Wv(612, 1130); shL.lineTo(b1.x, b1.y);
  var b2 = Wv(490, 1095); shL.lineTo(b2.x, b2.y);
  shL.lineTo(b0.x, b0.y);
  var gLeft = new THREE.Group(); gLeft.add(solid(shL, 1.4, matBarL)); scene.add(gLeft);

  /* swoosh: three nested ribbons that taper to points at both ends */
  var center = Ws([[770, 1130], [1050, 1055], [1300, 900], [1500, 700], [1620, 520], [1640, 390], [1560, 300], [1380, 278], [1150, 335], [920, 452]]);
  var ctr = Wv(1150, 700);
  function ribbon(offPx, widPx, depth, mat) {
    var pts = new THREE.SplineCurve(center).getPoints(90), L = [], Rr = [], n = pts.length;
    for (var i = 0; i < n; i++) {
      var t = i / (n - 1), p = pts[i], pa = pts[Math.max(0, i - 1)], pb = pts[Math.min(n - 1, i + 1)];
      var tg = pb.clone().sub(pa).normalize(), nm = new THREE.Vector2(-tg.y, tg.x);
      if (nm.dot(ctr.clone().sub(p)) < 0) nm.negate();
      var env = Math.sin(Math.PI * t), c = p.clone().addScaledVector(nm, offPx * S * env), h = widPx * S * Math.pow(env, 0.8) / 2;
      L.push(c.clone().addScaledVector(nm, h)); Rr.push(c.clone().addScaledVector(nm, -h));
    }
    var sh = new THREE.Shape(L.concat(Rr.reverse()));
    return solid(sh, depth, mat);
  }
  var gSw = new THREE.Group();
  gSw.add(ribbon(0, 88, 0.5, matSw)); gSw.add(ribbon(66, 28, 0.4, matSw2)); gSw.add(ribbon(112, 17, 0.32, matSw2));
  scene.add(gSw);

  /* arrow */
  var gAr = new THREE.Group();
  (function () {
    var p0 = Wv(830, 492), p1 = Wv(392, 905), d = p1.clone().sub(p0).normalize(), nm = new THREE.Vector2(-d.y, d.x), w0 = 11 * S, w1 = 6 * S;
    var shaft = new THREE.Shape([p0.clone().addScaledVector(nm, w0), p1.clone().addScaledVector(nm, w1), p1.clone().addScaledVector(nm, -w1), p0.clone().addScaledVector(nm, -w0)]);
    gAr.add(solid(shaft, 0.35, matAr));
    gAr.add(solid(new THREE.Shape(Ws([[343, 943], [372, 880], [394, 903], [433, 895]])), 0.35, matAr));
  })();
  scene.add(gAr);

  /* wordmark as a textured plane in front of the logo */
  var wmCanvas = document.createElement('canvas'); wmCanvas.width = 2048; wmCanvas.height = 512;
  var wmTex = new THREE.CanvasTexture(wmCanvas);
  function drawWordmark() {
    var c = wmCanvas.getContext('2d'), fam = "Anton, Impact, 'Arial Narrow', sans-serif";
    c.clearRect(0, 0, 2048, 512); c.lineJoin = 'round'; c.textBaseline = 'alphabetic';
    c.font = '340px ' + fam; var w1 = c.measureText('AHBA').width;
    c.font = '250px ' + fam; var w2 = c.measureText('Development').width;
    var gap = 44, k = Math.min(1, 1960 / (w1 + gap + w2)), x = (2048 - (w1 + gap + w2) * k) / 2, base = 400;
    var olive = css('--olive') || '#6B6A0A';
    [['AHBA', 340, 0, w1], ['Development', 250, w1 + gap, w2]].forEach(function (s) {
      c.font = Math.round(s[1] * k) + 'px ' + fam;
      var px = x + s[2] * k;
      c.strokeStyle = '#ffffff'; c.lineWidth = 30 * k; c.strokeText(s[0], px, base);
      c.fillStyle = olive; c.fillText(s[0], px, base);
    });
    wmTex.needsUpdate = true;
  }
  var wmMat = new THREE.MeshBasicMaterial({ map: wmTex, transparent: true, depthWrite: false });
  var wm = new THREE.Mesh(new THREE.PlaneGeometry(18, 4.5), wmMat);
  wm.position.set(0.4, -0.3, 1.5);
  scene.add(wm);

  /* floor: soft shade plus a ground plan */
  var shCanvas = document.createElement('canvas'); shCanvas.width = shCanvas.height = 256;
  (function () {
    var c = shCanvas.getContext('2d'), g = c.createRadialGradient(128, 128, 0, 128, 128, 128);
    g.addColorStop(0, '#fff'); g.addColorStop(1, '#000'); c.fillStyle = g; c.fillRect(0, 0, 256, 256);
  })();
  var shadeMat = new THREE.MeshBasicMaterial({ color: 0x0a3a12, transparent: true, opacity: 0.28, alphaMap: new THREE.CanvasTexture(shCanvas), depthWrite: false });
  var floor = new THREE.Mesh(new THREE.PlaneGeometry(46, 46), shadeMat);
  floor.rotation.x = -Math.PI / 2; floor.position.y = -8; scene.add(floor);
  var ringMat = new THREE.LineBasicMaterial({ color: 0x0a7a1d, transparent: true, opacity: 0.22 });
  var plan = new THREE.Group(); plan.position.y = -7.98;
  [7, 11, 16, 22, 29, 37].forEach(function (r) {
    var pts = []; for (var k = 0; k <= 128; k++) { var a = k / 128 * TAU; pts.push(new THREE.Vector3(Math.cos(a) * r, 0, Math.sin(a) * r)); }
    plan.add(new THREE.Line(new THREE.BufferGeometry().setFromPoints(pts), ringMat));
  });
  var rad = [];
  for (var q = 0; q < 28; q++) { var a2 = q / 28 * TAU; rad.push(Math.cos(a2) * 4, 0, Math.sin(a2) * 4, Math.cos(a2) * 44, 0, Math.sin(a2) * 44); }
  var radGeo = new THREE.BufferGeometry(); radGeo.setAttribute('position', new THREE.Float32BufferAttribute(rad, 3));
  plan.add(new THREE.LineSegments(radGeo, ringMat));
  scene.add(plan);

  /* seven forms riding the swoosh, one per service */
  var geos = [
    new THREE.OctahedronGeometry(0.95), new THREE.IcosahedronGeometry(0.85), new THREE.TorusGeometry(0.7, 0.24, 12, 40),
    new THREE.DodecahedronGeometry(0.85), new THREE.BoxGeometry(1.1, 1.1, 1.1), new THREE.ConeGeometry(0.8, 1.6, 5),
    new THREE.TorusKnotGeometry(0.55, 0.17, 72, 8)
  ];
  var modCurve = new THREE.CatmullRomCurve3(center.map(function (v) { return new THREE.Vector3(v.x, v.y, 0); }));
  var modBase = [], mods = [], tagWrap = document.getElementById('tags');
  var moduleMat = new THREE.MeshStandardMaterial({ color: 0x1fb83a, roughness: 0.3, metalness: 0.25, flatShading: true, emissive: 0x05400f, emissiveIntensity: 0.7 });
  for (var s = 0; s < N; s++) {
    var tt = 0.1 + s * (0.8 / (N - 1)), bp = modCurve.getPoint(tt);
    modBase.push(bp);
    var grp = new THREE.Group(), geo = geos[s % geos.length];
    var em = new THREE.LineBasicMaterial({ color: 0x0d2a15, transparent: true, opacity: 0.6 });
    grp.add(new THREE.Mesh(geo, moduleMat)); grp.add(new THREE.LineSegments(new THREE.EdgesGeometry(geo, 20), em));
    scene.add(grp);
    var tg = document.createElement('div'); tg.className = 'tag';
    tg.innerHTML = '<b>' + (s + 1) + '</b><span></span>'; tg.lastChild.textContent = SV[s].n;
    tagWrap.appendChild(tg);
    mods.push({ g: grp, em: em, tag: tg, k: 0, op: 0.6 });
  }

  /* particles gather onto the logo */
  var PC = narrow ? 800 : 1500;
  var pS = new Float32Array(PC * 3), pE = new Float32Array(PC * 3), pPh = new Float32Array(PC), pDl = new Float32Array(PC);
  function inPoly(x, y, poly) {
    var c = false;
    for (var i = 0, j = poly.length - 1; i < poly.length; j = i++) {
      if (((poly[i].y > y) !== (poly[j].y > y)) && (x < (poly[j].x - poly[i].x) * (y - poly[i].y) / (poly[j].y - poly[i].y) + poly[i].x)) c = !c;
    }
    return c;
  }
  var polyR = shR.getPoints(14), polyL = shL.getPoints(14), swPts = new THREE.SplineCurve(center).getPoints(90);
  function sampleIn(poly) {
    var mnx = 1e9, mxx = -1e9, mny = 1e9, mxy = -1e9;
    poly.forEach(function (p) { mnx = Math.min(mnx, p.x); mxx = Math.max(mxx, p.x); mny = Math.min(mny, p.y); mxy = Math.max(mxy, p.y); });
    for (var tries = 0; tries < 40; tries++) { var x = mnx + rand() * (mxx - mnx), y = mny + rand() * (mxy - mny); if (inPoly(x, y, poly)) return [x, y]; }
    return [poly[0].x, poly[0].y];
  }
  for (var p = 0; p < PC; p++) {
    var u = rand(), vv = rand(), rs = 30 + rand() * 40, th = TAU * u, ph = Math.acos(2 * vv - 1);
    pS[p * 3] = Math.sin(ph) * Math.cos(th) * rs; pS[p * 3 + 1] = Math.cos(ph) * rs * 0.7; pS[p * 3 + 2] = Math.sin(ph) * Math.sin(th) * rs;
    var kind = rand(), ex, ey, ez = (rand() - 0.5) * 1.4, pt;
    if (kind < 0.28) { pt = sampleIn(polyR); ex = pt[0]; ey = pt[1]; }
    else if (kind < 0.46) { pt = sampleIn(polyL); ex = pt[0]; ey = pt[1]; }
    else if (kind < 0.9) { var si = Math.floor(rand() * 91), sp = swPts[si]; ex = sp.x + (rand() - 0.5) * 0.9 * Math.sin(Math.PI * si / 90); ey = sp.y + (rand() - 0.5) * 0.9 * Math.sin(Math.PI * si / 90); ez = (rand() - 0.5) * 0.6 - 0.4; }
    else { ex = (rand() - 0.5) * 24; ey = (rand() - 0.5) * 14; ez = (rand() - 0.5) * 8; }
    pE[p * 3] = ex; pE[p * 3 + 1] = ey; pE[p * 3 + 2] = ez;
    pPh[p] = rand() * TAU; pDl[p] = rand() * 1.4;
  }
  var pGeo = new THREE.BufferGeometry();
  pGeo.setAttribute('position', new THREE.BufferAttribute(new Float32Array(PC * 3), 3));
  var pointMat = new THREE.PointsMaterial({ color: 0x0a7a1d, size: 0.11, transparent: true, opacity: 0.9, depthWrite: false });
  scene.add(new THREE.Points(pGeo, pointMat));

  function applyTheme() {
    var bg = css('--scene-bg');
    renderer.setClearColor(bg, 1); scene.fog.color.set(bg);
    var accent = css('--accent');
    pointMat.color.set(accent); ringMat.color.set(accent);
    shadeMat.color.set(css('--shade')); shadeMat.opacity = parseFloat(css('--shade-op')) || 0.28;
    mods.forEach(function (m) { m.em.color.set(css('--edge')); });
    hemi.intensity = parseFloat(css('--amb')) || 0.9;
    var dk = hemi.intensity < 0.8;
    hemi.groundColor.set(dk ? 0x0a1a10 : 0x7f9f86);
    keyL.intensity = dk ? 1.1 : 0.95;
    drawWordmark();
  }
  applyTheme();
  new MutationObserver(applyTheme).observe(root, { attributes: true, attributeFilter: ['data-theme'] });
  try { darkQuery.addEventListener('change', applyTheme); } catch (e) { darkQuery.addListener(applyTheme); }
  if (document.fonts && document.fonts.load) document.fonts.load('400 100px Anton').then(drawWordmark, function () {});

  /* camera poses */
  var V = function (x, y, z) { return new THREE.Vector3(x, y, z); };
  var freeCam = new THREE.CatmullRomCurve3([V(0, 0.5, 31), V(22, 3, 22), V(-22, 5, 22), V(6, -6, 24), V(0, 26, 18)], false, 'catmullrom', 0.4);
  var freeLook = new THREE.CatmullRomCurve3([V(0, 0, 0), V(3, -1, 0), V(0, 0, 0), V(0, 2, 0), V(0, -2, 0)], false, 'catmullrom', 0.4);
  var ids = ['home', 'services', 'about', 'management', 'contact'], anchors = [];
  var heroEl = document.getElementById('home'), heroH = 800;
  function measure() {
    var sy = window.scrollY, docH = document.documentElement.scrollHeight;
    heroH = heroEl.offsetHeight;
    anchors = ids.map(function (id) { var r = document.getElementById(id).getBoundingClientRect(); return r.top + sy + r.height / 2; });
    anchors[0] = 0;
    anchors[anchors.length - 1] = Math.min(anchors[anchors.length - 1], docH - window.innerHeight / 2);
    for (var k = 1; k < anchors.length; k++) if (anchors[k] <= anchors[k - 1]) anchors[k] = anchors[k - 1] + 1;
  }
  function progress() {
    var y = window.scrollY + window.innerHeight / 2;
    if (window.scrollY <= 1 || y <= anchors[0]) return 0;
    for (var k = 0; k < anchors.length - 1; k++) {
      if (y < anchors[k + 1]) return (k + (y - anchors[k]) / (anchors[k + 1] - anchors[k])) / (anchors.length - 1);
    }
    return 1;
  }
  function resize() {
    var w = window.innerWidth, h = window.innerHeight;
    narrow = w < 760;
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.75));
    renderer.setSize(w, h, false);
    camera.aspect = w / h; camera.fov = w / h < 1 ? 62 : 42; camera.updateProjectionMatrix();
    measure();
  }
  window.addEventListener('resize', resize);
  resize();
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(measure);
  if (window.ResizeObserver) new ResizeObserver(measure).observe(document.body);

  var clock = 0;
  var cp = new THREE.Vector3(), lp = new THREE.Vector3(), tp = new THREE.Vector3(), tl = new THREE.Vector3(), tmp = new THREE.Vector3(), mp = new THREE.Vector3();
  var camInit = false;
  function modPos(i, o) {
    var b = modBase[i];
    return o.set(b.x, b.y + Math.sin(clock * 0.9 + i) * 0.18, 1.7 + Math.cos(clock * 0.7 + i * 1.3) * 0.15);
  }

  var last = performance.now(), act = -1;
  function frame(now) {
    var dt = Math.min((now - last) / 1000, 0.05);
    last = now;
    var sy = window.scrollY;

    if (waitTop) { if (sy < 4) waitTop = false; }
    else if (playing) {
      if (sy > 80) skipFilm();
      else { T += dt; if (T >= END) skipFilm(); }
    }
    if (!reduce) clock += dt;
    var ch = chapter(T);
    setCaption(ch);
    act = (ch >= 0 && ch < N) ? ch : hoverIdx;

    /* logo assembles; every pose is a function of T */
    var eR = ease((T - 0.4) / 1.4); gRight.position.set(0, (1 - eR) * 16, 0.3); gRight.rotation.z = (1 - eR) * 0.5; gRight.visible = eR > 0.001;
    var eL = ease((T - 0.8) / 1.4); gLeft.position.set(0, -(1 - eL) * 16, 0); gLeft.rotation.z = -(1 - eL) * 0.5; gLeft.visible = eL > 0.001;
    var eS = ease((T - 1.3) / 1.7); gSw.rotation.y = -(1 - eS) * Math.PI * 1.2; gSw.scale.setScalar(0.3 + 0.7 * eS); gSw.position.z = -0.4; gSw.visible = eS > 0.001;
    var eA = ease((T - 2.1) / 1.0); gAr.position.set((1 - eA) * 8, (1 - eA) * 6, 0.3); gAr.visible = eA > 0.001;
    wmMat.opacity = ease((T - 2.7) / 0.9);
    plan.scale.setScalar(Math.max(0.001, ease(T / 1.6)));
    pointMat.opacity = 0.9 - 0.5 * ease((T - 3) / 1);

    var arr = pGeo.attributes.position.array;
    for (var p = 0; p < PC; p++) {
      var e = ease((T - pDl[p]) / 1.9), ph = pPh[p], dr = Math.sin(clock * 0.6 + ph) * 0.1 * e;
      arr[p * 3] = pS[p * 3] + (pE[p * 3] - pS[p * 3]) * e + dr;
      arr[p * 3 + 1] = pS[p * 3 + 1] + (pE[p * 3 + 1] - pS[p * 3 + 1]) * e + Math.cos(clock * 0.5 + ph) * 0.1 * e;
      arr[p * 3 + 2] = pS[p * 3 + 2] + (pE[p * 3 + 2] - pS[p * 3 + 2]) * e;
    }
    pGeo.attributes.position.needsUpdate = true;

    /* service forms */
    for (var m = 0; m < N; m++) {
      var md = mods[m], ap = ease((T - (3.0 + m * 0.1)) / 0.6), isAct = m === act;
      modPos(m, mp); md.g.position.copy(mp);
      md.g.rotation.y += dt * (isAct ? 1.4 : 0.4); md.g.rotation.x += dt * 0.2;
      md.k += ((isAct ? 1.7 : 1) - md.k) * (1 - Math.exp(-dt * 6));
      md.g.scale.setScalar(Math.max(0.001, ap * md.k));
      md.op += ((isAct ? 1 : 0.6) - md.op) * (1 - Math.exp(-dt * 6));
      md.em.opacity = md.op * ap;
    }

    /* camera target */
    var useIntro = playing && !waitTop && sy <= 80 && ch < N, sway = reduce ? 0 : Math.sin(clock * 0.3);
    if (useIntro) {
      if (ch < 0) {
        var pe = ease(T / PRE), a = 0.9 * (1 - pe), r = 36 - 5 * pe;
        tp.set(Math.sin(a) * r, 4 * (1 - pe) + 0.5, Math.cos(a) * r); tl.set(0, 0, 0);
        if (narrow) tp.multiplyScalar(1.25);
      } else {
        modPos(ch, mp);
        tp.set(mp.x * 0.78 + sway, mp.y * 0.78, 11.5 + (narrow ? 3 : 0));
        tl.set(mp.x * 0.85, mp.y * 0.85, 0);
      }
    } else {
      var pr = progress();
      freeCam.getPoint(pr, tp); freeLook.getPoint(pr, tl);
      tp.x += sway * 2.2;
      if (narrow) tp.multiplyScalar(1.25);
    }
    if (!camInit) { cp.copy(tp); lp.copy(tl); camInit = true; }
    var k = reduce ? 1 : 1 - Math.exp(-dt * (useIntro ? 2.4 : 3));
    cp.lerp(tp, k); lp.lerp(tl, k);
    camera.position.copy(cp); camera.lookAt(lp);
    camera.updateMatrixWorld();

    /* labels */
    var W = window.innerWidth, H = window.innerHeight, heroVis = sy < heroH * 0.6, showAll = !narrow && heroVis && ch >= 0;
    for (var t2 = 0; t2 < N; t2++) {
      var tg2 = mods[t2].tag;
      tmp.copy(mods[t2].g.position).project(camera);
      var vis = tmp.z < 1 && Math.abs(tmp.x) < 1.05 && Math.abs(tmp.y) < 1.05;
      var op = !vis ? 0 : (t2 === act ? 1 : (showAll ? 0.45 : 0));
      if (t2 === act && !heroVis && hoverIdx !== t2) op = 0;
      tg2.style.opacity = op;
      tg2.style.transform = 'translate(' + ((tmp.x * 0.5 + 0.5) * W + 18).toFixed(1) + 'px,' + ((-tmp.y * 0.5 + 0.5) * H - 9).toFixed(1) + 'px)';
    }

    renderer.render(scene, camera);
    requestAnimationFrame(frame);
  }
  requestAnimationFrame(frame);
})();
