/* 3D-примерочная «Монолит-Вест»: участок, дом, забор, зоны плитки, план, смета, отправка проекта. */
import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { mergeGeometries } from 'three/addons/utils/BufferGeometryUtils.js';
import { RGBELoader } from 'three/addons/loaders/RGBELoader.js';
import { formSpec, period, PATTERN_NAMES, PATTERN_ROT, rng, vn } from 'tryon/paving';

const D = JSON.parse(document.getElementById('tryon-data').textContent);
const $ = (s, r) => (r || document).querySelector(s), $$ = (s, r) => [...(r || document).querySelectorAll(s)];
const fmt = n => Math.round(n).toLocaleString('ru-RU');
const plural = (n, one, few, many) => { const a = n % 100, b = n % 10; return a > 10 && a < 20 ? many : b === 1 ? one : b > 1 && b < 5 ? few : many; };
const MOBILE = Math.min(screen.width, screen.height) < 700 || matchMedia('(pointer:coarse)').matches;
const ASSET = D.base + '/3d-assets/';
const view = $('#to-view'), body = $('[data-body]');

/* ---------- справочники ---------- */
const FACADE_MATS = { plaster: 'Штукатурка', siding: 'Сайдинг', brick: 'Кирпич', wood: 'Дерево' };
const ROOF_MATS = { metal: 'Металлочерепица', prof: 'Профлист', soft: 'Мягкая кровля' };
const FACADE_COLORS = [['Белый', '#f2f0ea'], ['Слоновая кость', '#ece3cf'], ['Бежевый', '#dccbad'], ['Песочный', '#d2b98e'], ['Светло-серый', '#c9c7c2'], ['Графит', '#55585b'], ['Терракота', '#b5654a'], ['Оливковый', '#8a8a5c']];
const BRICK_COLORS = [['Красный', '#9a4632'], ['Коричневый', '#6e4636'], ['Солома', '#d9c08f'], ['Белый', '#e6e2d8'], ['Серый', '#8f8c88']];
const WOOD_COLORS = [['Сосна', '#d6a96f'], ['Тик', '#a8743f'], ['Орех', '#7a5236'], ['Венге', '#4a3528'], ['Серый', '#9a958c']];
const RAL = [['RAL 3005 винно-красный', '#5e2028'], ['RAL 3011 коричнево-красный', '#781f19'], ['RAL 8017 шоколад', '#45322e'], ['RAL 7024 графит', '#474a51'], ['RAL 6005 зелёный мох', '#114232'], ['RAL 5005 синий', '#1e2460'], ['RAL 9005 чёрный', '#0a0a0a'], ['RAL 7004 серый', '#969992']];
const PLINTH_COLORS = [['Бетон', '#a29c94'], ['Тёмно-серый', '#5d5a56'], ['Кирпич', '#8a4a38'], ['Бежевый', '#c4b49a']];
const WIN_COLORS = [['Белые', '#f4f2ee'], ['Под дерево', '#6b4a2f'], ['Антрацит', '#3a3d40']];
const DOOR_COLORS = [['Дерево', '#5a3825'], ['Антрацит', '#33363a'], ['Белая', '#ece9e2'], ['Бордовая', '#6e1d22']];
const FENCE_TYPES = { none: 'Без забора', shtaketnik: 'Штакетник', evro: 'Евроштакетник', prof: 'Профлист', brick: 'Кирпичные столбы', setka: 'Сетка' };
const FENCE_COLORS = [['Белый', '#ece6da'], ['Дерево', '#a8743f'], ...RAL];
const HOUSES = {
  one:    { name: 'Одноэтажный', sub: '11×8 м', w: 11, d: 8, h: 3, floors: 1, pitch: .62, door: -2 },
  two:    { name: 'Двухэтажный', sub: '9×9 м', w: 9, d: 9, h: 5.6, floors: 2, pitch: .7, door: -1.5 },
  garage: { name: 'С гаражом', sub: '9×9 м + гараж', w: 9, d: 9, h: 5.6, floors: 2, pitch: .7, door: -1.5, garage: true },
  wide:   { name: 'Широкий', sub: '14×8 м, с террасой', w: 14, d: 8, h: 3, floors: 1, pitch: .5, door: 0 },
};
const FORMS = Object.fromEntries(D.forms.map(f => [f.slug, f]));
const KERB_DEF = Math.max(0, D.kerbs.findIndex(k => /20\.8/.test(k.name)));

/* ---------- шаблоны участков (координаты для участка 24×24, улица со стороны +z) ---------- */
let zid = 1;
const Z = (x1, z1, x2, z2, o = {}) => ({ id: zid++, x1, z1, x2, z2, kind: 'pave', form: 'kirpich', color: 'osen', pat: 'perevyazka', th: 60, kayma: '', kerb: true, kerbType: KERB_DEF, ...o });
const TEMPLATES = {
  1: { name: 'Дорожка и терраса', sub: 'одноэтажный дом', make: () => ({ house: { type: 'one', x: 0, z: -4 },
    zones: [Z(-5.5, 0, 5.5, 1.2, { color: 'kapuchino' }), Z(-2.7, 1.2, -1.3, 12), Z(1.5, 1.2, 5.5, 4.4, { pat: 'elka' })],
    extras: [['table', 3.5, 2.8]], trees: [[-8.5, 5, 1.05, 'birch'], [10, -8.5, 1, 'maple'], [-9.5, -8, 1, 'spruce'], [-10, 10, .9, 'maple']], bushes: [[-4.6, 1.9, 1], [-3.7, 2.5, .8], [-.4, 1.9, .9], [6.4, 1.6, 1.1]] }) },
  2: { name: 'Въезд под машину', sub: 'дом с гаражом', make: () => ({ house: { type: 'garage', x: -3, z: -4.5 },
    zones: [Z(1.4, 0, 6.4, 12, { form: 'novyj-gorod', color: 'noch', pat: 'haos' }), Z(-7.5, 0, 1.4, 1, { color: 'noch' }), Z(-5.2, 1, -3.8, 4, { color: 'noch' }), Z(-5.2, 2.6, 1.4, 4, { color: 'noch' })],
    extras: [['car', 3.9, 6.6]], trees: [[-9.5, 6, 1.05, 'maple'], [-10, 10.5, 1, 'birch'], [-10, -7, 1.05, 'spruce'], [9.8, -8, .95, 'birch']], bushes: [[-7, 2, 1], [-6.2, 2.7, .8], [-1.5, 1.8, .9]] }) },
  3: { name: 'Двор с беседкой', sub: 'терраса и площадка', make: () => ({ house: { type: 'wide', x: 0, z: -7 },
    zones: [Z(-7, -3, 7, .6, { form: 'staryj-gorod', color: 'yantar', pat: 'haos' }), Z(1.6, .6, 3, 5.6, { form: 'staryj-gorod', color: 'yantar', pat: 'haos' }), Z(.3, 5.6, 5.3, 10, { form: 'parket', color: 'klinker', pat: 'elka' }), Z(-5, .6, -3.6, 8, { form: 'staryj-gorod', color: 'yantar', pat: 'haos' })],
    extras: [['gazebo', 2.8, 7.8], ['table', 2.8, 7.8], ['table', -3, -1.2]], trees: [[-9.5, 3, 1.05, 'birch'], [10, -9, 1, 'spruce'], [-10, 10.5, .9, 'maple'], [9.8, -2, .9, 'birch']], bushes: [[-1.5, 1.4, 1], [4.5, 1.4, .9], [6, 4.5, 1.1]] }) },
  4: { name: 'Парковка на газоне', sub: 'газонная решётка', make: () => ({ house: { type: 'one', x: 0, z: -5 },
    zones: [Z(-5.5, -1, 5.5, .2, { color: 'seryj' }), Z(1.5, .2, 7, 12, { kind: 'grid', kerb: false }), Z(-2.7, .2, -1.3, 12, { color: 'seryj' })],
    extras: [['car', 4.2, 7]], trees: [[-8.5, 5, 1.05, 'birch'], [10.5, -8.5, 1, 'maple'], [-9.5, -8, 1, 'spruce']], bushes: [[-4.6, .8, 1], [-.4, .8, .9]] }) },
};
function fromTemplate(id) {
  const t = TEMPLATES[id].make();
  return { v: 1, tpl: +id, plot: { w: 24, d: 24 }, house: { ...t.house, facade: { m: 'plaster', c: '#ece3cf' }, roof: { m: 'metal', c: '#474a51' }, plinth: '#a29c94', win: '#f4f2ee', door: '#5a3825' },
    fence: { t: 'shtaketnik', c: '#ece6da' }, zones: t.zones, extras: t.extras, trees: t.trees, bushes: t.bushes };
}

/* ---------- состояние, ссылка, история ---------- */
let S = fromTemplate(1), sel = null;           // sel: {kind:'zone', id} | {kind:'house'} | null
const enc = o => btoa(unescape(encodeURIComponent(JSON.stringify(o)))).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
const dec = s => JSON.parse(decodeURIComponent(escape(atob(s.replace(/-/g, '+').replace(/_/g, '/')))));
/* в ссылке не храним деревья и машину шаблона, если их не меняли: ссылка в 2–3 раза короче */
const DECOR = ['trees', 'bushes', 'extras'], tplDecor = {};
function decorOf(tpl) { if (!tplDecor[tpl]) { const keepId = zid, t = TEMPLATES[tpl].make(); zid = keepId; tplDecor[tpl] = JSON.stringify(DECOR.map(k => t[k])); } return JSON.parse(tplDecor[tpl]); }
const ZDEF = () => ({ kind: 'pave', form: 'kirpich', color: 'osen', pat: 'perevyazka', th: 60, kayma: '', kerb: true, kerbType: KERB_DEF });
function pack(o) { const c = JSON.parse(JSON.stringify(o)), zd = ZDEF(); c.zones.forEach(z => { for (const k in zd) if (z[k] === zd[k]) delete z[k]; });
  if (TEMPLATES[c.tpl]) { const d = decorOf(c.tpl); DECOR.forEach((k, i) => { if (JSON.stringify(c[k]) === JSON.stringify(d[i])) delete c[k]; }); } return c; }
function unpack(o) { if (!o || o.v !== 1 || !Array.isArray(o.zones)) return null; o.zones = o.zones.map(z => ({ ...ZDEF(), ...z })); const d = TEMPLATES[o.tpl] ? decorOf(o.tpl) : [[], [], []]; DECOR.forEach((k, i) => { if (!Array.isArray(o[k])) o[k] = d[i]; }); o.plot = o.plot || { w: 24, d: 24 }; return o; }
const LS = { get(k) { try { return JSON.parse(localStorage.getItem(k)); } catch (e) { return null; } }, set(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); return true; } catch (e) { return false; } } };
let fromLink = false;
try { const m = location.hash.match(/p=([\w-]+)/); if (m) { const o = unpack(dec(m[1])); if (o) { S = o; fromLink = true; } } } catch (e) {}
if (!fromLink && !new URLSearchParams(location.search).get('forma')) { const o = unpack(LS.get('mw3d')); if (o) S = o; }   // автосохранение: вернулся и видит свой проект
zid = Math.max(0, ...S.zones.map(z => z.id)) + 1;
const hist = []; let histPos = -1;
function commit() {
  const j = JSON.stringify(S);
  if (hist[histPos] === j) return;
  hist.splice(histPos + 1); hist.push(j); if (hist.length > 50) hist.shift(); histPos = hist.length - 1;
  saveState();
}
function saveState() {
  try { history.replaceState(null, '', location.pathname + location.search + '#p=' + enc(pack(S))); } catch (e) {}
  LS.set('mw3d', pack(S));
  $('[data-undo]').disabled = histPos <= 0; $('[data-redo]').disabled = histPos >= hist.length - 1;
}
function travel(d) { const n = histPos + d; if (n < 0 || n >= hist.length) return; histPos = n; S = JSON.parse(hist[n]); if (sel && sel.kind === 'zone' && !S.zones.some(z => z.id === sel.id)) sel = null; rebuildAll(); renderPanel(); saveState(); }
const undo = () => travel(-1), redo = () => travel(1);

/* наличие и цены */
const formColors = f => Object.keys(D.prices[f] || {});
const ths = (f, c) => Object.keys((D.prices[f] || {})[c] || {}).map(Number);
function fixZone(z) {
  if (z.kind !== 'pave') return;
  if (!D.prices[z.form]) z.form = Object.keys(D.prices)[0];
  if (!D.prices[z.form][z.color]) z.color = formColors(z.form)[0];
  const t = ths(z.form, z.color); if (!t.includes(z.th)) z.th = t[0];
  const pats = formSpec(z.form).patterns; if (!pats.includes(z.pat)) z.pat = pats[0];
}
S.zones.forEach(fixZone);
(function fromCatalog() {   // переход из каталога: ?forma=kirpich&cvet=osen (если ссылки на проект нет)
  const q = new URLSearchParams(location.search), f = q.get('forma'), c = q.get('cvet');
  if (!f || !D.prices[f] || /p=/.test(location.hash)) return;
  S.zones.filter(z => z.kind === 'pave').forEach(z => { z.form = f; if (c && D.prices[f][c]) z.color = c; fixZone(z); });
})();
const zPrice = z => z.kind === 'grid' ? (D.lawn ? D.lawn.price : null) : ((D.prices[z.form] || {})[z.color] || {})[z.th] ?? null;
const swatch = pal => { const tot = pal.reduce((s, p) => s + p[1], 0) || 1; let a = 0; return 'conic-gradient(from 20deg,' + pal.map(([h, w]) => { const s = a / tot * 100; a += w; return `${h} ${s.toFixed(0)}% ${(a / tot * 100).toFixed(0)}%`; }).join(',') + ')'; };
const zoneName = (z, i) => z.kind === 'grid' ? 'Газонная решётка' : ['Площадка', 'Дорожка', 'Отмостка'][(Math.min(z.x2 - z.x1, z.z2 - z.z1) < 1.6) ? 1 : 0] + ' ' + (i + 1);

/* ---------- рендер ---------- */
let renderer;
try { renderer = new THREE.WebGLRenderer({ antialias: true, powerPreference: 'high-performance' }); }
catch (e) { $('[data-loader]').innerHTML = '<div>Ваш браузер не поддерживает 3D. Посмотрите плитку в <a href="' + D.base + '/bruschatka/">каталоге</a>.</div>'; throw e; }
let PR_MAX = Math.min(devicePixelRatio, MOBILE ? 1.5 : 1.25), PR = PR_MAX; const PR_MIN = Math.min(1, devicePixelRatio);
renderer.outputColorSpace = THREE.SRGBColorSpace; renderer.toneMapping = THREE.NeutralToneMapping; renderer.toneMappingExposure = .9;
renderer.shadowMap.enabled = true; renderer.shadowMap.type = THREE.PCFShadowMap; renderer.shadowMap.autoUpdate = false;
view.prepend(renderer.domElement);
let GPU = ''; try { const gl = renderer.getContext(), x = gl.getExtension('WEBGL_debug_renderer_info'); GPU = x ? gl.getParameter(x.UNMASKED_RENDERER_WEBGL) : ''; } catch (e) {}
const WEAK = /Intel|UHD|Iris|Mali|Adreno|PowerVR|SwiftShader|llvmpipe|Microsoft Basic/i.test(GPU), SOFT = /SwiftShader|llvmpipe|Microsoft Basic/i.test(GPU);
if (WEAK) PR = Math.min(PR, MOBILE ? 1.25 : 1);
renderer.setPixelRatio(PR);
const ANISO = Math.min(MOBILE ? 4 : 8, renderer.capabilities.getMaxAnisotropy());

const scene = new THREE.Scene();
scene.background = new THREE.Color('#dfe8ee'); scene.fog = new THREE.FogExp2('#e3e9ec', .0095);
const camera = new THREE.PerspectiveCamera(42, 1, .25, 500);
const controls = new OrbitControls(camera, renderer.domElement);
controls.enableDamping = true; controls.dampingFactor = .08; controls.maxPolarAngle = Math.PI / 2 - .07; controls.minDistance = 1.6; controls.maxDistance = 80;
const hemi = new THREE.HemisphereLight('#dfeaf6', '#6d7652', 1.1); scene.add(hemi);
const sun = new THREE.DirectionalLight('#fff0db', 2.7); sun.position.set(18, 26, 22); sun.castShadow = true;
const SM = (MOBILE || WEAK) ? 1024 : 2048; sun.shadow.mapSize.set(SM, SM); if (SOFT) renderer.shadowMap.enabled = false;
sun.shadow.bias = -.00025; sun.shadow.normalBias = .025; scene.add(sun, sun.target);
let dirty = true; const redraw = (shadows = true) => { dirty = true; if (shadows) renderer.shadowMap.needsUpdate = true; };

