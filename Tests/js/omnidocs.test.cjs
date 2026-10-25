// The search of assets/omnidocs.js, on node's test runner: its ranking, its marks, its cut.
//   node --test Tests/js/omnidocs.test.cjs
const test = require('node:test');
const assert = require('node:assert');

// The file sets up a page when it loads: a document that has none of what it looks for.
const listeners = {};
global.window = {
    document: {
        readyState: 'complete',
        documentElement: { classList: { toggle() {}, add() {}, remove() {} }, getAttribute: () => null },
        querySelectorAll: () => [],
        querySelector: () => null,
        addEventListener(type, f) { (listeners[type] = listeners[type] || []).push(f); },
    },
    navigator: { platform: 'MacIntel' },
    addEventListener() {},
    location: { search: '' },
};
const { search } = require('../../assets/omnidocs.js');

const entries = search.prepare([
    { k: 'page', t: 'Sticky and stops', u: '/sticky', d: 'The address follows the reading; the scroll stops.', h: 'Scroll stops · Keys' },
    { k: 'heading', t: 'Scroll stops', u: '/sticky#scroll-stops', d: 'Places a scroll settles on.', p: 'Sticky and stops' },
    { k: 'symbol', t: 'StickyStops.attach', u: '/api/stickystops#attach', d: 'The stops of a scroller.', x: 'function', s: 'StickyStops.attach(scroller, options)' },
    { k: 'page', t: 'Cookie', u: '/cookie', d: 'Les catégories du consentement.' },
]);

test('every word must be found, the title first', () => {
    assert.deepStrictEqual(search.rank(entries, 'stops').map((e) => e.u), ['/sticky', '/sticky#scroll-stops', '/api/stickystops#attach']);
    assert.deepStrictEqual(search.rank(entries, 'scroll keys').map((e) => e.u), ['/sticky']);
    assert.deepStrictEqual(search.rank(entries, '   '), []);
});

test('an API entry named by its last part leads', () => {
    assert.strictEqual(search.rank(entries, 'attach')[0].u, '/api/stickystops#attach');
});

test('accents and case do not count', () => {
    assert.deepStrictEqual(search.rank(entries, 'CATEGORIES').map((e) => e.u), ['/cookie']);
    assert.strictEqual(search.mark('Les catégories', 'categories'), 'Les <mark>catégories</mark>');
});

test('marks are escaped and never nest', () => {
    assert.strictEqual(search.mark('<b>stop</b> stops', 'stop stops'), '&lt;b&gt;<mark>stop</mark>&lt;/b&gt; <mark>stops</mark>');
});

test('a long text is cut around its first word found', () => {
    const long = 'a'.repeat(200) + ' needle ' + 'b'.repeat(200);
    const cut = search.around(long, 'needle', 60);
    assert.ok(cut.includes('needle') && cut.startsWith('…') && cut.endsWith('…'), cut);
});

test('a search key is taken, the find in page is not', () => {
    let prevented = false;
    const event = { key: 'f', metaKey: true, ctrlKey: false, altKey: false, shiftKey: false, target: { tagName: 'BODY', closest: () => null }, preventDefault() { prevented = true; } };
    listeners.keydown.forEach((f) => f(event));
    assert.strictEqual(prevented, false, 'Cmd+F stays the browser\'s');
});
