(() => {
  const box = document.getElementById('acf-live-preview__content');
  if (!box) return;

  // quick debounce to avoid spamming
  const debounce = (fn, wait = 250) => {
    let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), wait); };
  };

  // collect a super-simple snapshot of flexible content rows
  function collectFlexibleContent() {
    const snapshot = [];
    const flexFields = document.querySelectorAll('.acf-field[data-type="flexible_content"]');

    flexFields.forEach(flex => {
      const rows = flex.querySelectorAll(':scope > .acf-input .acf-flexible-content .values > .layout');
      rows.forEach(row => {
        const layout = row.getAttribute('data-layout') || row.dataset.layout || '';
        const fields = {};
        // grab a few common control values (extend later)
        row.querySelectorAll('.acf-field').forEach(wrap => {
          const name = wrap.dataset.name || wrap.getAttribute('data-name') || wrap.getAttribute('data-key');
          if (!name) return;
          const ctrl = wrap.querySelector('input, textarea, select');
          if (!ctrl) return;

          let val = '';
          if (ctrl.type === 'checkbox') {
            val = Array.from(wrap.querySelectorAll('input[type="checkbox"]:checked')).map(i => i.value);
          } else if (ctrl.type === 'radio') {
            const checked = wrap.querySelector('input[type="radio"]:checked');
            val = checked ? checked.value : '';
          } else if (ctrl.tagName === 'SELECT' && ctrl.multiple) {
            val = Array.from(ctrl.selectedOptions).map(o => o.value);
          } else {
            val = ctrl.value ?? '';
          }
          fields[name] = val;
        });

        snapshot.push({ layout, fields });
      });
    });

    return snapshot;
  }

  function paintDebug() {
    const snap = collectFlexibleContent();
    // tiny visual to prove wiring works
    box.innerHTML = `
      <div style="font:12px/1.5 system-ui, sans-serif">
        <div style="margin-bottom:.5rem"><strong>Live Preview (debug mode)</strong></div>
        <div>Flexible rows: <strong>${snap.length}</strong></div>
        <pre style="margin-top:.5rem; white-space:pre-wrap; word-break:break-word; background:#f6f8fa; padding:.5rem; border-radius:6px; border:1px solid #e2e8f0; max-height:240px; overflow:auto;">${JSON.stringify(snap, null, 2)}</pre>
      </div>
    `;
    // also log for devtools
    if (window.console) console.log('[ACF Live Preview] snapshot', snap);
  }

  const paintDebugDebounced = debounce(paintDebug, 250);

  // global listeners (classic editor)
  document.addEventListener('input', paintDebugDebounced, true);
  document.addEventListener('change', paintDebugDebounced, true);
  document.addEventListener('blur', paintDebugDebounced, true);

  // ACF lifecycle hooks (if available)
  if (window.acf && typeof acf.addAction === 'function') {
    acf.addAction('ready', paintDebugDebounced);
    acf.addAction('append', paintDebugDebounced);
    acf.addAction('remove', paintDebugDebounced);
    acf.addAction('sortstop', paintDebugDebounced);
    acf.addAction('ready_field', paintDebugDebounced);
  }

  // initial render
  paintDebugDebounced();
})();
