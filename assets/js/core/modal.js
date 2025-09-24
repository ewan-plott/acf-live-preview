import { $, on } from '../utils/dom.js';

export function ensureModal() {
  let m = $('#acflp-modal');
  if (m) return m;

  m = document.createElement('div');
  m.id = 'acflp-modal';
  m.innerHTML = `
    <div id="acflp-modal__dialog">
      <div id="acflp-modal__bar">
        <div id="acflp-modal__title">ACF Live Preview</div>
        <div>
          <button id="acflp-modal__debug-toggle" class="button button-small" type="button" style="display:none">Show debug</button>
          <button id="acflp-modal__close" class="button button-small" type="button" type="button">Close</button>
        </div>
      </div>
      <div id="acflp-modal__body">
      <pre id="acflp-debug" style="display:none;margin:0;padding:8px;border:none;max-height:220px;overflow:auto;background:#0b1020;color:#d1e4ff;"></pre>
      <iframe id="acflp-frame" title="ACF Live Preview"></iframe>
      </div>
    </div>`;

  document.body.appendChild(m);

  const close = () => m.classList.remove('-open');
  on($('#acflp-modal__close', m), 'click', close);
  on(m, 'click', (e) => { if (e.target === m) close(); });
  on(document, 'keydown', (e) => { if (e.key === 'Escape') close(); });

  // Toggle debug section
  const debugBtn = $('#acflp-modal__debug-toggle', m);
  const debugBox = $('#acflp-debug', m);

  on(debugBtn, 'click', () => {
    const vis = debugBox.style.display !== 'none';
    debugBox.style.display = vis ? 'none' : 'block';
    debugBtn.textContent = vis ? 'Show debug' : 'Hide debug';
  });

  return m;
}
