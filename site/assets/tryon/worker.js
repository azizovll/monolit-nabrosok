/* фоновая генерация плитки; модуль paving.js подключается по версионированному адресу из ?p= */
const queue = [];
let build = null;
onmessage = e => { if (build) run(e.data); else queue.push(e.data); };
function run(d) {
  const r = build(d);
  postMessage({ id: d.id, W: r.W, H: r.H, c: r.c, n: r.n, r: r.r }, [r.c.buffer, r.n.buffer, r.r.buffer]);
}
import(new URL(location.href).searchParams.get('p')).then(m => { build = m.build; queue.splice(0).forEach(run); });
