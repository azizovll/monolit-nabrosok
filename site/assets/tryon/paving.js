/* Генерация текстур плитки: раскладка камней и карты цвет/рельеф/шероховатость.
   Используется в фоновом потоке (worker.js) и в главном (превью). Без three.js. */

export function rng(seed) { let a = seed >>> 0; return () => { a = a + 0x6D2B79F5 | 0; let t = Math.imul(a ^ a >>> 15, 1 | a); t = t + Math.imul(t ^ t >>> 7, 61 | t) ^ t; return ((t ^ t >>> 14) >>> 0) / 4294967296; }; }
export function pick(pal, r) { let tot = 0; for (const p of pal) tot += p[1]; let v = r() * tot; for (const p of pal) { v -= p[1]; if (v <= 0) return p[0]; } return pal[0][0]; }
export const hexRgb = h => { const n = parseInt(h.slice(1), 16); return [n >> 16, n >> 8 & 255, n & 255]; };
const clamp = (v, a, b) => v < a ? a : v > b ? b : v;

const VN = (() => { const r = rng(99), g = new Float32Array(64 * 64); for (let i = 0; i < g.length; i++) g[i] = r(); return g; })();
export function vn(x, y) {
  const xi = Math.floor(x), yi = Math.floor(y), fx = x - xi, fy = y - yi, sx = fx * fx * (3 - 2 * fx), sy = fy * fy * (3 - 2 * fy);
  const i0 = xi & 63, i1 = (xi + 1) & 63, j0 = yi & 63, j1 = (yi + 1) & 63;
  const a = VN[j0 * 64 + i0], b = VN[j0 * 64 + i1], c = VN[j1 * 64 + i0], d = VN[j1 * 64 + i1];
  return a + (b - a) * sx + (c - a) * sy + (a - b - c + d) * sx * sy;
}
const HT = (() => { const r = rng(4242), t = new Float32Array(65536); for (let i = 0; i < t.length; i++) t[i] = r(); return t; })();
const hz = (x, y) => HT[((x * 1973) ^ (y * 9277) ^ (x * y * 26699)) & 65535];

/* Геометрия форм в «единицах» u (метры). Камень k×1 единиц, период N единиц (N*u = период текстуры, м).
   Наборы из разных камней (Новый и Старый город) кладутся рядами высотой h со случайной длиной камней. */
export const FORMS = {
  'kirpich':      { u: .10, N: 40, k: 2, patterns: ['perevyazka', 'elka', 'elka45', 'pletenka', 'line'] },
  'parket':       { u: .08, N: 48, k: 3, patterns: ['elka', 'elka45', 'perevyazka', 'pletenka'] },
  'novyj-gorod':  { u: .08, N: 50, mix: { h: 2, w: [3, 3, 2, 2, 1] }, patterns: ['haos', 'haos90'] },
  'staryj-gorod': { u: .066, N: 60, mix: { h: 2, w: [3, 3, 2, 2, 1] }, patterns: ['haos', 'haos90'] },
};
export const PATTERN_NAMES = { perevyazka: 'Вперевязку', elka: 'Ёлочка', elka45: 'Ёлочка 45°', pletenka: 'Плетёнка', line: 'В линию', haos: 'Хаотично', haos90: 'Хаотично поперёк' };
export const PATTERN_ROT = { elka45: Math.PI / 4, haos90: Math.PI / 2 };
export function formSpec(slug) { return FORMS[slug] || FORMS.kirpich; }
export function period(slug) { const f = formSpec(slug); return f.N * f.u; }