/* ---------- текстуры ---------- */
const texLoader = new THREE.TextureLoader(), TEX = {}; let assetsTotal = 0, assetsDone = 0, assetsResolve; const assetsReady = new Promise(r => assetsResolve = r);
function progress() { assetsDone++; const lp = $('[data-lp]'); if (lp) lp.textContent = Math.round(assetsDone / assetsTotal * 100) + '%'; if (assetsDone >= assetsTotal) assetsResolve(); redraw(); }
function loadTex(key, file, srgb) { assetsTotal++; const e = TEX[key] = { tex: null, w: [] };
  texLoader.load(ASSET + file, t => { t.wrapS = t.wrapT = THREE.RepeatWrapping; t.anisotropy = ANISO; if (srgb) t.colorSpace = THREE.SRGBColorSpace; e.tex = t; e.w.splice(0).forEach(f => f(t)); progress(); }, undefined, () => progress()); }
const withTex = (key, fn) => { const e = TEX[key]; if (e.tex) fn(e.tex); else e.w.push(fn); };
[['plaster', 'plaster'], ['roof', 'roof'], ['wood', 'wood'], ['concrete', 'concrete'], ['grass', 'grass']].forEach(([k, f]) => { loadTex(k + '_d', f + '_diff.jpg', true); loadTex(k + '_n', f + '_nor.jpg', false); });
function tiled(t, size) { const c = t.clone(); c.repeat.set(1 / size, 1 / size); c.needsUpdate = true; return c; }
function dataTex(a, W, H, srgb, size) { const t = new THREE.DataTexture(a, W, H, THREE.RGBAFormat); t.wrapS = t.wrapT = THREE.RepeatWrapping; t.magFilter = THREE.LinearFilter; t.minFilter = THREE.LinearMipmapLinearFilter; t.generateMipmaps = true; t.anisotropy = ANISO; if (srgb) t.colorSpace = THREE.SRGBColorSpace; if (size) t.repeat.set(1 / size, 1 / size); t.needsUpdate = true; return t; }
/* процедурная текстура по функции высоты/цвета: возвращает {map, normal} */
function procTex(W, H, fn, size, strength = 2) {
  const col = new Uint8Array(W * H * 4), hgt = new Float32Array(W * H);
  for (let y = 0; y < H; y++) for (let x = 0; x < W; x++) { const [v, h] = fn(x, y), i = y * W + x; col[i * 4] = col[i * 4 + 1] = col[i * 4 + 2] = v; col[i * 4 + 3] = 255; hgt[i] = h; }
  const nrm = new Uint8Array(W * H * 4);
  for (let y = 0; y < H; y++) for (let x = 0; x < W; x++) {
    const dx = (hgt[y * W + (x + 1) % W] - hgt[y * W + (x - 1 + W) % W]) * strength, dy = (hgt[((y + 1) % H) * W + x] - hgt[((y - 1 + H) % H) * W + x]) * strength, l = Math.hypot(dx, dy, 1), i = (y * W + x) * 4;
    nrm[i] = (-dx / l * .5 + .5) * 255; nrm[i + 1] = (dy / l * .5 + .5) * 255; nrm[i + 2] = (1 / l * .5 + .5) * 255; nrm[i + 3] = 255;
  }
  return { map: dataTex(col, W, H, true, size), normal: dataTex(nrm, W, H, false, size) };
}
const R0 = rng(11), NOISE = new Float32Array(65536).map(() => R0());
const nz = (x, y) => NOISE[((x * 73) ^ (y * 151)) & 65535];
const BRICK = procTex(260, 300, (x, y) => { const row = Math.floor(y / 20), off = row % 2 ? 32 : 0, bx = (x + off) % 65, by = y % 20; const m = bx < 3 || by < 3; const b = Math.floor((x + off) / 65) * 7 + row * 13; return m ? [228, 0] : [200 + (nz(b, 1) - .5) * 50 + (nz(x, y) - .5) * 16, 1]; }, 1.0, 3);
const METALTILE = procTex(128, 128, (x, y) => { const wv = Math.sin(x / 128 * Math.PI * 2 * 4), step = (y % 64) / 64; const h = wv * .5 + (step < .12 ? -1 : step * .3); return [205 + wv * 14 - (step < .12 ? 30 : 0), h]; }, .72, 4);
const SHINGLE = procTex(192, 192, (x, y) => { const row = Math.floor(y / 48), off = row % 2 ? 24 : 0, bx = (x + off) % 48, by = y % 48; const gap = bx < 3 || by < 4; const t = Math.floor((x + off) / 48) * 5 + row * 3; return gap ? [70, 0] : [150 + (nz(t, 7) - .5) * 40 + (nz(x, y) - .5) * 40, .6 + by / 48 * .4]; }, .6, 3);
const MESH = (() => { const S = 64, a = new Uint8Array(S * S * 4); for (let y = 0; y < S; y++) for (let x = 0; x < S; x++) { const d1 = Math.abs(((x + y) % 32) - 16), d2 = Math.abs(((x - y + 640) % 32) - 16), on = d1 > 14 || d2 > 14, i = (y * S + x) * 4; a[i] = a[i + 1] = a[i + 2] = 255; a[i + 3] = on ? 255 : 0; } return dataTex(a, S, S, true, .1); })();
const MACRO = (() => { const S = 256, a = new Uint8Array(S * S * 4); for (let y = 0; y < S; y++) for (let x = 0; x < S; x++) { const v = .5 * vn(x / 32, y / 32) + .3 * vn(x / 12 + 20, y / 12 + 7) + .2 * vn(x / 5 + 3, y / 5 + 11), i = (y * S + x) * 4; a[i] = a[i + 1] = a[i + 2] = (.78 + .22 * v) * 255; a[i + 3] = 255; } return dataTex(a, S, S, false); })();

/* ---------- материалы дома и общие ---------- */
const keepSet = new Set(), keep = o => (keepSet.add(o), o);
const M = (c, o = {}) => keep(new THREE.MeshStandardMaterial({ color: c, roughness: .9, ...o }));
const HM = { wall: M('#ece3cf'), roof: M('#474a51', { roughness: .42, metalness: .45 }), plinth: M('#a29c94'), trim: M('#f4f2ee', { roughness: .45 }), door: M('#5a3825', { roughness: .6 }),
  metal: M('#cfd1d2', { metalness: .7, roughness: .35 }), chimney: M('#7f4a3a'), glass: keep(new THREE.MeshPhysicalMaterial({ color: '#1a242c', metalness: .1, roughness: .03, envMapIntensity: 1.6 })),
  fence: M('#ece6da', { roughness: .7 }), fencePost: M('#9b8b74', { roughness: .8 }), mesh: keep(new THREE.MeshStandardMaterial({ color: '#2f5a33', map: MESH, alphaTest: .5, side: THREE.DoubleSide, metalness: .4, roughness: .5 })),
  wood: M('#9a7350', { roughness: .75 }), brickPost: M('#9a4632') };
withTex('concrete_d', t => { HM.plinth.map = tiled(t, 1.4); HM.plinth.needsUpdate = true; }); withTex('concrete_n', t => { HM.plinth.normalMap = tiled(t, 1.4); HM.plinth.needsUpdate = true; });
withTex('wood_d', t => { for (const m of [HM.door, HM.wood]) { m.map = tiled(t, 1.8); m.needsUpdate = true; } }); withTex('wood_n', t => { for (const m of [HM.door, HM.wood]) { m.normalMap = tiled(t, 1.8); m.needsUpdate = true; } });
HM.brickPost.map = BRICK.map; HM.brickPost.normalMap = BRICK.normal;
function applyHouseLook() {
  const h = S.house, w = HM.wall, r = HM.roof;
  w.color.set(h.facade.c); w.roughness = h.facade.m === 'wood' ? .75 : .92; w.map = null; w.normalMap = null;
  if (h.facade.m === 'plaster') { withTex('plaster_d', t => { if (S.house.facade.m === 'plaster') { w.map = tiled(t, 2.2); w.needsUpdate = true; redraw(false); } }); withTex('plaster_n', t => { if (S.house.facade.m === 'plaster') { w.normalMap = tiled(t, 2.2); w.normalScale.set(.8, .8); w.needsUpdate = true; } }); }
  else if (h.facade.m === 'brick') { w.map = BRICK.map; w.normalMap = BRICK.normal; w.normalScale.set(1, 1); }
  else { withTex('wood_d', t => { if (S.house.facade.m !== 'plaster' && S.house.facade.m !== 'brick') { w.map = tiled(t, h.facade.m === 'siding' ? 1.2 : 1.8); w.needsUpdate = true; redraw(false); } }); withTex('wood_n', t => { if (S.house.facade.m !== 'plaster' && S.house.facade.m !== 'brick') { w.normalMap = tiled(t, 1.2); w.normalScale.set(1.2, 1.2); w.needsUpdate = true; } }); }
  w.needsUpdate = true;
  r.color.set(h.roof.c); r.map = null; r.normalMap = null;
  if (h.roof.m === 'metal') { r.map = METALTILE.map; r.normalMap = METALTILE.normal; r.metalness = .5; r.roughness = .38; }
  else if (h.roof.m === 'soft') { r.map = SHINGLE.map; r.normalMap = SHINGLE.normal; r.metalness = 0; r.roughness = .9; }
  else { r.metalness = .45; r.roughness = .42; withTex('roof_d', t => { if (S.house.roof.m === 'prof') { r.map = tiled(t, 1.6); r.needsUpdate = true; redraw(false); } }); withTex('roof_n', t => { if (S.house.roof.m === 'prof') { r.normalMap = tiled(t, 1.6); r.needsUpdate = true; } }); }
  r.needsUpdate = true;
  HM.plinth.color.set(h.plinth); HM.trim.color.set(h.win); HM.door.color.set(h.door);
  redraw(false);
}

/* ---------- геометрия ---------- */
function mesh(geo, m, cast = true) { const x = new THREE.Mesh(geo, m); x.castShadow = cast; x.receiveShadow = true; return x; }
function boxGeo(w, h, d) { const g = new THREE.BoxGeometry(w, h, d), uv = g.attributes.uv, dm = [[d, h], [d, h], [w, d], [w, d], [w, h], [w, h]];
  for (let f = 0; f < 6; f++) for (let i = 0; i < 4; i++) { const k = f * 4 + i; uv.setXY(k, uv.getX(k) * dm[f][0], uv.getY(k) * dm[f][1]); } return g; }
function box(w, h, d, m, x = 0, y = 0, z = 0) { const o = mesh(boxGeo(w, h, d), m); o.position.set(x, y, z); return o; }
function cyl(rt, rb, h, m, seg = 16) { return mesh(new THREE.CylinderGeometry(rt, rb, h, seg), m); }
function mergeStatic(group) {
  group.updateMatrixWorld(true);
  const inv = new THREE.Matrix4().copy(group.matrixWorld).invert(), buckets = new Map(), dead = [];
  group.traverse(o => { if (!o.isMesh || o.isInstancedMesh || o.userData.noMerge) return;
    const g = o.geometry.index ? o.geometry.toNonIndexed() : o.geometry.clone(); g.clearGroups();
    for (const k of Object.keys(g.attributes)) if (!['position', 'normal', 'uv'].includes(k)) g.deleteAttribute(k);
    if (!g.attributes.uv) g.setAttribute('uv', new THREE.Float32BufferAttribute(new Float32Array(g.attributes.position.count * 2), 2));
    g.applyMatrix4(new THREE.Matrix4().multiplyMatrices(inv, o.matrixWorld));
    const key = o.material.uuid + (o.castShadow ? 's' : '');
    if (!buckets.has(key)) buckets.set(key, { m: o.material, cast: o.castShadow, g: [] }); buckets.get(key).g.push(g); dead.push(o); });
  dead.forEach(o => { o.parent.remove(o); o.geometry.dispose(); });
  for (const b of buckets.values()) { group.add(mesh(mergeGeometries(b.g), b.m, b.cast)); b.g.forEach(g => g.dispose()); }
  return group;
}
function disposeTree(o) { o.traverse(x => { if (x.geometry && !keepSet.has(x.geometry)) x.geometry.dispose(); if (x.material && !keepSet.has(x.material) && !Object.values(ZMAT).some(v => v.mat === x.material)) { if (x.material.map && !keepSet.has(x.material.map)) x.material.map.dispose(); x.material.dispose(); } }); }

const CTEX = new Map();
function contact(g, w, d, x, z, op = .5, pad = .7) {
  const ck = (w / (w + 2 * pad)).toFixed(2) + '|' + (d / (d + 2 * pad)).toFixed(2) + '|' + op; let t = CTEX.get(ck);
  if (!t) { const S2 = 96, a = new Uint8Array(S2 * S2 * 4), fx = w / (w + 2 * pad), fz = d / (d + 2 * pad);
    for (let y = 0; y < S2; y++) for (let xx = 0; xx < S2; xx++) { const u = Math.abs((xx + .5) / S2 - .5) * 2, v = Math.abs((y + .5) / S2 - .5) * 2, dx = Math.max(0, u - fx) / (1 - fx), dz = Math.max(0, v - fz) / (1 - fz), dd = Math.min(1, Math.hypot(dx, dz)); a[(y * S2 + xx) * 4 + 3] = Math.pow(1 - dd * dd * (3 - 2 * dd), 1.6) * op * 255; }
    t = keep(new THREE.DataTexture(a, S2, S2, THREE.RGBAFormat)); t.needsUpdate = true; CTEX.set(ck, t); }
  const m = new THREE.Mesh(new THREE.PlaneGeometry(w + 2 * pad, d + 2 * pad), new THREE.MeshBasicMaterial({ color: 0, map: t, transparent: true, depthWrite: false, polygonOffset: true, polygonOffsetFactor: -6, polygonOffsetUnits: -6 }));
  m.rotation.x = -Math.PI / 2; m.position.set(x, .036, z); m.renderOrder = 3; g.add(m); return m;
}

/* дом */
function win(g, x, y, z, ry, W = 1.3, H = 1.45) {
  const w = new THREE.Group(), fr = HM.trim, t = .08, f = .055;
  w.add(box(W + 2 * t, t, .06, fr, 0, H / 2 + t / 2, .05), box(W + 2 * t, t, .06, fr, 0, -H / 2 - t / 2, .05), box(t, H, .06, fr, -W / 2 - t / 2, 0, .05), box(t, H, .06, fr, W / 2 + t / 2, 0, .05));
  w.add(box(W, f, .05, fr, 0, H / 2 - f / 2, .025), box(W, f, .05, fr, 0, -H / 2 + f / 2, .025), box(f, H, .05, fr, -W / 2 + f / 2, 0, .025), box(f, H, .05, fr, W / 2 - f / 2, 0, .025), box(f * .9, H, .05, fr, 0, 0, .025));
  w.add(box(W - .04, H - .04, .01, HM.glass, 0, 0, .006), box(W + .26, .035, .22, HM.metal, 0, -H / 2 - t - .012, .1));
  w.position.set(x, y, z); w.rotation.y = ry; g.add(w);
}
function houseSpec() { return HOUSES[S.house.type] || HOUSES.one; }
function houseFoot() { const o = houseSpec(), h = S.house; return [h.x - o.w / 2 - .1, h.z - o.d / 2 - .1, h.x + o.w / 2 + .1 + (o.garage ? 4.8 : 0) + (o.w > 12 ? 0 : 0), h.z + o.d / 2 + 1.05]; }
function buildHouse() {
  const o = houseSpec(), { w, d, h } = o, P = .5, fh = h / o.floors, g = new THREE.Group();
  g.add(box(w + .16, P, d + .16, HM.plinth, 0, P / 2, 0), box(w, h, d, HM.wall, 0, P + h / 2, 0));
  const rh = d / 2 * o.pitch, sh = new THREE.Shape([new THREE.Vector2(-d / 2, 0), new THREE.Vector2(d / 2, 0), new THREE.Vector2(0, rh)]);
  const gg = new THREE.ExtrudeGeometry(sh, { depth: w, bevelEnabled: false }); gg.rotateY(Math.PI / 2); gg.translate(-w / 2, P + h, 0);
  const uv = gg.attributes.uv; for (let i = 0; i < uv.count; i++) uv.setXY(i, uv.getX(i), uv.getY(i)); g.add(mesh(gg, HM.wall));
  const oh = .55, a = Math.atan2(rh, d / 2), Dd = d / 2 + oh, Ls = Dd / Math.cos(a) + .08, t = .12, top = P + h + rh, L = w + .9;
  for (const s of [1, -1]) {
    const m = box(L, t, Ls, HM.roof); m.rotation.x = s * a; m.position.set(0, top - (Dd / 2) * Math.tan(a) + t / 2 / Math.cos(a), s * Dd / 2); g.add(m);
    for (const sx of [1, -1]) { const b = box(.05, .22, Ls, HM.trim); b.rotation.x = s * a; b.position.set(sx * L / 2, top - (Dd / 2) * Math.tan(a) - .04, s * Dd / 2); g.add(b); }
    const gut = cyl(.075, .075, L, HM.metal, 12); gut.rotation.z = Math.PI / 2; gut.position.set(0, top - Dd * Math.tan(a) - .06, s * (Dd + .02)); g.add(gut);
  }
  const ridge = box(L, .16, .16, HM.roof, 0, top + .06, 0); ridge.rotation.x = Math.PI / 4; g.add(ridge);
  for (const sx of [1, -1]) { const p = cyl(.05, .05, top - Dd * Math.tan(a) - .1, HM.metal, 10); p.position.set(sx * (w / 2 - .15), (top - Dd * Math.tan(a)) / 2, d / 2 + .12); g.add(p); }
  g.add(box(.62, 1.7, .62, HM.chimney, w * .27, top - rh * .35 + .45, -d * .14), box(.74, .08, .74, HM.plinth, w * .27, top - rh * .35 + 1.32, -d * .14));
  const nW = Math.max(2, Math.floor(w / 2.4));
  for (let f = 0; f < o.floors; f++) { const yc = P + f * fh + fh * .56;
    for (let i = 0; i < nW; i++) { const x = -w / 2 + (i + .5) * w / nW; if (f === 0 && Math.abs(x - o.door) < 1.2) continue; win(g, x, yc, d / 2, 0); }
    const nS = Math.max(1, Math.floor(d / 3.2)); for (let i = 0; i < nS; i++) { const z = -d / 2 + (i + .5) * d / nS; win(g, w / 2, yc, z, Math.PI / 2); win(g, -w / 2, yc, z, -Math.PI / 2); } }
  g.add(box(1.25, 2.3, .07, HM.trim, o.door, P + 1.15, d / 2 + .03), box(1.02, 2.14, .08, HM.door, o.door, P + 1.07, d / 2 + .045), box(.5, .6, .02, HM.glass, o.door, P + 1.65, d / 2 + .09), box(.04, .22, .05, HM.metal, o.door + .36, P + 1.02, d / 2 + .11));
  g.add(box(2.1, .1, 1.15, HM.roof, o.door, P + 2.6, d / 2 + .57));
  [[.5, .32], [.33, .64], [.17, .96]].forEach(([hh, dd]) => g.add(box(2, hh, dd, HM.plinth, o.door, hh / 2, d / 2 + dd / 2)));
  if (o.garage) { const gx = w / 2 + 2.4, gw = 4.6, gd = 6.5, gH = 2.9, gz = d / 2 - gd / 2;
    g.add(box(gw + .2, .4, gd + .2, HM.plinth, gx, .2, gz), box(gw, gH - .4, gd, HM.wall, gx, .4 + (gH - .4) / 2, gz), box(gw + .4, .25, gd + .4, HM.roof, gx, gH + .12, gz));
    g.add(box(gw - .9, 2.3, .06, HM.trim, gx, 1.17, gz + gd / 2 + .03)); for (let i = 1; i < 6; i++) g.add(box(gw - .9, .03, .02, HM.metal, gx, i * .38, gz + gd / 2 + .065)); }
  if (o.w > 12) { // терраса у широкого дома
    g.add(box(5, .12, 2.6, HM.wood, -w / 4, .55, d / 2 + 1.3)); for (const sx of [-1, 1]) for (const sz of [0]) g.add(box(.12, 2.4, .12, HM.wood, -w / 4 + sx * 2.4, 1.75, d / 2 + 2.5));
    g.add(box(5.2, .1, 2.9, HM.roof, -w / 4, 2.95, d / 2 + 1.3)); }
  g.position.set(S.house.x, 0, S.house.z);
  return mergeStatic(g);
}

