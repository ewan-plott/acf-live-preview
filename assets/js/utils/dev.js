export const isDebug = () => !!window.ACF_LIVE_PREVIEW?.debug;

export function dlog(...args) {
    if (isDebug()) console.log('[ACF LP]', ...args);
}

// Quick helper to stringify safely for display
export function pretty(obj) {
    try { return JSON.stringify(obj, null, 2); }
    catch { return String(obj); }
}
