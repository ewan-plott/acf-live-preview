// assets/js/core/tooltip.js
import { $, $$, on } from '../utils/dom.js';
import { dlog } from '../utils/dev.js';

const MORE_BTN_SELECTORS = [
  'a.acf-js-tooltip[data-name="more-layout-actions"]',
  'button.acf-js-tooltip[data-name="more-layout-actions"]',
  '.acf-actions [data-name="more-layout-actions"]',
  '.acf-icon.-more', // ACF sometimes uses icon buttons
  '[data-event="more-layout-actions"]',
].join(',');

const TOOLTIP_ROOT_SELECTORS = [
  '.acf-tooltip',              // classic ACF tooltip
  '.acf-fc-popup',             // flexible content popup
  '.acf-more-layout-actions',  // actions container
].join(',');

export function mountTooltipPreview({ onPreview }) {
  let lastRow = null;

  // Remember which row the user targeted when opening the tooltip
  const rememberRow = (e) => {
    const btn = e.target.closest(MORE_BTN_SELECTORS);
    if (!btn) return;

    const row = btn.closest('.layout, .acf-field-flexible-content .layout');
    if (row) {
      lastRow = row;
      dlog('rememberRow: found row', row);
      // Try both: observe + quick poll to catch reused tooltip elements
      setTimeout(tryInjectIntoLatestTooltip, 50);
    } else {
      dlog('rememberRow: no row found from button', btn);
    }
  };

  // Capture phase helps when ACF stops propagation
  document.addEventListener('mousedown', rememberRow, true);
  document.addEventListener('click', rememberRow, true);
  document.addEventListener('pointerdown', rememberRow, true);

  // Watch for tooltip nodes being added
  const tipObserver = new MutationObserver((muts) => {
    muts.forEach((m) => {
      m.addedNodes.forEach((node) => {
        if (!(node instanceof HTMLElement)) return;
        if (!node.matches?.(TOOLTIP_ROOT_SELECTORS) && !node.querySelector?.(TOOLTIP_ROOT_SELECTORS)) return;

        const tipEl = node.matches?.(TOOLTIP_ROOT_SELECTORS)
          ? node
          : node.querySelector(TOOLTIP_ROOT_SELECTORS);

        if (tipEl) {
          dlog('Observer saw tooltip node', tipEl);
          injectIntoTooltip(tipEl);
        }
      });
    });
  });
  tipObserver.observe(document.body, { childList: true, subtree: true });

  // Poll briefly—ACF sometimes reuses/hides the same tooltip element
  function tryInjectIntoLatestTooltip() {
    let tries = 0;
    const timer = setInterval(() => {
      // last visible tooltip
      const tips = $$(TOOLTIP_ROOT_SELECTORS).filter(el => el.offsetParent !== null);
      const tip = tips[tips.length - 1];
      if (tip) {
        clearInterval(timer);
        dlog('Polling found visible tooltip', tip);
        injectIntoTooltip(tip);
      } else if (++tries > 40) { // ~1s
        clearInterval(timer);
        dlog('Polling gave up (no visible tooltip)');
      }
    }, 25);
  }

  function injectIntoTooltip(tipEl) {
    // run once per "show"
    if (tipEl.__acflpInjected) {
      dlog('Already injected into tooltip; skipping');
      return;
    }

    // find an inner container or a UL; fallback to the tooltip itself
    const container =
      tipEl.querySelector('.acf-fc-popup, .acf-more-layout-actions, .acf-tooltip-inner, .inner, ul') ||
      tipEl;

    // avoid duplicates
    if (container.querySelector('.acflp-preview-item')) {
      dlog('Preview item already present; skipping');
      return;
    }

    const link = document.createElement('a');
    link.href = '#';
    link.className = 'acflp-preview-item';
    link.textContent = 'Preview layout';
    link.addEventListener('click', (ev) => {
      ev.preventDefault();
      ev.stopPropagation();
      if (!lastRow) {
        dlog('Clicked preview but no lastRow recorded');
      } else {
        dlog('Launching preview for row', lastRow);
        onPreview(lastRow);
      }
      // Close tooltip if possible
      try { tipEl.remove(); } catch {}
    });

    const ul = container.tagName === 'UL' ? container : container.querySelector('ul');
    if (ul) {
      const li = document.createElement('li');
      li.appendChild(link);
      ul.appendChild(li);
      dlog('Injected preview item into UL');
    } else {
      link.style.display = 'block';
      link.style.padding = '6px 10px';
      container.appendChild(link);
      dlog('Injected preview item into flat container');
    }

    tipEl.__acflpInjected = true;
  }

  // Keep a light hook for dynamic UI changes
  if (window.acf?.addAction) {
    window.acf.addAction('ready', () => dlog('acf:ready'));
    window.acf.addAction('append', () => dlog('acf:append'));
    window.acf.addAction('remove', () => dlog('acf:remove'));
    window.acf.addAction('sortstop', () => dlog('acf:sortstop'));
  }

  dlog('Tooltip module mounted');
}