export function layout(slug, pat) {
  const f = formSpec(slug), N = f.N, r = [];
  if (pat === 'soldier') { for (let x = 0; x < 40; x++) r.push([x, 0, 1, 2]); return r; }
  if (f.mix) {
    const R = rng(N * 13 + slug.length), h = f.mix.h, ws = f.mix.w;
    for (let y = 0; y < N; y += h) {
      let x = Math.floor(R() * 3), rest = N;
      while (rest > 0) { let w = ws[Math.floor(R() * ws.length)]; if (w > rest) w = rest; r.push([x % N, y, w, h]); x += w; rest -= w; }
    }
    return r;
  }
  const k = f.k;
  if (pat === 'line') { for (let y = 0; y < N; y++) for (let i = 0; i < N / k; i++) r.push([i * k, y, k, 1]); }
  else if (pat === 'perevyazka') { const o = Math.floor(k / 2) || 1; for (let y = 0; y < N; y++) for (let i = 0; i < N / k; i++) r.push([i * k + (y % 2) * o, y, k, 1]); }
  else if (pat === 'pletenka') {
    for (let by = 0; by < N / k; by++) for (let bx = 0; bx < N / k; bx++) {
      const x = bx * k, y = by * k;
      for (let t = 0; t < k; t++) r.push((bx + by) % 2 === 0 ? [x, y + t, k, 1] : [x + t, y, 1, k]);
    }
  } else { // ёлочка для камня k×1: решётка (1,1) и (k,-k), период кратен 2k
    for (let i = 0; i < N; i++) for (let j = 0; j < N / (2 * k); j++) {
      const ox = (i + k * j) % N, oy = ((i - k * j) % N + N) % N;
      r.push([ox, oy, k, 1], [ox, oy + 1, 1, k]);
    }
  }
  return r;
}

/* два цвета в одной площадке: микс (камни вперемешку), полосы (каждый P-й ряд), шахматка (клетки около 0,6 м) */
export const MIX_NAMES = { mix: 'Микс', stripes: 'Полосы', chess: 'Шахматка' };
function rowH(slug) { const f = formSpec(slug); return f.mix ? f.mix.h : 1; }
function stripeP(slug) { const f = formSpec(slug), rows = f.N / rowH(slug); return rows % 5 === 0 ? 5 : 4; }
function chessC(slug) { const f = formSpec(slug); let best = f.N / 2, bd = 9; for (let c = 2; c <= f.N / 2; c++) if (f.N % c === 0 && (f.N / c) % 2 === 0 && Math.abs(c * f.u - .6) < bd) { bd = Math.abs(c * f.u - .6); best = c; } return best; }
/* доля второго цвета: для сметы */
export function mixShare(slug, mode) { return mode === 'stripes' ? 1 / stripeP(slug) : mode === 'mix' || mode === 'chess' ? .5 : 0; }
function chooser(slug, mode, N) {
  if (mode === 'stripes') { const h = rowH(slug), P = stripeP(slug); return (rx, ry, rw, rh) => Math.floor(((ry + rh / 2) % N) / h) % P === P - 1; }
  if (mode === 'chess') { const C = chessC(slug); return (rx, ry, rw, rh) => (Math.floor(((rx + rw / 2) % N) / C) + Math.floor(((ry + rh / 2) % N) / C)) % 2 === 1; }
  return (rx, ry, rw, rh, r) => r() < .5;
}