/* машина, мебель, беседка */
function car(x, z) {
  const g = new THREE.Group(), paint = keep(new THREE.MeshPhysicalMaterial({ color: '#24405f', metalness: .6, roughness: .3, clearcoat: 1, clearcoatRoughness: .05 }));
  const rubber = M('#141414', { roughness: .92 }), alloy = M('#b9bcbf', { metalness: 1, roughness: .25 });
  const b = new THREE.Shape(); const AR = .41, WY = .34;
  b.moveTo(-2.25, .3); b.lineTo(-1.38 - AR, .3); b.absarc(-1.38, WY, AR, Math.PI, 0, true); b.lineTo(1.38 - AR, .3); b.absarc(1.38, WY, AR, Math.PI, 0, true);
  b.lineTo(2.22, .3); b.quadraticCurveTo(2.34, .34, 2.32, .6); b.quadraticCurveTo(2.3, .8, 2.05, .84); b.lineTo(1.0, .94); b.lineTo(-1.55, .98); b.lineTo(-2.12, .96); b.quadraticCurveTo(-2.32, .92, -2.3, .62); b.lineTo(-2.25, .3);
  const bg = new THREE.ExtrudeGeometry(b, { depth: 1.68, bevelEnabled: true, bevelThickness: .05, bevelSize: .04, bevelSegments: 4, curveSegments: 24 }); bg.translate(0, 0, -.84); g.add(mesh(bg, paint));
  const c = new THREE.Shape(); c.moveTo(1.0, .92); c.quadraticCurveTo(.45, 1.3, .15, 1.4); c.lineTo(-.95, 1.43); c.quadraticCurveTo(-1.35, 1.36, -1.62, .97); c.lineTo(1.0, .92);
  const cg = new THREE.ExtrudeGeometry(c, { depth: 1.36, bevelEnabled: true, bevelThickness: .06, bevelSize: .05, bevelSegments: 3, curveSegments: 16 }); cg.translate(0, 0, -.68); g.add(mesh(cg, HM.glass));
  g.add(box(1.12, .045, 1.38, paint, -.4, 1.47, 0));
  const tg = new THREE.CylinderGeometry(.34, .34, .24, 24); tg.rotateZ(Math.PI / 2); const rg = new THREE.CylinderGeometry(.21, .21, .25, 20); rg.rotateZ(Math.PI / 2);
  for (const [wx, wz] of [[-1.38, .82], [1.38, .82], [-1.38, -.82], [1.38, -.82]]) { const t = mesh(tg, rubber), r = mesh(rg, alloy); t.position.set(wx, WY, wz); r.position.set(wx, WY, wz); g.add(t, r); }
  g.rotation.y = Math.PI / 2; g.position.set(x, 0, z); return mergeStatic(g);
}
function table(x, z) { const g = new THREE.Group(), wd = HM.wood; g.add(box(1.4, .05, .85, wd, 0, .75, 0));
  for (const [a, b] of [[-.6, -.35], [.6, -.35], [-.6, .35], [.6, .35]]) g.add(box(.06, .72, .06, wd, a, .36, b));
  for (const s of [-1, 1]) g.add(box(1.3, .05, .4, wd, 0, .45, s * .75), box(1.3, .36, .04, wd, 0, .72, s * .95), box(.05, .45, .05, wd, -.6, .22, s * .75), box(.05, .45, .05, wd, .6, .22, s * .75));
  g.position.set(x, .03, z); return mergeStatic(g); }
function gazebo(x, z, s = 3.2) { const g = new THREE.Group(), wd = HM.wood, p = s / 2 - .1;
  for (const [a, b] of [[-p, -p], [p, -p], [-p, p], [p, p]]) g.add(box(.14, 2.35, .14, wd, a, 1.2, b));
  for (const sg of [-1, 1]) g.add(box(s, .14, .14, wd, 0, 2.35, sg * p), box(.14, .14, s, wd, sg * p, 2.35, 0));
  const rf = mesh(new THREE.ConeGeometry((s + .8) / Math.SQRT2, 1.25, 4, 1), HM.roof); rf.rotation.y = Math.PI / 4; rf.position.y = 3.04; g.add(rf);
  g.position.set(x, 0, z); return mergeStatic(g); }

/* деревья и кусты */
function leafTex(kind) {
  const S2 = 256, cv = document.createElement('canvas'); cv.width = cv.height = S2; const x = cv.getContext('2d'), r = rng(kind.length * 977);
  if (kind === 'spruce') {
    const twig = (x0, y0, ang, len, w) => { const ex = x0 + Math.cos(ang) * len, ey = y0 + Math.sin(ang) * len; x.strokeStyle = '#4a3a2a'; x.lineWidth = 2; x.beginPath(); x.moveTo(x0, y0); x.lineTo(ex, ey); x.stroke();
      const n = Math.round(len * 2.2); for (let i = 0; i < n; i++) { const t = i / n, px = x0 + (ex - x0) * t, py = y0 + (ey - y0) * t, L = w * (1 - t * .55) * (.7 + r() * .5);
        for (const sg of [-1, 1]) { const a = ang + sg * (.9 + r() * .35), gg = 48 + r() * 42 | 0; x.strokeStyle = `rgb(${18 + r() * 18 | 0},${gg + 18},${26 + r() * 16 | 0})`; x.lineWidth = 2.2; x.beginPath(); x.moveTo(px, py); x.lineTo(px + Math.cos(a) * L, py + Math.sin(a) * L); x.stroke(); } } };
    twig(4, S2 / 2, 0, S2 - 10, 26); for (let k = 0; k < 9; k++) { const t = .12 + k * .09, sx = 4 + t * (S2 - 10); for (const sg of [-1, 1]) twig(sx, S2 / 2, sg * (.55 + r() * .25), (1 - t) * S2 * .42 + 14, 14); }
  } else {
    const big = kind === 'maple', n = kind === 'bush' ? 260 : big ? 150 : 220;
    for (let i = 0; i < n; i++) { const rr = Math.sqrt(r()) * S2 * .42, aa = r() * Math.PI * 2, px = S2 / 2 + Math.cos(aa) * rr, py = S2 / 2 + Math.sin(aa) * rr, a = r() * Math.PI * 2, L = (kind === 'bush' ? 16 : big ? 30 : 22) * (.8 + r() * .4), W = L * (big ? .62 : .5);
      const hue = kind === 'birch' ? [92, 138, 46] : kind === 'bush' ? [52, 96, 34] : [66, 108, 36], k = .86 + r() * .2;
      x.save(); x.translate(px, py); x.rotate(a); x.fillStyle = `rgb(${hue[0] * k | 0},${hue[1] * k | 0},${hue[2] * k | 0})`; x.beginPath(); x.moveTo(-L / 2, 0); x.quadraticCurveTo(0, -W, L / 2, 0); x.quadraticCurveTo(0, W, -L / 2, 0); x.fill(); x.restore(); }
  }
  const t = new THREE.CanvasTexture(cv); t.colorSpace = THREE.SRGBColorSpace; t.anisotropy = ANISO; return t;
}
const LEAF = {}, BARK = {};
function leafMat(kind) {
  if (LEAF[kind]) return LEAF[kind];
  const spruce = kind === 'spruce', m = keep(new THREE.MeshLambertMaterial({ map: leafTex(kind), alphaTest: .45, side: THREE.DoubleSide, emissive: new THREE.Color(spruce ? '#1c3824' : '#35571f'), emissiveIntensity: .7 }));
  const crown = new THREE.Vector3(0, kind === 'birch' ? 3.9 : kind === 'bush' ? .42 : 3.5, 0);
  m.onBeforeCompile = sh => { sh.uniforms.uCrown = { value: crown };
    sh.vertexShader = sh.vertexShader.replace('#include <common>', '#include <common>\nuniform vec3 uCrown;').replace('#include <beginnormal_vertex>', `vec3 pvI=(instanceMatrix*vec4(position,1.)).xyz; vec3 dn=${spruce ? 'normalize(vec3(pvI.x,.5*length(pvI.xz)+.25,pvI.z))' : 'normalize(pvI-uCrown+vec3(0.,.35,0.))'}; vec3 objectNormal=transpose(mat3(instanceMatrix))*dn;`);
    sh.fragmentShader = sh.fragmentShader.replace('#include <normal_fragment_begin>', THREE.ShaderChunk.normal_fragment_begin.replace('gl_FrontFacing ? 1.0 : - 1.0', '1.0')); };
  m.customProgramCacheKey = () => 'leaf' + kind; return LEAF[kind] = m;
}
function barkMat(kind) { if (BARK[kind]) return BARK[kind]; const cv = document.createElement('canvas'); cv.width = 64; cv.height = 256; const x = cv.getContext('2d'), r = rng(kind.length * 31);
  if (kind === 'birch') { x.fillStyle = '#e9e6df'; x.fillRect(0, 0, 64, 256); for (let i = 0; i < 40; i++) { x.fillStyle = `rgba(30,28,26,${.5 + r() * .5})`; x.fillRect(r() * 64, r() * 256, 6 + r() * 20, 1.5 + r() * 3); } }
  else { x.fillStyle = '#4f3d2e'; x.fillRect(0, 0, 64, 256); for (let i = 0; i < 120; i++) { x.fillStyle = `rgba(${r() < .5 ? 20 : 110},${r() < .5 ? 15 : 90},10,.35)`; x.fillRect(r() * 64, r() * 256, 1 + r() * 2, 10 + r() * 40); } }
  const t = new THREE.CanvasTexture(cv); t.colorSpace = THREE.SRGBColorSpace; return BARK[kind] = keep(new THREE.MeshStandardMaterial({ map: t, roughness: .95 })); }
function bentCyl(r0, r1, h, bend, seg = 10) { const g = new THREE.CylinderGeometry(r1, r0, h, seg, 6, true); g.translate(0, h / 2, 0); const p = g.attributes.position; for (let i = 0; i < p.count; i++) { const y = p.getY(i) / h; p.setX(i, p.getX(i) + bend[0] * y * y); p.setZ(i, p.getZ(i) + bend[1] * y * y); } g.computeVertexNormals(); return g; }
function tree(x, z, s = 1, kind = 'maple') {
  const g = new THREE.Group(), r = rng(Math.abs(Math.round(x * 131 + z * 977)) + kind.length), bark = barkMat(kind), leaves = leafMat(kind), m4 = new THREE.Matrix4(), q = new THREE.Quaternion(), e = new THREE.Euler(), sc = new THREE.Vector3(), pos = new THREE.Vector3(), col = new THREE.Color();
  if (kind === 'spruce') {
    g.add(mesh(bentCyl(.17, .03, 6.4, [0, 0]), bark));
    const cards = [], geo = new THREE.PlaneGeometry(1, 1); geo.translate(.5, 0, 0); geo.rotateX(-Math.PI / 2); const cross = new THREE.Quaternion().setFromAxisAngle(new THREE.Vector3(1, 0, 0), Math.PI / 2);
    for (let y = .6; y < 6.3; y += .34) { const R = 2.05 * Math.pow(1 - (y - .6) / 5.75, .9) + .2, n = Math.max(5, Math.round(R * 5)), off = r() * Math.PI * 2;
      for (let k = 0; k < n; k++) { e.set(0, off + k / n * Math.PI * 2 + (r() - .5) * .3, 0); q.setFromEuler(e); q.multiply(new THREE.Quaternion().setFromAxisAngle(new THREE.Vector3(0, 0, 1), -(.18 + r() * .28)));
        sc.set(R * (.85 + r() * .3), 1, R * .95); pos.set(0, y + (r() - .5) * .1, 0); m4.compose(pos, q, sc); cards.push(m4.clone()); const q2 = q.clone().multiply(cross); sc.set(R * (.8 + r() * .3), 1, R * .6); m4.compose(pos, q2, sc); cards.push(m4.clone()); } }
    const im = new THREE.InstancedMesh(geo, leaves, cards.length); cards.forEach((m, i) => { im.setMatrixAt(i, m); im.setColorAt(i, col.setHSL(.33, .35, .32 + r() * .12)); }); im.castShadow = im.receiveShadow = true; g.add(im);
  } else {
    const birch = kind === 'birch', H = birch ? 2.6 : 2.1, crownY = birch ? 3.9 : 3.5, tips = [];
    g.add(mesh(bentCyl(birch ? .14 : .2, birch ? .08 : .12, H + .6, [(r() - .5) * .3, (r() - .5) * .3]), bark));
    for (let i = 0; i < (birch ? 6 : 8); i++) { const az = i / (birch ? 6 : 8) * Math.PI * 2 + r() * .5, el = (birch ? .35 : .6) + r() * .35, len = (birch ? 1.5 : 1.7) * (.8 + r() * .4), y0 = H - .3 + r() * .7;
      const b = mesh(bentCyl(.07, .025, len, [0, 0], 6), bark); b.position.set(0, y0, 0); b.rotation.set(0, az, 0); b.rotateZ(-el); g.add(b);
      tips.push(new THREE.Vector3(0, len, 0).applyEuler(new THREE.Euler(0, 0, -el)).applyAxisAngle(new THREE.Vector3(0, 1, 0), az).add(new THREE.Vector3(0, y0, 0))); }
    const count = Math.round((MOBILE ? .5 : 1) * (birch ? 230 : 210)), geo = new THREE.PlaneGeometry(1, 1), arr = [], RX = birch ? 1.45 : 1.9, RY = birch ? 1.9 : 1.55;
    for (let i = 0; i < count; i++) { let p; if (r() < .45) p = tips[i % tips.length].clone().add(new THREE.Vector3((r() - .5) * 1.1, (r() - .3), (r() - .5) * 1.1));
      else { const th = r() * Math.PI * 2, ph = Math.acos(2 * r() - 1), rr = .55 + .45 * Math.cbrt(r()); p = new THREE.Vector3(Math.sin(ph) * Math.cos(th) * RX * rr, Math.cos(ph) * RY * rr + crownY, Math.sin(ph) * Math.sin(th) * RX * rr); }
      e.set(r() * Math.PI, r() * Math.PI, r() * Math.PI); q.setFromEuler(e); const k = 1.25 * (birch ? .75 : .95) * (.75 + r() * .5); sc.set(k, k, k); m4.compose(p, q, sc); arr.push(m4.clone()); }
    const im = new THREE.InstancedMesh(geo, leaves, arr.length); arr.forEach((m, i) => { im.setMatrixAt(i, m); im.setColorAt(i, col.setHSL(birch ? .24 : .22, .45, .36 + r() * .16)); }); im.castShadow = im.receiveShadow = true; g.add(im);
  }
  g.position.set(x, 0, z); g.rotation.y = r() * Math.PI * 2; g.scale.setScalar(s); return g;
}
function bush(x, z, s = 1) {
  const g = new THREE.Group(), r = rng(Math.abs(Math.round(x * 311 + z * 733))), m4 = new THREE.Matrix4(), q = new THREE.Quaternion(), e = new THREE.Euler(), sc = new THREE.Vector3(), col = new THREE.Color(), arr = [];
  for (let i = 0; i < (MOBILE ? 45 : 75); i++) { const th = r() * Math.PI * 2, ph = Math.acos(2 * r() - 1), rr = .5 + .5 * Math.cbrt(r()); const p = new THREE.Vector3(Math.sin(ph) * Math.cos(th) * .62 * rr, Math.max(.12, Math.cos(ph) * .48 * rr + .46), Math.sin(ph) * Math.sin(th) * .62 * rr);
    e.set(r() * Math.PI, r() * Math.PI, r() * Math.PI); q.setFromEuler(e); const k = .55 + r() * .25; sc.set(k, k, k); m4.compose(p, q, sc); arr.push(m4.clone()); }
  const im = new THREE.InstancedMesh(new THREE.PlaneGeometry(1, 1), leafMat('bush'), arr.length); arr.forEach((m, i) => { im.setMatrixAt(i, m); im.setColorAt(i, col.setHSL(.27, .4, .3 + r() * .12)); }); im.castShadow = im.receiveShadow = true; g.add(im);
  g.position.set(x, 0, z); g.scale.setScalar(s); return g;
}

