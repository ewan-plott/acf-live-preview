// assets/js/core/acf-data.js
import { $$ } from '../utils/dom.js';

export function collectRowData(row) {
  const layout = row.getAttribute('data-layout') || row.dataset.layout || '';
  const fields = {};
  const types  = {}; // <-- add this

  $$('.acf-field', row).forEach((wrap) => {
    let name = wrap.getAttribute('data-name') || wrap.dataset.name || '';
    const fallbackKey = wrap.getAttribute('data-key') || '';
    if (!name && fallbackKey) {
      name = fallbackKey;
      console.warn('[ACF LP] using data-key instead of data-name:', name);
    }
    if (!name) return;

    const ftype = wrap.getAttribute('data-type') || wrap.dataset.type || '';
    const ctrl =
      wrap.querySelector('textarea.wp-editor-area') ||
      wrap.querySelector('input, textarea, select');
    if (!ctrl) return;

    let val = '';
    if (ftype === 'link') {
      const title  = wrap.querySelector('input.input-title')?.value || '';
      const url    = wrap.querySelector('input.input-url')?.value || '';
      const target = wrap.querySelector('input.input-target')?.value || '';
      val = { title, url, target };
    } else if (ftype === 'wysiwyg') {
      val = ctrl.value || '';
    } else if (ftype === 'image' || ftype === 'file') {
      val = parseInt(ctrl.value, 10) || null; // ID
    } else if (ftype === 'gallery') {
      const hidden = wrap.querySelector('input[type="hidden"]');
      if (hidden?.value) {
        try {
          const parsed = JSON.parse(hidden.value);
          val = Array.isArray(parsed) ? parsed.map(v => parseInt(v, 10)).filter(Boolean) : [];
        } catch {
          val = hidden.value.split(',').map(v => parseInt(v, 10)).filter(Boolean);
        }
      } else {
        val = Array.from(wrap.querySelectorAll('input[type="hidden"][name^="acf["]'))
          .map(i => parseInt(i.value, 10)).filter(Boolean);
      }
    } else if (ftype === 'repeater') {  
      
    } else if (ctrl.type === 'checkbox') {
      val = Array.from(wrap.querySelectorAll('input[type="checkbox"]:checked')).map(i => i.value);
    } else if (ctrl.type === 'radio') {
      const checked = wrap.querySelector('input[type="radio"]:checked');
      val = checked ? checked.value : '';
    } else if (ctrl.tagName === 'SELECT' && ctrl.multiple) {
      val = Array.from(ctrl.selectedOptions).map(o => o.value);
    } else {
      val = ctrl.value ?? '';
    }

    console.log('[ACF LP] field:', { name, type: ftype, value: val });
    fields[name] = val;
    if (ftype) types[name] = ftype; // <-- record the type
  });

  console.log('[ACF LP] row collected:', { layout, fields, types });
  return { layout, fields, types }; // <-- include types
}
