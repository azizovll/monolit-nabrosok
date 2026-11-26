/* Админка: строки характеристик, подтверждения, защита от потери несохранённых правок */
(function () {
  document.addEventListener('click', function (e) {
    var add = e.target.closest('[data-add-row]');
    if (add) {
      var box = add.previousElementSibling.matches('[data-specs]') ? add.previousElementSibling : add.parentNode.querySelector('[data-specs]');
      var last = box.lastElementChild, row = last.cloneNode(true);
      row.querySelectorAll('input').forEach(function (i) { i.value = ''; });
      box.appendChild(row); row.querySelector('input').focus();
      return;
    }
    var del = e.target.closest('[data-del-row]');
    if (del) {
      var r = del.closest('.spec-row'), b = r.parentNode;
      if (b.children.length > 1) r.remove(); else r.querySelectorAll('input').forEach(function (i) { i.value = ''; });
      markDirty(b.closest('form'));
    }
  });

  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) { if (!confirm(f.getAttribute('data-confirm'))) e.preventDefault(); });
  });

  var dirty = new Set();
  function markDirty(f) { if (f && f.hasAttribute('data-dirty-guard')) dirty.add(f); }
  document.querySelectorAll('form[data-dirty-guard]').forEach(function (f) {
    f.addEventListener('input', function () { markDirty(f); });
    f.addEventListener('change', function () { markDirty(f); });
    f.addEventListener('submit', function () { dirty.delete(f); });
  });
  window.addEventListener('beforeunload', function (e) {
    if (dirty.size) { e.preventDefault(); e.returnValue = ''; }
  });

  // подсветка изменённых цен
  document.querySelectorAll('input.price').forEach(function (i) {
    var v0 = i.value;
    i.addEventListener('input', function () { i.style.borderColor = i.value !== v0 ? '#b5701b' : ''; i.style.background = i.value !== v0 ? '#fff6e8' : ''; });
  });
})();