/* газон */
{ const ao = MACRO.clone(); ao.repeat.set(1 / 16, 1 / 16); ao.needsUpdate = true;
  const gm = keep(new THREE.MeshLambertMaterial({ color: '#7fa04e', aoMap: ao }));
  withTex('grass_d', t => { gm.map = tiled(t, 2.6); gm.color.set('#cdd3b8'); gm.needsUpdate = true; });
  gm.onBeforeCompile = sh => { sh.fragmentShader = sh.fragmentShader.replace('#include <map_fragment>', `#include <map_fragment>
#ifdef USE_MAP
  vec2 uvB=mat2(.8,-.6,.6,.8)*vMapUv*.37+.13; diffuseColor.rgb=mix(diffuseColor.rgb,diffuse*texture2D(map,uvB).rgb,.5);
  float mac=texture2D(map,vMapUv*.027+.5).g; diffuseColor.rgb*=.6+.75*mac;
#endif`); };
  gm.customProgramCacheKey = () => 'lawn';
  const geo = new THREE.PlaneGeometry(400, 400), uv = geo.attributes.uv; for (let i = 0; i < uv.count; i++) uv.setXY(i, uv.getX(i) * 400, uv.getY(i) * 400);
  const m = new THREE.Mesh(geo, gm); m.rotation.x = -Math.PI / 2; m.receiveShadow = true; scene.add(m); }

/* ---------- плитка: материалы по зонам (форма|цвет|рисунок), генерация в фоне ---------- */
const PX = MOBILE ? 900 : 1280;
const ZMAT = {};      // key -> {mat, ready}
let worker = null; const wcb = new Map(); let wid = 0;
try { worker = new Worker(D.worker + '&p=' + encodeURIComponent(new URL(D.paving, location.href).href), { type: 'module' }); worker.onmessage = e => { const f = wcb.get(e.data.id); wcb.delete(e.data.id); f && f(e.data); }; }
catch (e) { worker = null; }
async function genTex(job) {
  if (worker) return new Promise(res => { const id = ++wid; wcb.set(id, res); worker.postMessage({ ...job, id }); });
  const m = await import(D.paving); return m.build(job);
}
const paveAO = MACRO.clone(); paveAO.repeat.set(.32, .32); paveAO.needsUpdate = true;
function avgColor(pal) { let r = 0, g = 0, b = 0, t = 0; for (const [h, w] of pal) { const n = parseInt(h.slice(1), 16); r += (n >> 16) * w; g += (n >> 8 & 255) * w; b += (n & 255) * w; t += w; } return new THREE.Color(`rgb(${r / t | 0},${g / t | 0},${b / t | 0})`); }
function zoneMat(z) {
  if (z.kind === 'grid') return gridMat();
  const key = z.form + '|' + z.color + '|' + z.pat;
  if (ZMAT[key]) return ZMAT[key].mat;
  const pal = (D.colors[z.color] || {}).pal || [['#999999', 1]];
  const mat = keep(new THREE.MeshStandardMaterial({ color: avgColor(pal), roughness: 1, aoMap: paveAO, aoMapIntensity: .9 }));
  ZMAT[key] = { mat, ready: false };
  genTex({ slug: z.form, pat: z.pat, px: PX, pal, seed: 11 }).then(d => {
    const rot = PATTERN_ROT[z.pat] || 0, P = period(z.form);
    const set = (a, srgb) => { const t = dataTex(a, d.W, d.H, srgb); t.repeat.set(1 / P, 1 / P); t.rotation = rot; return t; };
    mat.map = set(d.c, true); mat.normalMap = set(d.n, false); mat.roughnessMap = set(d.r, false); mat.color.set('#ffffff'); mat.needsUpdate = true; ZMAT[key].ready = true; redraw(false);
  });
  return mat;
}
function kaymaMat(color) {
  const key = 'kayma|' + color; if (ZMAT[key]) return ZMAT[key].mat;
  const pal = (D.colors[color] || {}).pal || [['#999999', 1]];
  const mat = keep(new THREE.MeshStandardMaterial({ color: avgColor(pal), roughness: 1, polygonOffset: true, polygonOffsetFactor: -2, polygonOffsetUnits: -2 }));
  ZMAT[key] = { mat, ready: false };
  genTex({ pat: 'soldier', s: MOBILE ? 24 : 32, pal, seed: 23 }).then(d => { for (const [k, a, srgb] of [['map', d.c, true], ['normalMap', d.n, false], ['roughnessMap', d.r, false]]) mat[k] = dataTex(a, d.W, d.H, srgb); mat.color.set('#fff'); mat.needsUpdate = true; ZMAT[key].ready = true; redraw(false); });
  return mat;
}
let GRIDM;
function gridMat() {   // газонная решётка: бетонные соты с травой в ячейках, шаг 0,6×0,4 м
  if (GRIDM) return GRIDM;
  const W = 240, H = 160, col = new Uint8Array(W * H * 4), nrm = new Uint8Array(W * H * 4);
  const cells = [[60, 40], [180, 40], [0, 120], [120, 120], [240, 120], [60, -40], [180, -40], [0, -40]];
  for (let y = 0; y < H; y++) for (let x = 0; x < W; x++) {
    let dmin = 9; for (const [cx, cy] of cells) { const d = (Math.abs(x - cx) + Math.abs(y - cy)) / 38; dmin = Math.min(dmin, d); for (const ox of [-W, W]) dmin = Math.min(dmin, (Math.abs(x - cx - ox) + Math.abs(y - cy)) / 38); }
    const hole = dmin < 1, edge = Math.max(0, Math.min(1, (dmin - 1) * 6)), i = (y * W + x) * 4, n = nz(x, y);
    if (hole) { col[i] = 70 + n * 40; col[i + 1] = 110 + n * 60; col[i + 2] = 40 + n * 20; } else { const v = 168 + n * 30 - (1 - edge) * 40; col[i] = v; col[i + 1] = v - 2; col[i + 2] = v - 8; }
    col[i + 3] = 255; nrm[i] = 128; nrm[i + 1] = 128; nrm[i + 2] = 255; nrm[i + 3] = 255;
  }
  const t = dataTex(col, W, H, true); t.repeat.set(1 / .6, 1 / .4);
  return GRIDM = keep(new THREE.MeshStandardMaterial({ map: t, roughness: .95 }));
}

/* ---------- зоны: растр, контуры, бордюр, кайма ---------- */
const G = .1;
let RS = null;   // {X0,Z0,NX,NZ,g}
function rasterZones() {
  const W = S.plot.w / 2 + 2, Dd = S.plot.d / 2 + 2, X0 = -W, Z0 = -Dd, NX = Math.round(2 * W / G), NZ = Math.round(2 * Dd / G), g = new Int16Array(NX * NZ);
  S.zones.forEach((z, idx) => { const i1 = Math.round((z.x1 - X0) / G), i2 = Math.round((z.x2 - X0) / G), k1 = Math.round((z.z1 - Z0) / G), k2 = Math.round((z.z2 - Z0) / G);
    for (let k = Math.max(0, k1); k < Math.min(NZ, k2); k++) for (let i = Math.max(0, i1); i < Math.min(NX, i2); i++) g[k * NX + i] = idx + 1; });
  const hm = new Uint8Array(NX * NZ);   // под домом плитки нет: площадь не считаем, бордюр вдоль стены не ставим
  for (const [x1, z1, x2, z2] of houseBoxes()) { const i1 = Math.round((x1 - X0) / G), i2 = Math.round((x2 - X0) / G), k1 = Math.round((z1 - Z0) / G), k2 = Math.round((z2 - Z0) / G);
    for (let k = Math.max(0, k1); k < Math.min(NZ, k2); k++) for (let i = Math.max(0, i1); i < Math.min(NX, i2); i++) { g[k * NX + i] = 0; hm[k * NX + i] = 1; } }
  return RS = { X0, Z0, NX, NZ, g, hm };
}
function houseBoxes() { const o = houseSpec(), h = S.house, r = [[h.x - o.w / 2, h.z - o.d / 2, h.x + o.w / 2, h.z + o.d / 2]];
  if (o.garage) { const gx = h.x + o.w / 2 + 2.4, gz = h.z + o.d / 2 - 3.25; r.push([gx - 2.4, gz - 3.35, gx + 2.4, gz + 3.35]); } return r; }
const at = (i, k) => i >= 0 && k >= 0 && i < RS.NX && k < RS.NZ ? RS.g[k * RS.NX + i] : 0;
const cellAt = (x, z) => at(Math.floor((x - RS.X0) / G), Math.floor((z - RS.Z0) / G));
function outline() {   // внешние края мощения; id = зона внутри
  const s = [], { X0, Z0, NX, NZ } = RS;
  const hm = (i, k) => i >= 0 && k >= 0 && i < NX && k < NZ ? RS.hm[k * NX + i] : 0;
  for (let k = 0; k <= NZ; k++) { let run = null; for (let i = 0; i <= NX; i++) { let t = 0, id = 0; if (i < NX) { const a = at(i, k - 1), b = at(i, k); if (a && !b) { t = 1; id = a * 2 + hm(i, k); } else if (!a && b) { t = -1; id = b * 2 + hm(i, k - 1); } }
    if (run && (run.t !== t || run.id !== id)) { s.push({ x1: X0 + run.i * G, z1: Z0 + k * G, x2: X0 + i * G, z2: Z0 + k * G, nx: 0, nz: run.t, id: run.id >> 1, house: run.id & 1 }); run = null; } if (t && !run) run = { i, t, id }; } }
  for (let i = 0; i <= NX; i++) { let run = null; for (let k = 0; k <= NZ; k++) { let t = 0, id = 0; if (k < NZ) { const a = at(i - 1, k), b = at(i, k); if (a && !b) { t = 1; id = a * 2 + hm(i, k); } else if (!a && b) { t = -1; id = b * 2 + hm(i - 1, k); } }
    if (run && (run.t !== t || run.id !== id)) { s.push({ x1: X0 + i * G, z1: Z0 + run.k * G, x2: X0 + i * G, z2: Z0 + k * G, nx: run.t, nz: 0, id: run.id >> 1, house: run.id & 1 }); run = null; } if (t && !run) run = { k, t, id }; } }
  return s;
}
function zoneGeo(z, y) {
  const P = z.kind === 'grid' ? 1 : period(z.form), p = [z.x1, y, z.z1, z.x2, y, z.z1, z.x1, y, z.z2, z.x2, y, z.z2];
  const g = new THREE.BufferGeometry();
  g.setAttribute('position', new THREE.Float32BufferAttribute(p, 3));
  const uvs = []; for (let i = 0; i < 4; i++) uvs.push(p[i * 3] / P, -p[i * 3 + 2] / P);
  g.setAttribute('uv', new THREE.Float32BufferAttribute(uvs, 2)); g.setAttribute('normal', new THREE.Float32BufferAttribute([0, 1, 0, 0, 1, 0, 0, 1, 0, 0, 1, 0], 3));
  g.setIndex([0, 2, 1, 1, 2, 3]); return g;
}
const zonesG = new THREE.Group(); scene.add(zonesG);
const zoneMeshes = new Map();
let segs = [], areas = {}, kerbLen = {};
function buildZones() {
  disposeTree(zonesG); zonesG.clear(); zoneMeshes.clear();
  rasterZones(); segs = outline();
  areas = {}; for (let i = 0; i < RS.g.length; i++) { const v = RS.g[i]; if (v) areas[v] = (areas[v] || 0) + G * G; }
  S.zones.forEach((z, idx) => { const m = mesh(zoneGeo(z, .03 + idx * .0004), zoneMat(z), false); m.userData.zone = z.id; zonesG.add(m); zoneMeshes.set(z.id, m); });
  // бордюр снаружи
  kerbLen = {}; const cw = .08, ch = .1, kg = [];
  for (const s of segs) { const z = S.zones[s.id - 1]; if (!z || !z.kerb) continue;
    if (s.house) continue;
    const L = Math.hypot(s.x2 - s.x1, s.z2 - s.z1), tx = (s.x2 - s.x1) / L, tz = (s.z2 - s.z1) / L; kerbLen[z.id] = (kerbLen[z.id] || 0) + L;
    const e1 = !cellAt(s.x1 - tx * .05 + s.nx * .05, s.z1 - tz * .05 + s.nz * .05) ? cw : 0, e2 = !cellAt(s.x2 + tx * .05 + s.nx * .05, s.z2 + tz * .05 + s.nz * .05) ? cw : 0;
    const b = boxGeo(L + e1 + e2, ch, cw); b.rotateY(Math.atan2(-tz, tx)); const mid = (e2 - e1) / 2; b.translate((s.x1 + s.x2) / 2 + tx * mid + s.nx * cw / 2, ch / 2, (s.z1 + s.z2) / 2 + tz * mid + s.nz * cw / 2); kg.push(b); }
  if (kg.length) zonesG.add(mesh(mergeGeometries(kg), HM.plinth));
  // кайма внутри
  const byColor = {};
  segs.forEach((s, idx) => { const z = S.zones[s.id - 1]; if (!z || !z.kayma || z.kind !== 'pave') return;
    const L = Math.hypot(s.x2 - s.x1, s.z2 - s.z1), Wk = .2, y = .0312 + (s.id - 1) * .0004 + idx * .00001;
    const p = [s.x1, y, s.z1, s.x2, y, s.z2, s.x1 - s.nx * Wk, y, s.z1 - s.nz * Wk, s.x2 - s.nx * Wk, y, s.z2 - s.nz * Wk];
    const g = new THREE.BufferGeometry(); g.setAttribute('position', new THREE.Float32BufferAttribute(p, 3)); g.setAttribute('uv', new THREE.Float32BufferAttribute([0, 0, L / 4, 0, 0, 1, L / 4, 1], 2)); g.setAttribute('normal', new THREE.Float32BufferAttribute([0, 1, 0, 0, 1, 0, 0, 1, 0, 0, 1, 0], 3));
    const cr = (s.x2 - s.x1) * (-s.nz * Wk) - (s.z2 - s.z1) * (-s.nx * Wk); g.setIndex(cr > 0 ? [0, 2, 1, 1, 2, 3] : [0, 1, 2, 1, 3, 2]); (byColor[z.kayma] = byColor[z.kayma] || []).push(g); });
  for (const [c, gs] of Object.entries(byColor)) { const m = new THREE.Mesh(mergeGeometries(gs), kaymaMat(c)); m.receiveShadow = true; zonesG.add(m); }
  grassTufts();
  buildFence();
  updateHandles();
  renderFoot(); renderSwap();
  redraw();
}
/* быстрое обновление одной зоны при перетаскивании (без бордюров) */
function moveZoneMesh(z) { const m = zoneMeshes.get(z.id); if (!m) return; const idx = S.zones.indexOf(z); m.geometry.dispose(); m.geometry = zoneGeo(z, .03 + idx * .0004); updateHandles(); redraw(false); }

