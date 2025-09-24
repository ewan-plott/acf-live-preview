// assets/js/core/frame.js
export function initFrame(frame) {
  const doc = frame.contentDocument;
  frame.__acflpLoaded = { css: new Set(), js: new Set() };

  doc.open();
  doc.write(`<!doctype html><html><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  html,body{margin:0;padding:0;background:#fff;font:14px/1.5 system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,sans-serif;color:#111}
  .preview-block{border:1px solid #e2e8f0;border-radius:8px;padding:12px;margin-bottom:12px;background:#fff}
  img,video,iframe{max-width:100%;height:auto;display:block}
  /* AOS neutralizer */
  [data-aos], .aos-init, .aos-animate { opacity: 1 !important; transform: none !important; transition: none !important; }
</style>
<script>
  // Flag + AOS stub (prevents init even if the library loads)
  window.__ACF_LP_PREVIEW__ = true;
  (function(){
    var _AOS;
    Object.defineProperty(window, 'AOS', {
      configurable: true,
      get: function(){
        return _AOS || { init:function(){}, refresh:function(){}, refreshHard:function(){}, on:function(){} };
      },
      set: function(v){
        try {
          if (v && typeof v.init === 'function') {
            v.init = function(){ /* no-op in preview */ };
            if (typeof v.refresh === 'function') v.refresh = function(){};
            if (typeof v.refreshHard === 'function') v.refreshHard = function(){};
          }
        } catch(e) {}
        _AOS = v;
      }
    });
  })();
</script>
</head><body><em style="color:#64748b">Loading…</em></body></html>`);
  doc.close();
  return doc;
}


export function ensureAssetsInDoc(doc, assets, frame) {
  if (!assets) return;
  const loaded = frame.__acflpLoaded || (frame.__acflpLoaded = { css: new Set(), js: new Set() });

  (assets.css || []).forEach((url) => {
    if (loaded.css.has(url)) return;
    const link = doc.createElement('link');
    link.rel = 'stylesheet';
    link.href = url;
    doc.head.appendChild(link);
    loaded.css.add(url);
  });

  (assets.js || []).forEach((url) => {
    if (loaded.js.has(url)) return;
    const s = doc.createElement('script');
    s.src = url;
    s.defer = true;
    doc.body.appendChild(s);
    loaded.js.add(url);
  });
}
