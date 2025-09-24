(() => {
  const debounce = (fn, wait = 250) => { let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), wait); }; };

  function ensureFrame() {
    const host = document.getElementById('acf-live-preview__content');
    if (!host) return null;
    let frame = host.querySelector('iframe#acflp-frame');
    if (!frame) {
      frame = document.createElement('iframe');
      frame.id = 'acflp-frame';
      frame.style.width = '100%';
      frame.style.border = 0;
      host.innerHTML = '';
      host.appendChild(frame);
      const doc = frame.contentDocument;
      doc.open(); doc.write(`<!doctype html><html><head><meta charset="utf-8">
        <style>
          html,body{margin:0;padding:12px;background:#fff;font:12px/1.5 system-ui,sans-serif;color:#111}
          pre{background:#f6f8fa;padding:.5rem;border:none;border-radius:6px;white-space:pre-wrap;word-break:break-word;max-height:60vh;overflow:auto}
        </style>
      </head><body></body></html>`); doc.close();
    }
    return frame;
  }

  function collectFlexibleContent() {
    const out = [];
    document.querySelectorAll('.acf-field[data-type="flexible_content"]').forEach(flex => {
      flex.querySelectorAll(':scope > .acf-input .acf-flexible-content .values > .layout').forEach(row => {
        const layout = row.getAttribute('data-layout') || row.dataset.layout || '';
        const fields = {};
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
        out.push({ layout, fields });
      });
    });
    return out;
  }

  function paint() {
    const frame = ensureFrame(); if (!frame) return;
    const doc = frame.contentDocument;
    const snap = collectFlexibleContent();
    doc.body.innerHTML = `
      <div>Flexible rows: <strong>${snap.length}</strong></div>
      <pre>${JSON.stringify(snap, null, 2)}</pre>
    `;
    frame.style.height = Math.max(200, doc.body.scrollHeight) + 'px';
  }

  const run = debounce(paint, 250);

  document.addEventListener('input',  run, true);
  document.addEventListener('change', run, true);
  document.addEventListener('blur',   run, true);
  if (window.acf && typeof acf.addAction === 'function') {
    acf.addAction('ready', run);
    acf.addAction('append', run);
    acf.addAction('remove', run);
    acf.addAction('sortstop', run);
    acf.addAction('ready_field', run);
  }
  run();
})();