/* пучки травы вдоль краёв */
let GRASSM;
function grassTufts() {
  if (!GRASSM) { const cv = document.createElement('canvas'); cv.width = cv.height = 128; const x = cv.getContext('2d'), r = rng(17);
    for (let i = 0; i < 60; i++) { const bx = 8 + r() * 112, h = 128 * (.45 + r() * .5), lean = (r() - .5) * 30, g = 90 + r() * 70 | 0; x.strokeStyle = `rgb(${40 + r() * 40 | 0},${g},${25 + r() * 25 | 0})`; x.lineWidth = 1.5 + r() * 1.5; x.beginPath(); x.moveTo(bx, 128); x.quadraticCurveTo(bx + lean * .3, 128 - h * .6, bx + lean, 128 - h); x.stroke(); }
    const t = new THREE.CanvasTexture(cv); t.colorSpace = THREE.SRGBColorSpace;
    GRASSM = keep(new THREE.MeshLambertMaterial({ map: t, alphaTest: .4, side: THREE.DoubleSide, emissive: new THREE.Color('#2a4220'), emissiveIntensity: .6 }));
    GRASSM.onBeforeCompile = sh => { sh.vertexShader = sh.vertexShader.replace('#include <beginnormal_vertex>', 'vec3 objectNormal=transpose(mat3(instanceMatrix))*vec3(0.,1.,0.);'); sh.fragmentShader = sh.fragmentShader.replace('#include <normal_fragment_begin>', THREE.ShaderChunk.normal_fragment_begin.replace('gl_FrontFacing ? 1.0 : - 1.0', '1.0')); };
    GRASSM.customProgramCacheKey = () => 'grass'; }
  const geo = mergeGeometries([0, 1].map(k => { const p = new THREE.PlaneGeometry(.26, .13); p.translate(0, .065, 0); p.rotateY(k * Math.PI / 2); return p; }));
  const r = rng(7), list = [], m4 = new THREE.Matrix4(), q = new THREE.Quaternion(), e = new THREE.Euler(), col = new THREE.Color(), hf = houseFoot();
  for (let i = 0; i < (MOBILE ? 500 : 1300) && segs.length; i++) { const s = segs[(r() * segs.length) | 0], t = r(), off = .16 + Math.pow(r(), 2) * 1.1, x = s.x1 + (s.x2 - s.x1) * t + s.nx * off, z = s.z1 + (s.z2 - s.z1) * t + s.nz * off;
    if (cellAt(x, z) || cellAt(x + .14, z) || cellAt(x - .14, z) || cellAt(x, z + .14) || cellAt(x, z - .14)) continue; if (x > hf[0] && x < hf[2] && z > hf[1] && z < hf[3]) continue; list.push([x, z]); }
  const im = new THREE.InstancedMesh(geo, GRASSM, list.length);
  list.forEach(([x, z], i) => { e.set(0, r() * Math.PI, 0); q.setFromEuler(e); const k = .7 + r() * .7; m4.compose(new THREE.Vector3(x, 0, z), q, new THREE.Vector3(k, k * (.8 + r() * .5), k)); im.setMatrixAt(i, m4); im.setColorAt(i, col.setHSL(.2 + r() * .05, .3, .3 + r() * .1)); });
  im.receiveShadow = true; zonesG.add(im);
}

/* ---------- забор ---------- */
const fenceG = new THREE.Group(); scene.add(fenceG);
function buildFence() {
  disposeTree(fenceG); fenceG.clear();
  const T = S.fence.t; if (T === 'none') return;
  HM.fence.color.set(S.fence.c);
  const W = S.plot.w / 2 + .15, Dd = S.plot.d / 2 + .15;
  const gate = x => { for (let dx = -.25; dx <= .25; dx += .05) if (cellAt(x + dx, S.plot.d / 2 - .05)) return true; return false; };
  const lines = [[-W, Dd, W, Dd, gate], [-W, -Dd, -W, Dd, () => false], [W, -Dd, W, Dd, () => false], [-W, -Dd, W, -Dd, () => false]];
  const H = { shtaketnik: 1.15, evro: 1.7, prof: 1.8, brick: 1.8, setka: 1.5 }[T];
  const runs = [];
  for (const [x1, z1, x2, z2, gp] of lines) { const L = Math.hypot(x2 - x1, z2 - z1), n = Math.round(L / .1), tx = (x2 - x1) / L, tz = (z2 - z1) / L; let run = null;
    for (let i = 0; i <= n; i++) { const px = x1 + tx * L * i / n, pz = z1 + tz * L * i / n; if (gp(px, pz)) { if (run) { runs.push(run); run = null; } continue; } if (!run) run = { a: [px, pz], tx, tz }; run.b = [px, pz]; }
    if (run) runs.push(run); }
  const geos = [], postGeos = [], slats = [], m4 = new THREE.Matrix4(), q = new THREE.Quaternion(), up = new THREE.Vector3(0, 1, 0);
  const addBox = (arr, w, h, d, x, y, z, ang) => { const b = boxGeo(w, h, d); b.rotateY(ang); b.translate(x, y, z); arr.push(b); };
  for (const r of runs) {
    const L = Math.hypot(r.b[0] - r.a[0], r.b[1] - r.a[1]); if (L < .3) continue; const ang = Math.atan2(-r.tz, r.tx), mx = (r.a[0] + r.b[0]) / 2, mz = (r.a[1] + r.b[1]) / 2;
    const step = T === 'brick' ? 3 : 2.4, np = Math.max(1, Math.round(L / step));
    for (let i = 0; i <= np; i++) { const x = r.a[0] + (r.b[0] - r.a[0]) * i / np, z = r.a[1] + (r.b[1] - r.a[1]) * i / np;
      if (T === 'brick') addBox(postGeos, .38, H + .15, .38, x, (H + .15) / 2, z, ang); else addBox(geos, T === 'shtaketnik' ? .1 : .06, H + .2, T === 'shtaketnik' ? .1 : .06, x, (H + .2) / 2, z, ang); }
    if (T === 'shtaketnik' || T === 'evro') {
      const sw = T === 'evro' ? .12 : .085, gap = T === 'evro' ? .05 : .075, n = Math.floor(L / (sw + gap));
      for (let i = 0; i < n; i++) { const t = (i + .5) / n, x = r.a[0] + (r.b[0] - r.a[0]) * t, z = r.a[1] + (r.b[1] - r.a[1]) * t; q.setFromAxisAngle(up, ang); m4.compose(new THREE.Vector3(x - r.tz * .03, H / 2 + .08, z + r.tx * .03), q, new THREE.Vector3(1, 1, 1)); slats.push(m4.clone()); }
      for (const y of [.35, H - .2]) addBox(geos, L, .06, .04, mx, y, mz, ang);
      if (!slats.geo) slats.geo = boxGeo(sw, H, T === 'evro' ? .015 : .022);
    } else if (T === 'prof' || T === 'brick') { const b = boxGeo(L, T === 'brick' ? H - .3 : H, .03); b.rotateY(ang); b.translate(mx, T === 'brick' ? H / 2 + .05 : H / 2 + .05, mz); slats.push(b); }
    else if (T === 'setka') { const p = new THREE.PlaneGeometry(L, H); const uv = p.attributes.uv; for (let i = 0; i < uv.count; i++) uv.setXY(i, uv.getX(i) * L, uv.getY(i) * H); p.rotateY(ang); p.translate(mx, H / 2 + .05, mz); slats.push(p); }
  }
  if (geos.length) fenceG.add(mesh(mergeGeometries(geos), T === 'shtaketnik' ? HM.fencePost : HM.metal));
  if (postGeos.length) fenceG.add(mesh(mergeGeometries(postGeos), HM.brickPost));
  if (T === 'shtaketnik' || T === 'evro') { if (slats.length) { const im = new THREE.InstancedMesh(slats.geo, HM.fence, slats.length); slats.forEach((m, i) => im.setMatrixAt(i, m)); im.castShadow = im.receiveShadow = true; fenceG.add(im); } }
  else if (slats.length) { const sheetM = T === 'setka' ? HM.mesh : HM.fence; if (T === 'setka') HM.mesh.color.set(S.fence.c); fenceG.add(mesh(mergeGeometries(slats), sheetM)); }
  HM.fence.metalness = T === 'shtaketnik' ? 0 : .4; HM.fence.roughness = T === 'shtaketnik' ? .7 : .45; HM.fence.map = null; HM.fence.normalMap = null;
  if (T === 'prof' || T === 'brick') withTex('roof_n', t => { if (S.fence.t === 'prof' || S.fence.t === 'brick') { HM.fence.normalMap = t.clone(); HM.fence.normalMap.repeat.set(1 / 1.6, 1 / 1.6); HM.fence.normalMap.rotation = Math.PI / 2; HM.fence.normalMap.needsUpdate = true; HM.fence.needsUpdate = true; redraw(false); } });
  HM.fence.needsUpdate = true;
}

/* ---------- сцена целиком ---------- */
const worldG = new THREE.Group(); scene.add(worldG);
function buildWorld() {
  disposeTree(worldG); worldG.clear();
  worldG.add(buildHouse()); const f = houseFoot(); contact(worldG, f[2] - f[0], f[3] - f[1], (f[0] + f[2]) / 2, (f[1] + f[3]) / 2, .38, .8);
  for (const [k, x, z] of S.extras || []) { if (k === 'car') { worldG.add(car(x, z)); contact(worldG, 1.9, 4.4, x, z, .6, .5); } else if (k === 'table') worldG.add(table(x, z)); else if (k === 'gazebo') worldG.add(gazebo(x, z)); }
  for (const [x, z, s, kind] of S.trees || []) { const t = tree(x, z, s, kind); t.userData.tree = true; t.visible = mode !== 'plan'; worldG.add(t); contact(worldG, 1.8 * s, 1.8 * s, x, z, .32, 1.4 * s); }
  for (const [x, z, s] of S.bushes || []) { worldG.add(bush(x, z, s)); contact(worldG, .9 * s, .9 * s, x, z, .4, .4 * s); }
  buildGrid();
  const R = Math.max(S.plot.w, S.plot.d) * .75 + 4; Object.assign(sun.shadow.camera, { left: -R, right: R, top: R, bottom: -R, near: 2, far: 120 }); sun.shadow.camera.updateProjectionMatrix();
  applyHouseLook();
}
function rebuildAll() { buildWorld(); buildZones(); }
/* сетка 1 м для плана */
let grid = null;
function buildGrid() {
  if (grid) { scene.remove(grid); grid.geometry.dispose(); }
  const w = S.plot.w, d = S.plot.d, p = [];
  for (let x = -w / 2; x <= w / 2 + 1e-6; x++) p.push(x, .045, -d / 2, x, .045, d / 2);
  for (let z = -d / 2; z <= d / 2 + 1e-6; z++) p.push(-w / 2, .045, z, w / 2, .045, z);
  const g = new THREE.BufferGeometry(); g.setAttribute('position', new THREE.Float32BufferAttribute(p, 3));
  grid = new THREE.LineSegments(g, new THREE.LineBasicMaterial({ color: '#ffffff', transparent: true, opacity: .22, depthWrite: false }));
  grid.visible = mode === 'plan'; grid.renderOrder = 4; scene.add(grid);
}
function planLook(on) {
  const r = HM.roof; r.transparent = on; r.opacity = on ? .22 : 1; r.depthWrite = !on; r.needsUpdate = true;
  worldG.children.forEach(o => { if (o.userData.tree) o.visible = !on; });
  if (grid) grid.visible = on; redraw();
}

/* ---------- небо HDRI ---------- */
const pmrem = new THREE.PMREMGenerator(renderer);   // небо не задерживает старт: участок виден сразу, небо подгружается следом
const SUN0 = new THREE.Vector3(18, 26, 22); let skyReady = false;
new RGBELoader().setDataType(THREE.FloatType).load(ASSET + 'sky.hdr', tex => {
  const { data, width: W, height: H } = tex.image, C = data.length / (W * H);
  let best = -1, bi = 0; for (let y = 0; y < H / 2; y++) for (let x = 0; x < W; x++) { const i = (y * W + x) * C, l = data[i] * .2126 + data[i + 1] * .7152 + data[i + 2] * .0722; if (l > best) { best = l; bi = y * W + x; } }
  const row = Math.floor(bi / W), u = (bi % W + .5) / W, v = 1 - (row + .5) / H, phi = (u - .5) * Math.PI * 2, el = Math.min(1, Math.max(.5, (v - .5) * Math.PI)), want = Math.atan2(.78, .62);
  scene.environmentRotation.y = scene.backgroundRotation.y = phi - want;
  SUN0.set(Math.cos(el) * Math.cos(want), Math.sin(el), Math.cos(el) * Math.sin(want)).multiplyScalar(60);
  let R = 0, Gc = 0, B = 0, n = 0; for (let y = Math.floor(H * .46); y < Math.floor(H * .495); y++) for (let x = 0; x < W; x += 2) { const i = (y * W + x) * C; if (data[i] > 4) continue; R += data[i]; Gc += data[i + 1]; B += data[i + 2]; n++; }
  if (n) scene.fog.color.setRGB(R / n, Gc / n, B / n, THREE.LinearSRGBColorSpace);
  tex.mapping = THREE.EquirectangularReflectionMapping; scene.background = tex; scene.environment = pmrem.fromEquirectangular(tex).texture;
  skyReady = true; setLight(light); redraw();
}, undefined, () => {});

/* освещение: солнце, пасмурно, вечер */
const LIGHTS = { day: 'Солнце', cloud: 'Пасмурно', eve: 'Вечер' };
let light = 'day';
function setLight(k) {
  light = k; const sky = skyReady;
  const el = Math.atan2(SUN0.y, Math.hypot(SUN0.x, SUN0.z)), az = Math.atan2(SUN0.z, SUN0.x);
  const at = e => sun.position.set(Math.cos(e) * Math.cos(az), Math.sin(e), Math.cos(e) * Math.sin(az)).multiplyScalar(60);
  if (k === 'day') { at(el); sun.color.set('#fff0db'); sun.intensity = 2.7; scene.environmentIntensity = .95; scene.backgroundIntensity = 1.05; renderer.toneMappingExposure = .9; hemi.intensity = sky ? .12 : 1.1; }
  if (k === 'cloud') { at(el); sun.color.set('#eef2f6'); sun.intensity = .45; scene.environmentIntensity = 1.35; scene.backgroundIntensity = .75; renderer.toneMappingExposure = 1; hemi.intensity = sky ? .55 : 1.6; }
  if (k === 'eve') { at(.22); sun.color.set('#ffb36b'); sun.intensity = 2.6; scene.environmentIntensity = .6; scene.backgroundIntensity = .6; renderer.toneMappingExposure = 1.05; hemi.intensity = sky ? .08 : .6; }
  sun.target.position.set(0, 0, 0); $$('[data-light]').forEach(b => b.textContent = LIGHTS[k]); redraw();
}

/* ---------- камера и режимы ---------- */
let mode = '3d', tween = null, CLOCK = 0, lastT = 0;
function cam3d(instant) {
  const w = S.plot.w, d = S.plot.d, tg = new THREE.Vector3(0, .5, d * .12), pos = camera.aspect < 1 ? new THREE.Vector3(w * .2, d * .62, d * .95) : new THREE.Vector3(w * .55, d * .42, d * .82);
  go(pos, tg, instant);
}
/* виды: общий, с улицы на уровне глаз (от калитки), сбоку */
const VIEWS = ['Общий вид', 'С улицы', 'Сбоку']; let view3d = 0;
function camView(i) {
  view3d = i % VIEWS.length; $$('[data-view]').forEach(b => b.textContent = VIEWS[view3d]);
  const w = S.plot.w, d = S.plot.d, h = S.house, o = houseSpec();
  if (view3d === 0) return cam3d();
  if (view3d === 1) { const fz = S.zones.filter(z => z.z2 >= d / 2 - .3), gx = fz.length ? (fz[0].x1 + fz[0].x2) / 2 : h.x + o.door;
    return go(new THREE.Vector3(gx, 1.65, d / 2 - .4), new THREE.Vector3(h.x + o.door * .5, 1.2, h.z + o.d / 2)); }
  go(new THREE.Vector3(-w * .48, 3.4, d * .1), new THREE.Vector3(h.x, .8, h.z + o.d / 2 + 2));
}
function camPlan(instant) { const t = Math.tan(camera.fov * Math.PI / 360), dist = Math.max((S.plot.d / 2 + 1) / t, (S.plot.w / 2 + 1) / (t * camera.aspect)) * 1.04; go(new THREE.Vector3(0, dist, .01), new THREE.Vector3(0, 0, 0), instant); }
function go(pos, tg, instant) { if (instant) { camera.position.copy(pos); controls.target.copy(tg); redraw(false); return; } tween = { t0: CLOCK, p0: camera.position.clone(), g0: controls.target.clone(), p1: pos, g1: tg }; }
function setMode(m) {
  mode = m; draw = null;
  $$('[data-mode]').forEach(b => b.setAttribute('aria-pressed', b.dataset.mode === m));
  $$('[data-plan-only]').forEach(e => e.hidden = m !== 'plan'); $$('[data-draw]').forEach(b => b.setAttribute('aria-pressed', 'false'));
  controls.enableRotate = m === '3d'; LINE_M.depthTest = HOUSE_LINE.depthTest = m !== '3d';
  controls.mouseButtons.LEFT = m === '3d' ? THREE.MOUSE.ROTATE : THREE.MOUSE.PAN;
  controls.touches.ONE = m === '3d' ? THREE.TOUCH.ROTATE : THREE.TOUCH.PAN;
  if (m === 'plan') camPlan(); else camView(0);
  planLook(m === 'plan'); $$('[data-3d-only]').forEach(e => e.hidden = m !== '3d');
  hint(m === 'plan' ? (MOBILE ? 'Двигайте площадки и дом пальцем, тяните за углы' : 'Перетаскивайте площадки и дом, тяните за углы. Края прилипают друг к другу') : '');
  updateHandles();
}
function resize() { const w = view.clientWidth, h = view.clientHeight; if (!w || !h) return; renderer.setSize(w, h, false); camera.aspect = w / h; camera.fov = w / h < 1 ? 55 : 42; camera.updateProjectionMatrix(); redraw(false); }
new ResizeObserver(resize).observe(view);
function fitTop() { const t = $('[data-tryon]').getBoundingClientRect().top + scrollY; document.documentElement.style.setProperty('--to-top', Math.round(t) + 'px'); }
fitTop(); addEventListener('resize', fitTop);
let hintT = 0; function hint(t, ms = 6000) { const h = $('[data-hint]'); h.textContent = t; h.hidden = !t; clearTimeout(hintT); if (t) hintT = setTimeout(() => h.hidden = true, ms); }