/* rects в единицах; s пикселей на единицу; u размер единицы в метрах (для шва и фаски в мм) */
export function paint(rects, N, s, u, pal, seed, NY, pal2, second) {
  const W = N * s, H = (NY || N) * s, NP = W * H, r = rng(seed), ny = NY || N;
  const col = new Uint8ClampedArray(NP * 4), hgt = new Float32Array(NP), rgh = new Uint8ClampedArray(NP * 4);
  for (let i = 0; i < NP; i++) { const n = HT[(i * 7919) & 65535]; hgt[i] = .1 + .025 * n; const c = 112 + n * 34; col[i * 4] = c + 10; col[i * 4 + 1] = c + 2; col[i * 4 + 2] = c - 14; col[i * 4 + 3] = 255; rgh[i * 4] = rgh[i * 4 + 1] = rgh[i * 4 + 2] = 250; rgh[i * 4 + 3] = 255; }
  const pxm = s / u, j = Math.max(1.2, .0045 * pxm), bv = Math.max(1.6, .012 * pxm);
  for (const [rx, ry, rw, rh] of rects) {
    const P = pal2 && second(rx, ry, rw, rh, r) ? pal2 : pal;
    const A = hexRgb(pick(P, r)), B = hexRgb(pick(P, r)), k = (r() - .5) * .11, hOff = (r() - .5) * .07, tX = (r() - .5) * .05, tY = (r() - .5) * .05, n1 = r() * 64, n2 = r() * 64, rough = .82 + r() * .12, mix = .55 + r() * .35;
    for (const ox of [-N, 0, N]) for (const oy of [-ny, 0, ny]) {
      const X0 = (rx + ox) * s + j / 2, Y0 = (ry + oy) * s + j / 2, w = rw * s - j, h = rh * s - j;
      const xa = Math.max(0, Math.floor(X0)), xb = Math.min(W, Math.ceil(X0 + w)), ya = Math.max(0, Math.floor(Y0)), yb = Math.min(H, Math.ceil(Y0 + h));
      if (xa >= xb || ya >= yb) continue;
      for (let py = ya; py < yb; py++) { const ly = py + .5 - Y0; if (ly < 0 || ly > h) continue;
        for (let px = xa; px < xb; px++) { const lx = px + .5 - X0; if (lx < 0 || lx > w) continue;
          const d = Math.min(lx, w - lx, ly, h - ly), e = Math.min(1, d / bv), prof = Math.sin(e * Math.PI / 2), i = py * W + px;
          hgt[i] = .55 + .45 * prof + hOff + tX * (lx / w - .5) + tY * (ly / h - .5);
          const m = vn(lx / pxm * 14 + n1, ly / pxm * 14 + n2), t = clamp((m - .3) * 1.7, 0, 1) * mix, g = (hz(px, py) - .5) * .03 + (hz(px >> 2, py >> 2) - .5) * .035;
          const K = 1 + k + g - (1 - prof) * .1;
          col[i * 4] = (A[0] + (B[0] - A[0]) * t) * K; col[i * 4 + 1] = (A[1] + (B[1] - A[1]) * t) * K; col[i * 4 + 2] = (A[2] + (B[2] - A[2]) * t) * K;
          const rv = (rough + (1 - prof) * .1 + g * .6) * 255; rgh[i * 4] = rgh[i * 4 + 1] = rgh[i * 4 + 2] = rv;
        }
      }
    }
  }
  const nrm = new Uint8ClampedArray(NP * 4), sc = bv * 1.15;
  for (let y = 0; y < H; y++) { const yu = ((y - 1 + H) % H) * W, yd = ((y + 1) % H) * W, yr = y * W;
    for (let x = 0; x < W; x++) { const xl = (x - 1 + W) % W, xr = (x + 1) % W;
      const dx = (hgt[yr + xr] - hgt[yr + xl]) * .5 * sc, dy = (hgt[yd + x] - hgt[yu + x]) * .5 * sc;
      const l = Math.hypot(dx, dy, 1), i = (yr + x) * 4;
      nrm[i] = (-dx / l * .5 + .5) * 255; nrm[i + 1] = (dy / l * .5 + .5) * 255; nrm[i + 2] = (1 / l * .5 + .5) * 255; nrm[i + 3] = 255; } }
  const flip = a => { const o = new Uint8ClampedArray(a.length), row = W * 4; for (let y = 0; y < H; y++) o.set(a.subarray(y * row, (y + 1) * row), (H - 1 - y) * row); return o; };
  return { W, H, c: flip(col), n: flip(nrm), r: flip(rgh) };
}

/* одна задача целиком: форма, рисунок, палитра → карты */
export function build(job) {
  if (job.pat === 'soldier') return paint(layout('kirpich', 'soldier'), 40, job.s, .1, job.pal, job.seed, 2);
  const f = formSpec(job.slug);
  const pat = job.pat === 'elka45' ? 'elka' : job.pat === 'haos90' ? 'haos' : job.pat;
  const pal2 = job.pal2 && job.pal2.length ? job.pal2 : null;
  return paint(layout(job.slug, pat), f.N, Math.max(8, Math.round(job.px / f.N)), f.u, job.pal, job.seed, null, pal2, pal2 ? chooser(job.slug, job.mix || 'mix', f.N) : null);
}
