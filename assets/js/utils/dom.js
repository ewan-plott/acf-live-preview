export const $  = (sel, ctx = document) => ctx.querySelector(sel);
export const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

export const on = (target, type, handler, opts) => target.addEventListener(type, handler, opts);

// Tiny event delegation helper
export const delegate = (root, type, selector, handler, opts) => {
    const listener = (e) => {
        const el = e.target.closest(selector);
        if (el && root.contains(el)) handler(e, el);
    };
    root.addEventListener(type, listener, opts);
    return () => root.removeEventListener(type, listener, opts);
};
