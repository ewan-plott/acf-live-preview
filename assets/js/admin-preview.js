// assets/js/admin-preview.js
import { mountTooltipPreview } from './core/tooltip.js';
import { previewRow } from './core/preview.js';
import { debounce } from './utils/debounce.js';

const rebind = debounce(() => {}, 50);

mountTooltipPreview({ onPreview: previewRow });

if (window.acf?.addAction) {
  window.acf.addAction('ready', rebind);
  window.acf.addAction('append', rebind);
  window.acf.addAction('remove', rebind);
  window.acf.addAction('sortstop', rebind);
}