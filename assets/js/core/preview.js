import { $ } from '../utils/dom.js';
import { ensureModal } from './modal.js';
import { initFrame, ensureAssetsInDoc } from './frame.js';
import { collectRowData } from './acf-data.js';
import { dlog, isDebug, pretty } from '../utils/dev.js';

export async function previewRow(row) {
  const modal = ensureModal();
  const frame = $('#acflp-frame', modal);
  const doc = initFrame(frame);

  const label  = row.getAttribute('data-label') || '';
  const layout = row.getAttribute('data-layout') || '';
  $('#acflp-modal__title').textContent = `${label || layout}`;
  modal.classList.add('-open');

  // handy refs for debug panel
  const debugBtn = $('#acflp-modal__debug-toggle', modal);
  const debugBox = $('#acflp-debug', modal);
  const showDebug = (text) => {
    if (!text) return;
    debugBtn.style.display = 'inline-block';
    debugBox.textContent = text;
  };

  // Load global bundles
  if (window.ACF_LIVE_PREVIEW?.global_assets) {
    ensureAssetsInDoc(doc, window.ACF_LIVE_PREVIEW.global_assets, frame);
  }

  const payload = [collectRowData(row)];
  dlog('Payload:', payload);

  try {
    const body = new URLSearchParams({
      action: 'acf_live_preview_render',
      nonce:  window.ACF_LIVE_PREVIEW.nonce,
      post_id: String(window.ACF_LIVE_PREVIEW.post_id || 0),
      flex: JSON.stringify(payload),
      _acflp_debug: isDebug() ? '1' : '0', // optional: server can use to add logs
    });

    dlog('POST', window.ACF_LIVE_PREVIEW.ajax_url, body.toString());

    const res = await fetch(window.ACF_LIVE_PREVIEW.ajax_url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' },
      body,
    });

    const contentType = res.headers.get('content-type') || '';
    const statusLine = `HTTP ${res.status} ${res.statusText}`;
    dlog('Response status:', statusLine, 'Content-Type:', contentType);

    // If it's not JSON, show raw text (likely a PHP warning/notice or HTML error)
    if (!contentType.includes('application/json')) {
      const raw = await res.text();
      dlog('Non-JSON response:', raw);
      doc.body.innerHTML = '<strong>Preview error (non-JSON response).</strong>';
      showDebug(`${statusLine}\n\n${raw}`);
      return;
    }

    // Parse JSON safely
    let json;
    try {
      json = await res.json();
    } catch (e) {
      const raw = await res.text().catch(() => '');
      dlog('JSON parse failed. Raw:', raw);
      doc.body.innerHTML = '<strong>Preview error (invalid JSON).</strong>';
      showDebug(`${statusLine}\n\nJSON parse failed.\n\nRaw:\n${raw}`);
      return;
    }

    dlog('JSON:', json);

    if (json?.success) {
      // Load per-layout assets then inject HTML
      ensureAssetsInDoc(doc, json.data?.assets, frame);
      const html = json.data?.html || '';
      doc.body.innerHTML = html || '<em>No output.</em>';

      // Strip AOS attributes/classes to avoid any leftover styles
      try {
        doc.querySelectorAll('[data-aos]').forEach(el => el.removeAttribute('data-aos'));
        doc.querySelectorAll('.aos-init, .aos-animate').forEach(el => {
          el.classList.remove('aos-init');
          el.classList.remove('aos-animate');
          el.style.opacity = '';
          el.style.transform = '';
          el.style.transition = '';
        });
      } catch (e) {
        // no-op
      }


      // In debug, dump a compact summary
      if (isDebug()) {
        const summary = {
          request: {
            url: window.ACF_LIVE_PREVIEW.ajax_url,
            body: Object.fromEntries(body.entries()),
          },
          response: {
            status: res.status,
            assets: json.data?.assets || {},
            htmlLength: (html || '').length,
          },
        };
        showDebug(pretty(summary));
      }
    } else {
      const msg = json?.data?.message || json?.message || 'Unknown error.';
      doc.body.innerHTML = '<strong>Preview error.</strong>';
      showDebug(pretty({ status: statusLine, json, note: 'json.success is false', message: msg }));
    }
  } catch (err) {
    console.error(err);
    doc.body.innerHTML = '<strong>Preview failed.</strong>';
    showDebug(pretty({ error: String(err) }));
  }
}