/* ---------- выделение и маркеры ---------- */
const handlesG = new THREE.Group(); handlesG.renderOrder = 10; scene.add(handlesG);
const HANDLE_M = new THREE.MeshBasicMaterial({ color: '#8f141c', depthTest: false }), LINE_M = new THREE.LineBasicMaterial({ color: '#8f141c' }), HOUSE_LINE = new THREE.LineBasicMaterial({ color: '#2b6cb0' });
function rectLine(x1, z1, x2, z2, m) { const g = new THREE.BufferGeometry().setFromPoints([[x1, z1], [x2, z1], [x2, z2], [x1, z2], [x1, z1]].map(([x, z]) => new THREE.Vector3(x, .06, z))); const l = new THREE.Line(g, m); l.renderOrder = 10; return l; }
function handleSize() { return Math.max(.25, camera.position.distanceTo(controls.target) * (MOBILE ? .03 : .018)); }
const LBL = new Map(), num = v => (Math.round(v * 10) / 10).toString().replace('.', ',');
function label(text, on) {   // подпись размера: текстура рисуется один раз на текст
  const key = text + (on ? '*' : ''); let m = LBL.get(key);
  if (!m) { if (LBL.size > 150) { LBL.forEach(v => { v.map.dispose(); v.dispose(); }); LBL.clear(); }
    const c = document.createElement('canvas'), x = c.getContext('2d'), f = 44; x.font = `700 ${f}px Onest, Arial`; const tw = Math.ceil(x.measureText(text).width) + 36;
    c.width = tw; c.height = f + 24; x.font = `700 ${f}px Onest, Arial`; x.fillStyle = on ? 'rgba(143,20,28,.92)' : 'rgba(28,24,23,.78)'; x.beginPath(); x.roundRect(0, 0, c.width, c.height, 18); x.fill();
    x.fillStyle = '#fff'; x.textBaseline = 'middle'; x.textAlign = 'center'; x.fillText(text, c.width / 2, c.height / 2 + 2);
    const t = new THREE.CanvasTexture(c); t.colorSpace = THREE.SRGBColorSpace; m = new THREE.SpriteMaterial({ map: t, depthTest: false, transparent: true }); m.userData.aspect = c.width / c.height; LBL.set(key, m); }
  const s = new THREE.Sprite(m), h = handleSize() * 1.25; s.scale.set(h * m.userData.aspect, h, 1); s.renderOrder = 12; s.userData.label = true; return s;
}
function updateHandles() {
  handlesG.children.forEach(c => { if (!c.userData.label) c.geometry.dispose(); }); handlesG.clear();
  if (mode === 'plan') S.zones.forEach(z => { const w = z.x2 - z.x1, d = z.z2 - z.z1, on = sel && sel.id === z.id, l = label(`${num(w)} × ${num(d)} м`, on); l.position.set((z.x1 + z.x2) / 2, .3, (z.z1 + z.z2) / 2); handlesG.add(l); });
  if (!sel) { redraw(false); return; }
  if (sel.kind === 'house') { const f = houseFoot(); handlesG.add(rectLine(f[0], f[1], f[2], f[3], HOUSE_LINE)); redraw(false); return; }
  const z = S.zones.find(q => q.id === sel.id); if (!z) { sel = null; return; }
  handlesG.add(rectLine(z.x1, z.z1, z.x2, z.z2, LINE_M));
  if (mode === 'plan') { const s = handleSize(); for (const [x, zz] of [[z.x1, z.z1], [z.x2, z.z1], [z.x1, z.z2], [z.x2, z.z2]]) { const h = new THREE.Mesh(new THREE.BoxGeometry(s, .05, s), HANDLE_M); h.position.set(x, .08, zz); h.renderOrder = 11; handlesG.add(h); } }
  redraw(false);
}
function select(s) { sel = s; updateHandles(); if (s && s.kind === 'zone') openTab('zone'); else if (s && s.kind === 'house') openTab('house'); else renderPanel(); }

/* ---------- мышь и касания ---------- */
const ray = new THREE.Raycaster(), ndc = new THREE.Vector2(), ground = new THREE.Plane(new THREE.Vector3(0, 1, 0), 0);
function groundAt(e) { const r = renderer.domElement.getBoundingClientRect(); ndc.set(((e.clientX - r.left) / r.width) * 2 - 1, -((e.clientY - r.top) / r.height) * 2 + 1); ray.setFromCamera(ndc, camera); const p = new THREE.Vector3(); return ray.ray.intersectPlane(ground, p) ? p : null; }
const snap = v => Math.round(v * 10) / 10;
const clampX = v => Math.max(-S.plot.w / 2, Math.min(S.plot.w / 2, v)), clampZ = v => Math.max(-S.plot.d / 2, Math.min(S.plot.d / 2, v));
function zoneAt(p) { for (let i = S.zones.length - 1; i >= 0; i--) { const z = S.zones[i]; if (p.x >= z.x1 && p.x <= z.x2 && p.z >= z.z1 && p.z <= z.z2) return z; } return null; }
function inHouse(p) { const f = houseFoot(); return p.x >= f[0] && p.x <= f[2] && p.z >= f[1] && p.z <= f[3]; }
let op = null, draw = null, downXY = null;
/* магнит: край прилипает к краям других площадок, дома и участка */
function magnet(edges, axis, self) {
  const c = [axis === 'x' ? -S.plot.w / 2 : -S.plot.d / 2, axis === 'x' ? S.plot.w / 2 : S.plot.d / 2];
  for (const z of S.zones) if (z !== self) c.push(...(axis === 'x' ? [z.x1, z.x2] : [z.z1, z.z2]));
  const f = houseFoot(); for (const b of houseBoxes()) c.push(...(axis === 'x' ? [b[0], b[2]] : [b[1], b[3]]));
  c.push(axis === 'x' ? f[0] : f[3]);
  const tol = Math.max(.15, handleSize() * .45); let best = 0, bd = tol;
  for (const v of c) for (const e of edges) { const d = v - e; if (Math.abs(d) < bd) { bd = Math.abs(d); best = d; } }
  return +best.toFixed(2);
}
view.addEventListener('pointerdown', e => {
  if (e.target !== renderer.domElement || e.button > 0) return;
  downXY = [e.clientX, e.clientY];
  const p = groundAt(e); if (!p) return;
  if (mode === 'plan') {
    if (draw) { op = { t: 'draw', kind: draw, x0: snap(clampX(p.x)), z0: snap(clampZ(p.z)), zone: null }; }
    else {
      const z = sel && sel.kind === 'zone' ? S.zones.find(q => q.id === sel.id) : null, hs = handleSize() * .9;
      const corner = z && [[z.x1, z.z1, 'x1', 'z1'], [z.x2, z.z1, 'x2', 'z1'], [z.x1, z.z2, 'x1', 'z2'], [z.x2, z.z2, 'x2', 'z2']].find(([x, zz]) => Math.abs(p.x - x) < hs && Math.abs(p.z - zz) < hs);
      if (corner) op = { t: 'resize', z, kx: corner[2], kz: corner[3] };
      else { const hit = zoneAt(p); if (hit) { select({ kind: 'zone', id: hit.id }); op = { t: 'move', z: hit, dx: p.x - hit.x1, dz: p.z - hit.z1, w: hit.x2 - hit.x1, d: hit.z2 - hit.z1 }; }
        else if (inHouse(p)) { select({ kind: 'house' }); op = { t: 'house', dx: p.x - S.house.x, dz: p.z - S.house.z }; } }
    }
    if (op) { controls.enabled = false; renderer.domElement.setPointerCapture(e.pointerId); }
  }
}, true);
view.addEventListener('pointermove', e => {
  if (!op) return; const p = groundAt(e); if (!p) return;
  const x = snap(clampX(p.x)), z = snap(clampZ(p.z));
  if (op.t === 'draw') {
    if (!op.zone && Math.abs(x - op.x0) < .3 && Math.abs(z - op.z0) < .3) return;
    if (!op.zone) { const base = (sel && sel.kind === 'zone' && S.zones.find(q => q.id === sel.id)) || S.zones.filter(q => q.kind === 'pave').slice(-1)[0] || {};
      op.zone = Z(op.x0, op.z0, op.x0 + .4, op.z0 + .4, op.kind === 'grid' ? { kind: 'grid', kerb: false } : { form: base.form || 'kirpich', color: base.color || 'osen', pat: base.pat || 'perevyazka', th: base.th || 60, kayma: base.kayma || '' });
      fixZone(op.zone); S.zones.push(op.zone); buildZones(); sel = { kind: 'zone', id: op.zone.id }; }
    const zz = op.zone, mx = x + magnet([x], 'x', zz), mz = z + magnet([z], 'z', zz); zz.x1 = Math.min(op.x0, mx); zz.x2 = Math.max(op.x0, mx); zz.z1 = Math.min(op.z0, mz); zz.z2 = Math.max(op.z0, mz);
    if (zz.x2 - zz.x1 < .4) zz.x2 = zz.x1 + .4; if (zz.z2 - zz.z1 < .4) zz.z2 = zz.z1 + .4; moveZoneMesh(zz);
  } else if (op.t === 'move') {
    const zz = op.z; let x1 = snap(Math.max(-S.plot.w / 2, Math.min(S.plot.w / 2 - op.w, p.x - op.dx))), z1 = snap(Math.max(-S.plot.d / 2, Math.min(S.plot.d / 2 - op.d, p.z - op.dz)));
    x1 += magnet([x1, x1 + op.w], 'x', zz); z1 += magnet([z1, z1 + op.d], 'z', zz);
    zz.x1 = +x1.toFixed(2); zz.z1 = +z1.toFixed(2); zz.x2 = +(x1 + op.w).toFixed(2); zz.z2 = +(z1 + op.d).toFixed(2); moveZoneMesh(zz);
  } else if (op.t === 'resize') {
    const zz = op.z; zz[op.kx] = +(x + magnet([x], 'x', zz)).toFixed(2); zz[op.kz] = +(z + magnet([z], 'z', zz)).toFixed(2);
    if (zz.x2 - zz.x1 < .4) { if (op.kx === 'x1') zz.x1 = zz.x2 - .4; else zz.x2 = zz.x1 + .4; } if (zz.z2 - zz.z1 < .4) { if (op.kz === 'z1') zz.z1 = zz.z2 - .4; else zz.z2 = zz.z1 + .4; } moveZoneMesh(zz);
  } else if (op.t === 'house') {
    S.house.x = snap(p.x - op.dx); S.house.z = snap(p.z - op.dz); const hm = worldG.children[0]; if (hm) hm.position.set(S.house.x, 0, S.house.z); updateHandles(); redraw(false);
  }
});
window.addEventListener('pointerup', e => {
  const moved = downXY ? Math.hypot(e.clientX - downXY[0], e.clientY - downXY[1]) : 99; downXY = null;
  if (op) {
    const t = op.t; op = null; controls.enabled = true;
    if (t === 'house') buildWorld(); if (t !== 'draw' || sel) buildZones();
    if (t === 'draw') { draw = null; $$('[data-draw]').forEach(b => b.setAttribute('aria-pressed', 'false')); hint(''); openTab('zone'); }
    commit(); renderPanel(); return;
  }
  if (e.target === renderer.domElement && moved < 6) {   // простой клик: выделить
    const p = groundAt(e); if (!p) return; const hit = zoneAt(p);
    if (hit) select({ kind: 'zone', id: hit.id }); else if (inHouse(p)) select({ kind: 'house' }); else if (mode === 'plan') select(null);
  }
});
window.addEventListener('keydown', e => {
  if (e.target.closest('input,textarea,select')) return;
  if ((e.key === 'Delete' || e.key === 'Backspace') && sel && sel.kind === 'zone') { delZone(sel.id); e.preventDefault(); }
  const k = e.key.toLowerCase();
  if ((e.ctrlKey || e.metaKey) && (k === 'y' || (k === 'z' && e.shiftKey))) { redo(); e.preventDefault(); return; }
  if ((e.ctrlKey || e.metaKey) && k === 'z') { undo(); e.preventDefault(); return; }
  const z = sel && sel.kind === 'zone' && S.zones.find(q => q.id === sel.id), dir = { arrowleft: [-1, 0], arrowright: [1, 0], arrowup: [0, -1], arrowdown: [0, 1] }[k];
  if (z && dir && mode === 'plan') {   // стрелки двигают площадку на 10 см, с Shift на 1 м
    const s = e.shiftKey ? 1 : .1, w = z.x2 - z.x1, d = z.z2 - z.z1;
    z.x1 = +Math.max(-S.plot.w / 2, Math.min(S.plot.w / 2 - w, z.x1 + dir[0] * s)).toFixed(2); z.x2 = +(z.x1 + w).toFixed(2);
    z.z1 = +Math.max(-S.plot.d / 2, Math.min(S.plot.d / 2 - d, z.z1 + dir[1] * s)).toFixed(2); z.z2 = +(z.z1 + d).toFixed(2);
    moveZoneMesh(z); clearTimeout(window._nt); window._nt = setTimeout(() => { buildZones(); commit(); renderPanel(); }, 350); e.preventDefault();
  }
});
function delZone(id) { S.zones = S.zones.filter(z => z.id !== id); sel = null; buildZones(); commit(); renderPanel(); }

/* ---------- панель ---------- */
let tab = 'plot';
function openTab(t) { tab = t;
  if (t === 'zone' && !(sel && sel.kind === 'zone') && S.zones.length) { sel = { kind: 'zone', id: S.zones[0].id }; updateHandles(); } $$('[data-tab]').forEach(b => b.setAttribute('aria-selected', b.dataset.tab === t)); renderPanel(); }
