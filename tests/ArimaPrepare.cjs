const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { test } = require('node:test');
const vm = require('node:vm');

const source = readFileSync('resources/js/arima-prepare.js', 'utf8');

function start(responses, enabled = true) {
    const calls = [];
    const timers = [];
    const progress = {};
    const heading = {};
    const state = { calls, timers, progress, heading, reloads: 0 };
    const panel = {
        dataset: { arimaPrepare: '/inventory-analysis/arima/prepare', cutoff: '2026-11-12', branch: '2' },
        querySelector: (selector) => selector === '[data-arima-progress]' ? progress : heading,
        setAttribute: (name, value) => { state[name] = value; },
    };
    vm.runInNewContext(source, {
        document: { querySelector: (selector) => selector.startsWith('meta') ? { content: 'csrf' } : (enabled ? panel : null) },
        window: {
            location: { reload: () => { state.reloads++; } },
            setTimeout: (callback, delay) => timers.push({ callback, delay }),
        },
        fetch: async (url, options) => {
            calls.push({ url, options });
            return responses.shift();
        },
    });

    return state;
}

const response = (body, ok = true) => ({ ok, json: async () => body });
const flush = () => new Promise((resolve) => setImmediate(resolve));

test('automatically continues sequential requests, preserves filters and reloads once complete', async () => {
    const state = start([
        response({ complete: false, remaining: 1 }),
        response({ complete: true, remaining: 0 }),
    ]);
    await flush();
    assert.equal(state.calls.length, 1);
    assert.equal(state.calls[0].options.method, 'POST');
    assert.equal(state.calls[0].options.headers['X-CSRF-TOKEN'], 'csrf');
    assert.deepEqual(JSON.parse(state.calls[0].options.body), { cutoff: '2026-11-12', branch_id: '2' });
    assert.match(state.progress.textContent, /1 kelompok tersisa/);
    assert.equal(state.reloads, 0);
    await state.timers.shift().callback();
    assert.equal(state.calls.length, 2);
    assert.equal(state.reloads, 1);
    assert.equal(state.timers.length, 0);
});

test('waits when another request holds the lock', async () => {
    const state = start([response({ complete: false, busy: true })]);
    await flush();
    assert.equal(state.timers[0].delay, 2000);
    assert.match(state.progress.textContent, /sedang berjalan/);
    assert.equal(state.reloads, 0);
});

test('stops and shows a recoverable error rather than retrying endlessly', async () => {
    const state = start([response({ message: 'Muat ulang halaman untuk mencoba lagi.' }, false)]);
    await flush();
    assert.equal(state.role, 'alert');
    assert.match(state.heading.textContent, /belum selesai/);
    assert.match(state.progress.textContent, /Muat ulang/);
    assert.equal(state.timers.length, 0);
    assert.equal(state.reloads, 0);
});

test('does no work when forecasts are cached or the page uses another method', async () => {
    const state = start([], false);
    await flush();
    assert.equal(state.calls.length, 0);
    assert.equal(state.timers.length, 0);
});
