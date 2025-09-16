(() => {
  const box = document.getElementById('acf-live-preview__content');
  if (!box) return;

  const debounce = (fn, wait = 250) => { let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), wait); }; };

  function collectFlexibleContent() {
    const result = [];
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
        result.push({ layout, fields });
      });
    });
    return result;
  }

  // TODO (Step 4): replace with AJAX render to PHP endpoint
  function renderPreview() {
    const snap = collectFlexibleContent();
    box.innerHTML = `<div style="font:12px/1.5 system-ui,sans-serif;color:#64748b"><em>Rendering… (${snap.length} rows)</em></div>`;
    // In Step 4, POST snap to ACF_LIVE_PREVIEW.ajax_url and replace box.innerHTML with the returned HTML
  }

  const render = debounce(renderPreview, 250);

  document.addEventListener('input',  render, true);
  document.addEventListener('change', render, true);
  document.addEventListener('blur',   render, true);

  if (window.acf && typeof acf.addAction === 'function') {
    acf.addAction('ready', render);
    acf.addAction('append', render);
    acf.addAction('remove', render);
    acf.addAction('sortstop', render);
    acf.addAction('ready_field', render);
  }

  render();
})();