$$('[data-tab]').forEach(b => b.addEventListener('click', () => openTab(b.dataset.tab)));
const esc = s => String(s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
const dots = (list, cur, attr, custom) => '<div class="dots">' + list.map(([n, h]) => `<button type="button" class="dot-b" ${attr}="${h}" title="${esc(n)}" aria-label="${esc(n)}" aria-pressed="${h.toLowerCase() === String(cur).toLowerCase()}" style="background:${h}"></button>`).join('') + (custom ? `<label class="to-custom" title="Свой цвет"><input type="color" ${attr}-custom value="${cur}">свой</label>` : '') + '</div>';
const nameOf = (list, h) => (list.find(x => x[1].toLowerCase() === String(h).toLowerCase()) || ['свой цвет'])[0];
const card = (attr, val, cur, title, sub) => `<button type="button" class="to-card" ${attr}="${val}" aria-pressed="${val == cur}">${esc(title)}${sub ? `<small>${esc(sub)}</small>` : ''}</button>`;
const seg = (attr, opts, cur) => '<div class="seg">' + opts.map(([v, n]) => `<button type="button" ${attr}="${v}" aria-pressed="${v == cur}">${esc(n)}</button>`).join('') + '</div>';

function renderPanel() {
  const h = S.house, z = sel && sel.kind === 'zone' ? S.zones.find(q => q.id === sel.id) : null;
  let html = '';
  if (tab === 'plot') {
    html += `<div class="to-sec"><h3>Готовый участок</h3><div class="to-cards">${Object.entries(TEMPLATES).map(([k, t]) => card('data-tpl', k, S.tpl, t.name, t.sub)).join('')}</div><p class="to-note">Выберите похожий двор, а потом меняйте под себя.</p></div>`;
    html += `<div class="to-sec"><h3>Размер участка</h3><div class="to-row"><b>Ширина</b></div><div class="to-range"><input type="range" min="14" max="40" step="1" value="${S.plot.w}" data-plot="w"><output>${S.plot.w} м</output></div>
      <div class="to-row" style="margin-top:8px"><b>Глубина</b></div><div class="to-range"><input type="range" min="14" max="40" step="1" value="${S.plot.d}" data-plot="d"><output>${S.plot.d} м</output></div></div>`;
    html += `<div class="to-sec"><div class="to-row"><h3>Площадки и дорожки</h3><span>${S.zones.length}</span></div><div class="to-zones">${S.zones.map((q, i) => zoneRow(q, i)).join('') || '<div class="to-empty">Площадок нет. Нажмите «План» и «+ Площадка».</div>'}</div>
      <div class="to-btns" style="margin-top:10px"><button type="button" class="btn btn-line" data-add="pave">+ Площадка</button><button type="button" class="btn btn-line" data-add="grid">+ Газонная решётка</button></div>
      <p class="to-note">В режиме «План» площадки и дом можно перетаскивать, а размер менять за углы.</p></div>`;
    const vars = LS.get('mw3d_vars') || [];
    html += `<div class="to-sec"><div class="to-row"><h3>Мои варианты</h3><span>${vars.length} из 4</span></div>
      ${vars.length ? `<div class="to-vars">${vars.map((v, n) => `<div class="to-var"><button type="button" data-var="${n}" title="Открыть вариант"><img src="${v.img}" alt=""><span>${esc(v.name)}<small>≈ ${fmt(v.total)} ₽</small></span></button><button type="button" class="to-x" data-vdel="${n}" aria-label="Удалить вариант">×</button></div>`).join('')}</div>` : ''}
      <div class="to-btns" style="margin-top:8px"><button type="button" class="btn btn-line" data-vsave ${vars.length >= 4 ? 'disabled' : ''}>Сохранить как вариант</button><button type="button" class="btn btn-line" data-reset>Начать заново</button></div>
      <p class="to-note">Сохраните 2–3 варианта цвета и сравните: они останутся в этом браузере.</p></div>`;
  } else if (tab === 'house') {
    const fc = h.facade.m === 'brick' ? BRICK_COLORS : h.facade.m === 'wood' || h.facade.m === 'siding' ? (h.facade.m === 'wood' ? WOOD_COLORS : FACADE_COLORS) : FACADE_COLORS;
    html += `<div class="to-sec"><h3>Дом</h3><div class="to-cards">${Object.entries(HOUSES).map(([k, o]) => card('data-house', k, h.type, o.name, o.sub)).join('')}</div></div>`;
    html += `<div class="to-sec"><h3>Фасад</h3>${seg('data-fm', Object.entries(FACADE_MATS), h.facade.m)}<div class="to-row" style="margin-top:10px"><b>Цвет</b><span>${esc(nameOf(fc, h.facade.c))}</span></div>${dots(fc, h.facade.c, 'data-fc', true)}</div>`;
    html += `<div class="to-sec"><h3>Крыша</h3>${seg('data-rm', Object.entries(ROOF_MATS), h.roof.m)}<div class="to-row" style="margin-top:10px"><b>Цвет</b><span>${esc(nameOf(RAL, h.roof.c))}</span></div>${dots(RAL, h.roof.c, 'data-rc', true)}</div>`;
    html += `<div class="to-sec"><div class="to-row"><b>Цоколь и ступени</b><span>${esc(nameOf(PLINTH_COLORS, h.plinth))}</span></div>${dots(PLINTH_COLORS, h.plinth, 'data-pc')}</div>`;
    html += `<div class="to-sec"><div class="to-row"><b>Окна</b><span>${esc(nameOf(WIN_COLORS, h.win))}</span></div>${dots(WIN_COLORS, h.win, 'data-wc')}</div>`;
    html += `<div class="to-sec"><div class="to-row"><b>Дверь</b><span>${esc(nameOf(DOOR_COLORS, h.door))}</span></div>${dots(DOOR_COLORS, h.door, 'data-dc')}</div>`;
    html += `<p class="to-note">Дом можно передвинуть в режиме «План».</p>`;
  } else if (tab === 'fence') {
    html += `<div class="to-sec"><h3>Забор</h3><div class="to-cards">${Object.entries(FENCE_TYPES).map(([k, n]) => card('data-ft', k, S.fence.t, n)).join('')}</div></div>`;
    if (S.fence.t !== 'none') html += `<div class="to-sec"><div class="to-row"><b>Цвет</b><span>${esc(nameOf(FENCE_COLORS, S.fence.c))}</span></div>${dots(FENCE_COLORS, S.fence.c, 'data-fcol', true)}</div><p class="to-note">Ворота появляются сами там, где дорожка выходит к забору.</p>`;
  } else if (tab === 'zone') {
    if (!z) html += `<div class="to-empty">Выберите площадку: нажмите на неё на участке или в списке.</div><div class="to-zones" style="margin-top:10px">${S.zones.map((q, i) => zoneRow(q, i)).join('')}</div>`;
    else html += zonePanel(z);
  } else if (tab === 'sum') html += sumPanel();
  body.innerHTML = html; renderFoot(); renderSwap();
}
function zoneRow(q, i) {
  const a = areas[S.zones.indexOf(q) + 1] || 0, sw = q.kind === 'grid' ? 'repeating-conic-gradient(#9d968c 0 25%,#6e9b48 0 50%) 0 0/10px 10px' : swatch((D.colors[q.color] || {}).pal || []);
  const sub = q.kind === 'grid' ? 'газонная решётка' : `${(FORMS[q.form] || {}).name || ''} · ${(D.colors[q.color] || {}).name || ''}`, pr = zPrice(q), sum = pr ? Math.round(a * (q.kind === 'grid' ? 1.05 : 1.07) * pr / 100) * 100 : 0;
  return `<button type="button" class="to-zone" data-zsel="${q.id}" aria-pressed="${sel && sel.id === q.id}"><i style="background:${sw}"></i><span>${esc(zoneName(q, i))}<small>${esc(sub)} · ${num(a)} м²</small></span>${sum ? `<b>${fmt(sum)} ₽</b>` : ''}</button>`;
}
function zonePanel(z) {
  const i = S.zones.indexOf(z), a = areas[i + 1] || 0;
  let h = `<div class="to-sec"><div class="to-row"><h3>${esc(zoneName(z, i))}</h3><span>${a.toFixed(1).replace('.', ',')} м²</span></div>
    <div class="to-size"><label>Длина, м<input type="number" step="0.1" min="0.4" value="${(z.x2 - z.x1).toFixed(1)}" data-zw></label><label>Ширина, м<input type="number" step="0.1" min="0.4" value="${(z.z2 - z.z1).toFixed(1)}" data-zd></label></div></div>`;
  h += `<div class="to-sec">${seg('data-zkind', [['pave', 'Брусчатка'], ['grid', 'Газонная решётка']], z.kind)}</div>`;
  if (z.kind === 'pave') {
    const cols = formColors(z.form), pats = formSpec(z.form).patterns, t = ths(z.form, z.color), price = zPrice(z);
    h += `<div class="to-sec"><h3>Форма</h3><div class="to-cards">${D.forms.map(f => card('data-zform', f.slug, z.form, f.name, f.el.map(e => e.join('×')).join(', ') + ' мм')).join('')}</div></div>`;
    h += `<div class="to-sec"><div class="to-row"><b>Цвет</b><span>${esc((D.colors[z.color] || {}).name || '')}${price ? ' · ' + fmt(price) + ' ₽/м²' : ''}</span></div><div class="dots">${cols.map(c => `<button type="button" class="dot-b" data-zcol="${c}" title="${esc(D.colors[c].name)}" aria-label="${esc(D.colors[c].name)}" aria-pressed="${c === z.color}" style="background:${swatch(D.colors[c].pal)}"></button>`).join('')}</div></div>`;
    h += `<div class="to-sec"><b class="to-row">Рисунок укладки</b>${seg('data-zpat', pats.map(p => [p, PATTERN_NAMES[p]]), z.pat)}</div>`;
    if (t.length > 1) h += `<div class="to-sec"><b class="to-row">Толщина</b>${seg('data-zth', t.map(x => [x, x + ' мм']), z.th)}</div>`;
    h += `<div class="to-sec"><div class="to-row"><b>Кайма по краю</b><span>${z.kayma ? esc(D.colors[z.kayma].name) : 'нет'}</span></div><div class="dots"><button type="button" class="dot-b" data-zkay="" aria-pressed="${!z.kayma}" title="Без каймы" aria-label="Без каймы" style="background:#fff;box-shadow:inset 0 0 0 2px #ddd"></button>${formColors('kirpich').map(c => `<button type="button" class="dot-b" data-zkay="${c}" aria-pressed="${c === z.kayma}" title="${esc(D.colors[c].name)}" aria-label="${esc(D.colors[c].name)}" style="background:${swatch(D.colors[c].pal)}"></button>`).join('')}</div></div>`;
  }
  h += `<div class="to-sec"><label class="to-check"><input type="checkbox" data-zkerb ${z.kerb ? 'checked' : ''}> Бордюр по краю</label>${z.kerb && D.kerbs.length ? `<select data-zkt style="margin-top:8px">${D.kerbs.map((k, n) => `<option value="${n}" ${n === z.kerbType ? 'selected' : ''}>${esc(k.name)}${k.price ? ' · ' + fmt(k.price) + ' ₽/шт' : ''}</option>`).join('')}</select>` : ''}</div>`;
  h += `<div class="to-btns">${z.kind === 'pave' ? `<button type="button" class="btn btn-line" data-zall>Применить ко всем площадкам</button>` : ''}<button type="button" class="btn btn-line" data-zdup>Копировать</button><button type="button" class="btn btn-line" data-zrot>Повернуть</button><button type="button" class="btn btn-line" data-zdel style="color:#c62828;border-color:#f0c9c9">Удалить</button></div>`;
  if (z.kind === 'pave' && FORMS[z.form]) h += `<p class="to-note"><a href="${FORMS[z.form].url}?cvet=${z.color}" target="_blank">Открыть «${esc(FORMS[z.form].name)}» в каталоге ↗</a></p>`;
  return h;
}
function calc() {
  const rows = []; let total = 0;
  S.zones.forEach((z, i) => { const a = areas[i + 1] || 0; if (a <= 0) return; const res = z.kind === 'grid' ? 1.05 : 1.07, m2 = a * res, pr = zPrice(z), sum = pr ? Math.round(m2 * pr / 100) * 100 : 0; total += sum;
    const perPal = z.kind === 'pave' && FORMS[z.form] ? FORMS[z.form].m2 : 0, pal = perPal ? Math.ceil(m2 / perPal) : 0;
    rows.push({ name: zoneName(z, i), what: z.kind === 'grid' ? 'Газонная решётка' : `«${(FORMS[z.form] || {}).name}» ${(D.colors[z.color] || {}).name}, ${PATTERN_NAMES[z.pat].toLowerCase()}, ${z.th} мм`, m2, pr, sum, a, pal });
    if (z.kerb && kerbLen[z.id] && D.kerbs[z.kerbType]) { const k = D.kerbs[z.kerbType], n = Math.ceil(kerbLen[z.id] / k.len), s = k.price ? n * k.price : 0; total += s; rows.push({ name: 'Бордюр: ' + zoneName(z, i), what: k.name, pcs: n, pr: k.price, sum: s }); } });
  return { rows, total };
}
function sumPanel() {
  const { rows, total } = calc();
  if (!rows.length) return '<div class="to-empty">Добавьте площадку, и здесь появится смета.</div>';
  const pals = rows.reduce((s, r) => s + (r.pal || 0), 0);
  let h = '<table class="to-sum">' + rows.map(r => `<tr><td>${esc(r.name)}<small>${esc(r.what)}${r.m2 ? ` · ${num(r.a)} м², с запасом ${num(r.m2)} м²${r.pal ? `, ≈ ${r.pal} ${plural(r.pal, 'поддон', 'поддона', 'поддонов')}` : ''}` : ` · ${r.pcs} шт`}</small></td><td>${r.sum ? fmt(r.sum) + ' ₽' : 'по запросу'}</td></tr>`).join('') + '</table>';
  h += `<div class="to-total"><span>Примерно, без доставки${pals ? `<small>около ${pals} ${plural(pals, 'поддона', 'поддонов', 'поддонов')} плитки</small>` : ''}</span><b>≈ ${fmt(Math.round(total / 500) * 500)} ₽</b></div>`;
  h += `<div class="to-btns"><button type="button" class="btn btn-brand" data-send>Отправить проект менеджеру</button><button type="button" class="btn btn-line" data-share>Поделиться</button><button type="button" class="btn btn-line" data-shot>Сохранить картинку</button><button type="button" class="btn btn-line" data-copy>Скопировать ссылку</button></div>`;
  h += `<p class="to-note">Запас на подрезку: 7% для плитки, 5% для решётки. Цены из каталога, точный расчёт с доставкой пришлёт менеджер.</p><p class="to-note">${esc(D.disclaimer)}</p>`;
  return h;
}

/* действия в панели */
body.addEventListener('click', e => {
  const t = e.target.closest('button, [data-zsel]'); if (!t) return; const d = t.dataset, h = S.house, z = sel && sel.kind === 'zone' ? S.zones.find(q => q.id === sel.id) : null;
  let world = false, zones = false, look = false;
  if (d.tpl) { const keepLook = { house: { facade: h.facade, roof: h.roof, plinth: h.plinth, win: h.win, door: h.door }, fence: S.fence };
    S = fromTemplate(+d.tpl); Object.assign(S.house, keepLook.house); S.fence = keepLook.fence; S.zones.forEach(fixZone); sel = null; world = zones = true; cam3d(); }
  else if (d.zsel) { select({ kind: 'zone', id: +d.zsel }); return; }
  else if (d.add) { const base = S.zones.filter(q => q.kind === 'pave').slice(-1)[0] || {}; const nz = Z(-1.5, 2, 1.5, 4, d.add === 'grid' ? { kind: 'grid', kerb: false } : { form: base.form || 'kirpich', color: base.color || 'osen', pat: base.pat || 'perevyazka' }); fixZone(nz); S.zones.push(nz); sel = { kind: 'zone', id: nz.id }; zones = true; tab = 'zone'; hint('Новая площадка в центре участка. Перетащите её в «Плане»'); }
  else if (d.house) { h.type = d.house; world = zones = true; }
  else if (d.fm) { h.facade.m = d.fm; h.facade.c = (d.fm === 'brick' ? BRICK_COLORS : d.fm === 'wood' ? WOOD_COLORS : FACADE_COLORS)[0][1]; look = true; }
  else if (d.fc) { h.facade.c = d.fc; look = true; } else if (d.rm) { h.roof.m = d.rm; look = true; } else if (d.rc) { h.roof.c = d.rc; look = true; }
  else if (d.pc) { h.plinth = d.pc; look = true; } else if (d.wc) { h.win = d.wc; look = true; } else if (d.dc) { h.door = d.dc; look = true; }
  else if (d.ft) { S.fence.t = d.ft; buildFence(); redraw(); }
  else if (d.fcol) { S.fence.c = d.fcol; HM.fence.color.set(d.fcol); HM.mesh.color.set(d.fcol); redraw(false); }
  else if (z && d.zkind) { z.kind = d.zkind; if (z.kind === 'grid') z.kerb = false; fixZone(z); zones = true; }
  else if (z && d.zform) { z.form = d.zform; fixZone(z); zones = true; }
  else if (z && d.zcol) { z.color = d.zcol; fixZone(z); zones = true; }
  else if (z && d.zpat) { z.pat = d.zpat; zones = true; }
  else if (z && d.zth) { z.th = +d.zth; }
  else if (z && 'zkay' in d) { z.kayma = d.zkay; zones = true; }
  else if (z && 'zall' in d) { S.zones.filter(q => q.kind === 'pave').forEach(q => { q.form = z.form; q.color = z.color; q.pat = z.pat; q.th = z.th; fixZone(q); }); zones = true; hint('Применено ко всем площадкам'); }
  else if (z && 'zdel' in d) { delZone(z.id); return; }
  else if (z && 'zdup' in d) { const w = z.x2 - z.x1, dd = z.z2 - z.z1, c = { ...z, id: zid++ };
    if (z.x2 + w <= S.plot.w / 2) { c.x1 = z.x2; c.x2 = z.x2 + w; } else if (z.x1 - w >= -S.plot.w / 2) { c.x1 = z.x1 - w; c.x2 = z.x1; } else { c.z1 = Math.min(S.plot.d / 2 - dd, z.z2); c.z2 = c.z1 + dd; }
    S.zones.push(c); sel = { kind: 'zone', id: c.id }; zones = true; hint('Копия стоит рядом. Передвиньте её в «Плане»', 3500); }
  else if (z && 'zrot' in d) { const cx = (z.x1 + z.x2) / 2, cz = (z.z1 + z.z2) / 2, w = z.z2 - z.z1, dd = z.x2 - z.x1;
    z.x1 = snap(Math.max(-S.plot.w / 2, Math.min(S.plot.w / 2 - w, cx - w / 2))); z.x2 = +(z.x1 + w).toFixed(2); z.z1 = snap(Math.max(-S.plot.d / 2, Math.min(S.plot.d / 2 - dd, cz - dd / 2))); z.z2 = +(z.z1 + dd).toFixed(2); zones = true; }
  else if ('vsave' in d) { saveVariant(); return; }
  else if (d.var) { const v = (LS.get('mw3d_vars') || [])[+d.var]; const o = v && unpack(v.s); if (!o) return; S = o; zid = Math.max(0, ...S.zones.map(q => q.id)) + 1; sel = null; world = zones = true; look = true; hint('Открыт вариант «' + v.name + '»', 2500); }
  else if (d.vdel) { const vars = LS.get('mw3d_vars') || []; vars.splice(+d.vdel, 1); LS.set('mw3d_vars', vars); renderPanel(); return; }
  else if ('reset' in d) { if (!confirm('Начать заново? Текущий проект сбросится (сохранённые варианты останутся).')) return; S = fromTemplate(1); S.zones.forEach(fixZone); zid = Math.max(0, ...S.zones.map(q => q.id)) + 1; sel = null; world = zones = true; look = true; cam3d(); }
  else if ('share' in d) { shareProject(); return; }
  else if ('send' in d) { sendProject(); return; }
  else if ('shot' in d) { saveShot(); return; }
  else if ('copy' in d) { copyLink(); return; }
  else return;
  if (world) buildWorld(); if (zones) buildZones(); if (look) applyHouseLook();
  commit(); renderPanel();
});
body.addEventListener('input', e => {
  const t = e.target, d = t.dataset, z = sel && sel.kind === 'zone' ? S.zones.find(q => q.id === sel.id) : null;
  if (d.plot) { S.plot[d.plot] = +t.value; t.nextElementSibling.textContent = t.value + ' м'; S.zones.forEach(q => { q.x1 = clampX(q.x1); q.x2 = clampX(q.x2); q.z1 = clampZ(q.z1); q.z2 = clampZ(q.z2); }); clearTimeout(body._pt); body._pt = setTimeout(() => { buildWorld(); buildZones(); commit(); }, 120); return; }
  if ('fcCustom' in d) { S.house.facade.c = t.value; applyHouseLook(); return; }
  if ('rcCustom' in d) { S.house.roof.c = t.value; applyHouseLook(); return; }
  if ('fcolCustom' in d) { S.fence.c = t.value; HM.fence.color.set(t.value); HM.mesh.color.set(t.value); redraw(false); return; }
  if (z && ('zw' in d || 'zd' in d)) { const v = Math.max(.4, Math.min(40, parseFloat(t.value) || .4)); if ('zw' in d) z.x2 = +(z.x1 + v).toFixed(1); else z.z2 = +(z.z1 + v).toFixed(1); moveZoneMesh(z); clearTimeout(body._zt); body._zt = setTimeout(() => { buildZones(); commit(); }, 300); }
});
body.addEventListener('change', e => {
  const t = e.target, d = t.dataset, z = sel && sel.kind === 'zone' ? S.zones.find(q => q.id === sel.id) : null;
  if (z && 'zkerb' in d) { z.kerb = t.checked; buildZones(); commit(); renderPanel(); }
  if (z && 'zkt' in d) { z.kerbType = +t.value; commit(); }
  if ('fcCustom' in d || 'rcCustom' in d || 'fcolCustom' in d) { commit(); renderPanel(); }
  if (z && ('zw' in d || 'zd' in d)) renderPanel();
});

/* инструменты над сценой */
$$('[data-mode]').forEach(b => b.addEventListener('click', () => setMode(b.dataset.mode)));
$$('[data-draw]').forEach(b => b.addEventListener('click', () => { draw = draw === b.dataset.draw ? null : b.dataset.draw; $$('[data-draw]').forEach(x => x.setAttribute('aria-pressed', x.dataset.draw === draw)); hint(draw ? 'Проведите пальцем или мышью по участку, чтобы нарисовать прямоугольник' : ''); }));
$('[data-undo]').addEventListener('click', undo); $('[data-redo]').addEventListener('click', redo);
$('[data-view]').addEventListener('click', () => camView(view3d + 1));
$('[data-light]').addEventListener('click', () => { const k = Object.keys(LIGHTS); setLight(k[(k.indexOf(light) + 1) % k.length]); });
view.addEventListener('pointerdown', () => { if (CLOCK > 1500) $('[data-hint]').hidden = true; });

/* быстрая смена цвета прямо на сцене: для выбранной площадки или для всех сразу */
function swapTarget() { const z = sel && sel.kind === 'zone' && S.zones.find(q => q.id === sel.id); if (z && z.kind === 'pave') return [z]; return S.zones.filter(q => q.kind === 'pave'); }
function renderSwap() {
  const el = $('[data-swap]'), t = swapTarget(); el.hidden = !t.length; if (!t.length) return;
  $('[data-swt]').textContent = t.length === 1 && sel && sel.kind === 'zone' ? zoneName(t[0], S.zones.indexOf(t[0])) : 'Все площадки';
  const same = t.every(q => q.color === t[0].color); $('[data-swn]').textContent = same ? (D.colors[t[0].color] || {}).name : 'разные цвета';
}
$$('[data-sw]').forEach(b => b.addEventListener('click', () => {
  const t = swapTarget(); if (!t.length) return; const cols = formColors(t[0].form), i = cols.indexOf(t[0].color), c = cols[(i + +b.dataset.sw + cols.length) % cols.length];
  t.forEach(z => { if (D.prices[z.form] && D.prices[z.form][c]) { z.color = c; fixZone(z); const m = zoneMeshes.get(z.id); if (m) m.material = zoneMat(z); } });
  redraw(false); commit(); renderPanel();
}));
/* плашка с ценой всегда на виду */
function renderFoot() {
  const { total } = calc(), area = S.zones.reduce((s, z, i) => s + (areas[i + 1] || 0), 0);
  $('[data-foot]').innerHTML = total ? `<div><small>${num(area)} м² · без доставки</small><b>≈ ${fmt(Math.round(total / 500) * 500)} ₽</b></div><button type="button" class="btn btn-brand" data-send>Отправить проект</button>` : `<div><small>Смета</small><b>Нарисуйте площадку</b></div>`;
}
$('[data-foot]').addEventListener('click', e => { if (e.target.closest('[data-send]')) sendProject(); });
$$('.to-tools [data-shot]').forEach(b => b.addEventListener('click', saveShot));

/* ---------- картинка, ссылка, заявка ---------- */
function render3() { const hs = handlesG.visible; handlesG.visible = false; renderer.render(scene, camera); handlesG.visible = hs; redraw(false); return renderer.domElement; }
function snapshot(maxW = 1600, list = true) {
  const src = render3(), k = Math.min(1, maxW / src.width), W = Math.round(src.width * k), H = Math.round(src.height * k), u = Math.round(Math.max(44, W * .045));
  const m = document.createElement('canvas').getContext('2d'), fs = Math.max(12, Math.round(u * .26)), ls = Math.round(fs * 1.12); m.font = `500 ${fs}px Onest, Arial`;
  const lines = []; let cur = ''; for (const w of String(D.disclaimer).split(/\s+/)) { const t = cur ? cur + ' ' + w : w; if (m.measureText(t).width > W - u * .8 && cur) { lines.push(cur); cur = w; } else cur = t; } if (cur) lines.push(cur);
  const { rows, total } = calc(), mats = list ? rows.map(r => [`${r.name}: ${r.what}${r.m2 ? ', ' + num(r.a) + ' м²' : ', ' + r.pcs + ' шт'}`, r.sum ? fmt(r.sum) + ' ₽' : '']) : [];
  const listH = mats.length ? mats.length * ls * 1.35 + ls * 2 : 0, band = Math.round(u * .62 + listH + lines.length * fs * 1.3 + u * .25);
  const c = document.createElement('canvas'); c.width = W; c.height = H + band; const x = c.getContext('2d');
  x.drawImage(src, 0, 0, W, H); x.fillStyle = '#1c1817'; x.fillRect(0, H, W, band); x.textBaseline = 'middle';
  x.fillStyle = '#fff'; x.font = `700 ${Math.round(u * .34)}px Onest, Arial`; x.fillText(D.brand + ' · ' + D.phone, u * .4, H + u * .36);
  let y = H + u * .62 + ls * .6;
  if (mats.length) {
    x.font = `500 ${ls}px Onest, Arial`;
    for (const [t, p] of mats) { const pw = p ? x.measureText(p).width : 0; let s = t; while (x.measureText(s).width > W - u * 1.2 - pw && s.length > 10) s = s.slice(0, -2); if (s !== t) s = s.trim() + '…';
      x.fillStyle = '#efe7e3'; x.textAlign = 'left'; x.fillText(s, u * .4, y); if (p) { x.textAlign = 'right'; x.fillText(p, W - u * .4, y); } y += ls * 1.35; }
    x.font = `700 ${Math.round(ls * 1.15)}px Onest, Arial`; x.fillStyle = '#fff'; x.textAlign = 'left'; x.fillText('Итого примерно, без доставки', u * .4, y + ls * .2); x.textAlign = 'right'; x.fillText('≈ ' + fmt(Math.round(total / 500) * 500) + ' ₽', W - u * .4, y + ls * .2); x.textAlign = 'left'; y += ls * 1.8;
  }
  x.fillStyle = '#d4cac5'; x.font = m.font; lines.forEach((l, i) => x.fillText(l, u * .4, y + i * fs * 1.3));
  return c;
}
function thumb() { const src = render3(), W = 320, H = Math.round(W * src.height / src.width), c = document.createElement('canvas'); c.width = W; c.height = H; c.getContext('2d').drawImage(src, 0, 0, W, H); return c.toDataURL('image/jpeg', .7); }
function saveVariant() {
  const vars = LS.get('mw3d_vars') || []; if (vars.length >= 4) return;
  const pv = S.zones.filter(q => q.kind === 'pave'), forms = [...new Set(pv.map(q => (FORMS[q.form] || {}).name))], cols = [...new Set(pv.map(q => (D.colors[q.color] || {}).name))];
  const name = pv.length ? `${forms.join(' + ')}, ${cols.slice(0, 2).join(' + ')}${cols.length > 2 ? '…' : ''}` : 'Вариант ' + (vars.length + 1);
  vars.push({ name, total: Math.round(calc().total / 500) * 500, img: thumb(), s: pack(S) });
  if (!LS.set('mw3d_vars', vars)) { hint('Не удалось сохранить: браузер запретил хранение', 3500); return; }
  hint('Вариант сохранён. Меняйте дальше и сравнивайте', 3000); renderPanel();
}
function saveShot() { const c = snapshot(); const a = document.createElement('a'); a.download = 'proekt-monolit-west.jpg'; a.href = c.toDataURL('image/jpeg', .9); a.click(); }
function projectLink() { return D.site.replace(/\/$/, '') + '/3d/#p=' + enc(pack(S)); }
function copyLink() { const l = projectLink(); (navigator.clipboard ? navigator.clipboard.writeText(l) : Promise.reject()).then(() => hint('Ссылка скопирована: её можно отправить родным или менеджеру', 3500)).catch(() => prompt('Ссылка на проект:', l)); }
async function shareProject() {   // на телефоне: системное меню (MAX, WhatsApp, почта) с картинкой и ссылкой
  const l = projectLink(), text = 'Мой проект двора в 3D-примерочной ' + D.brand;
  try {
    const blob = await new Promise(r => snapshot(1280).toBlob(r, 'image/jpeg', .85)), file = new File([blob], 'proekt-monolit-west.jpg', { type: 'image/jpeg' });
    if (navigator.canShare && navigator.canShare({ files: [file] })) { await navigator.share({ files: [file], title: text, text: text + ': ' + l }); return; }
    if (navigator.share) { await navigator.share({ title: text, text, url: l }); return; }
  } catch (e) { if (e && e.name === 'AbortError') return; }
  copyLink();
}
function projectText() {
  const h = S.house, { rows, total } = calc();
  return [`Дом: ${HOUSES[h.type].name}; фасад: ${FACADE_MATS[h.facade.m]} ${nameOf(h.facade.m === 'brick' ? BRICK_COLORS : h.facade.m === 'wood' ? WOOD_COLORS : FACADE_COLORS, h.facade.c)}; крыша: ${ROOF_MATS[h.roof.m]} ${nameOf(RAL, h.roof.c)}`,
    `Забор: ${FENCE_TYPES[S.fence.t]}${S.fence.t !== 'none' ? ' ' + nameOf(FENCE_COLORS, S.fence.c) : ''}; участок ${S.plot.w}×${S.plot.d} м`, '',
    ...rows.map(r => `${r.name}: ${r.what}${r.m2 ? `, ${r.a.toFixed(1)} м² (с запасом ${r.m2.toFixed(1)} м²)` : `, ${r.pcs} шт`}${r.sum ? ', ≈ ' + fmt(r.sum) + ' ₽' : ''}`),
    '', `Итого примерно: ${fmt(total)} ₽ без доставки`].join('\n');
}
function sendProject() {
  const { total } = calc(), area = S.zones.reduce((s, z, i) => s + (areas[i + 1] || 0), 0);
  const shot = snapshot(1280).toDataURL('image/jpeg', .8);
  if (!window.mwLead) { alert('Не удалось открыть форму. Позвоните нам: ' + D.phone); return; }
  window.mwLead({ type: 'project3d', title: 'Отправить проект менеджеру', sub: 'Менеджер посмотрит проект, уточнит размеры и пришлёт точный расчёт с доставкой.',
    product: `Проект из 3D-примерочной (${S.zones.length} ${S.zones.length === 1 ? 'площадка' : S.zones.length < 5 ? 'площадки' : 'площадок'})`, area: area.toFixed(1), total: Math.round(total / 500) * 500, project: projectText(), link: projectLink(), shot });
}

/* ---------- цикл отрисовки ---------- */
let started = false, PRt = { last: 0, sum: 0, n: 0, good: 0, ceil: 9 }, fi = 0, half = false; const VS = [];
function adapt(t) {
  const dt = t - PRt.last; PRt.last = t; if (dt > 200 || dt <= 0 || tween || op || CLOCK < 3000) { PRt.sum = PRt.n = 0; return; }
  PRt.sum += dt; PRt.n++; if (PRt.n < 14) return; const avg = PRt.sum / PRt.n; PRt.sum = PRt.n = 0;
  const v = VS.length ? [...VS].sort((a, b) => a - b)[Math.floor(VS.length * .2)] : 16.7, budget = v * (half ? 2 : 1); let np = PR;
  if (avg > budget * 1.3 && PR > PR_MIN) { PRt.ceil = PR - .05; np = Math.max(PR_MIN, PR - .15); PRt.good = 0; }
  else if (avg < budget * 1.08) { PRt.good++; if (PRt.good % 12 === 0) PRt.ceil = Math.min(PR_MAX, PRt.ceil + .05); if (PRt.good >= 6 && PR + .1 <= Math.min(PR_MAX, PRt.ceil) + 1e-6) { np = PR + .1; PRt.good = 0; } } else PRt.good = 0;
  if (np !== PR) { PR = np; renderer.setPixelRatio(PR); resize(); }
}
renderer.setAnimationLoop(t => {
  const dRaw = lastT ? t - lastT : 0; if (dRaw > 0 && dRaw < 40) { VS.push(dRaw); if (VS.length > 90) VS.shift(); } if (VS.length >= 30 && fi % 30 === 0) half = [...VS].sort((a, b) => a - b)[Math.floor(VS.length * .2)] < 9.5; fi++;
  if (!started) { lastT = t; return; }
  if (half && (fi & 1)) return;
  CLOCK += lastT ? Math.min(50, t - lastT) : 0; lastT = t;
  if (tween) { const k = Math.min(1, Math.max(0, (CLOCK - tween.t0) / 850)), e = k < .5 ? 2 * k * k : 1 - Math.pow(-2 * k + 2, 2) / 2; camera.position.lerpVectors(tween.p0, tween.p1, e); controls.target.lerpVectors(tween.g0, tween.g1, e); if (k >= 1) { tween = null; updateHandles(); } dirty = true; }
  if (controls.update() || dirty) { adapt(t); renderer.render(scene, camera); dirty = false; }
});
controls.addEventListener('change', () => { if (mode === 'plan' && sel) { clearTimeout(controls._h); controls._h = setTimeout(updateHandles, 120); } });
if (location.search.includes('debug')) window.__to = { THREE, scene, camera, controls, renderer, get S() { return S; }, setMode, select, buildZones, calc, zoneMeshes, ZMAT };

/* ---------- старт ---------- */
resize(); rebuildAll(); cam3d(true); renderPanel(); commit();
Promise.race([Promise.all([assetsReady, new Promise(r => setTimeout(r, 300))]), new Promise(r => setTimeout(r, 12000))]).then(() => renderer.compileAsync ? renderer.compileAsync(scene, camera).catch(() => {}) : null).then(() => {
  started = true; redraw(); $('[data-loader]').classList.add('off');
  hint(MOBILE ? 'Крутите участок пальцем. «План»: рисовать и двигать площадки' : 'Крутите участок мышью. «План»: рисовать и двигать площадки, менять размеры', 6000);
});
