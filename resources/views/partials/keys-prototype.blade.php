{{-- PROTOTYPE (#449), throwaway: four solid-button key styles on the real game page, picked with ?variant=A..D.
     Lives on the prototype/449-keys branch only; the winner gets rewritten into app.css. --}}
<style>
    html[data-keys] .key {
        font: inherit;
        font-weight: 600;
        font-size: 1.1rem;
        text-transform: uppercase;
        cursor: pointer;
        transition: transform 60ms, box-shadow 60ms, background-color 120ms, filter 120ms;
    }
    html[data-keys] .key:focus-visible {
        outline: 2px solid #fec63b;
        outline-offset: 2px;
    }
    html[data-keys] .key.correct, html[data-keys] .key.present {
        color: #0e1626;
    }

    /* A: Keycap. Raised navy caps with a darker edge that sink when pressed. */
    html[data-keys="A"] .key {
        background: #2a3d5e;
        color: #e6eefa;
        border-radius: 6px;
        box-shadow: 0 4px 0 #111b2d;
        margin: 2px 2px 6px;
    }
    html[data-keys="A"] .key:hover { background: #33496f; }
    html[data-keys="A"] .key:active { transform: translateY(3px); box-shadow: 0 1px 0 #111b2d; }
    html[data-keys="A"] .key.correct { background: var(--correct-color); box-shadow: 0 4px 0 #23802a; }
    html[data-keys="A"] .key.present { background: var(--present-color); box-shadow: 0 4px 0 #a39d1c; }
    html[data-keys="A"] .key.absent { background: #18233a; color: #56698a; box-shadow: 0 4px 0 #0b1220; }

    /* B: Framed. Dark pills with the board's blue edge; Enter carries the gold accent. */
    html[data-keys="B"] .key {
        background: #121d31;
        color: #cfe0f7;
        border: 1px solid #3d5f8c;
        border-radius: 999px;
        margin: 3px;
        height: 46px;
    }
    html[data-keys="B"] .key:hover { border-color: #6ba0e2; color: #fff; }
    html[data-keys="B"] .key:active { transform: scale(.94); background: #1c2b45; }
    html[data-keys="B"] .key[data-key="Enter"] { background: #fec63b; border-color: #fec63b; color: #0e1626; font-size: .8rem; }
    html[data-keys="B"] .key.correct { background: var(--correct-color); border-color: var(--correct-color); }
    html[data-keys="B"] .key.present { background: var(--present-color); border-color: var(--present-color); }
    html[data-keys="B"] .key.absent { background: transparent; border-color: #1f2d45; color: #3f5272; }

    /* C: Tile. Square keys cut from the board's gradient, so they read as spare tiles. */
    html[data-keys="C"] .key {
        background: linear-gradient(153deg, #24375a 0%, #101a2c 100%);
        color: #8fb3e3;
        border-radius: 3px;
        box-shadow: inset 0 0 0 1px rgba(107, 160, 226, .18);
        font-size: 1.25rem;
    }
    html[data-keys="C"] .key:hover { color: #fff; box-shadow: inset 0 0 0 1px rgba(107, 160, 226, .5); }
    html[data-keys="C"] .key:active { filter: brightness(1.4); }
    html[data-keys="C"] .key.correct { background: var(--correct-color); box-shadow: 0 0 12px rgba(38, 246, 110, .42); }
    html[data-keys="C"] .key.present { background: var(--present-color); box-shadow: 0 0 12px rgba(246, 171, 38, .42); }
    html[data-keys="C"] .key.absent { background: #0b1220; color: #34445f; box-shadow: none; }

    /* D: Inverted. Light, high-contrast keys that stand off the dark page like a real keyboard. */
    html[data-keys="D"] .key {
        background: #dfe8f5;
        color: #0e1626;
        border-radius: 8px;
        height: 54px;
        font-weight: 700;
    }
    html[data-keys="D"] .key:hover { background: #fff; }
    html[data-keys="D"] .key:active { transform: scale(.94); }
    html[data-keys="D"] .key[data-key="Enter"], html[data-keys="D"] .key[data-key="Backspace"] { background: #6ba0e2; }
    html[data-keys="D"] .key.absent { background: #2b3850; color: #8090aa; }

    /* E: Light tile. C's shading on today's light grey, so the keys still stand off the board. */
    html[data-keys="E"] .key, html[data-keys="F"] .key {
        background: linear-gradient(153deg, #e2e2e2 0%, #a8a8a8 100%);
        color: #0e1626;
        border-radius: 3px;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .45);
        font-size: 1.25rem;
    }
    html[data-keys="E"] .key:hover, html[data-keys="F"] .key:hover { filter: brightness(1.08); }
    html[data-keys="E"] .key:active, html[data-keys="F"] .key:active { filter: brightness(.85); }
    html[data-keys="E"] .key.correct, html[data-keys="F"] .key.correct { background: var(--correct-color); box-shadow: 0 0 12px rgba(38, 246, 110, .42); }
    html[data-keys="E"] .key.present, html[data-keys="F"] .key.present { background: var(--present-color); box-shadow: 0 0 12px rgba(246, 171, 38, .42); }
    html[data-keys="E"] .key.absent, html[data-keys="F"] .key.absent { background: #2b3850; color: #6f80a0; box-shadow: none; }

    /* F: Cool light tile. As E, but the grey leans toward the board's blue. */
    html[data-keys="F"] .key {
        background: linear-gradient(153deg, #e3e9f2 0%, #a3afc2 100%);
    }

    /* G: Bevelled cool tile. F with a bevelled bottom edge that flattens when pressed, and an Enter that fits. */
    html[data-keys="G"] .key {
        background: linear-gradient(153deg, #e3e9f2 0%, #a3afc2 100%);
        color: #0e1626;
        border-radius: 4px;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .7), inset 0 -4px 0 rgba(40, 58, 88, .35);
        font-size: 1.25rem;
        padding-bottom: 3px;
    }
    html[data-keys="G"] .key[data-key="Enter"] { font-size: .75rem; letter-spacing: .02em; }
    html[data-keys="G"] .key:hover { filter: brightness(1.08); }
    html[data-keys="G"] .key:active { box-shadow: inset 0 1px 0 rgba(255, 255, 255, .4), inset 0 -1px 0 rgba(40, 58, 88, .35); padding: 3px 0 0; filter: brightness(.92); }
    html[data-keys="G"] .key.correct { background: var(--correct-color); box-shadow: inset 0 1px 0 rgba(255, 255, 255, .5), inset 0 -4px 0 rgba(0, 60, 0, .3), 0 0 12px rgba(38, 246, 110, .42); }
    html[data-keys="G"] .key.present { background: var(--present-color); box-shadow: inset 0 1px 0 rgba(255, 255, 255, .5), inset 0 -4px 0 rgba(90, 80, 0, .3), 0 0 12px rgba(246, 171, 38, .42); }
    html[data-keys="G"] .key.absent { background: #2b3850; color: #6f80a0; box-shadow: inset 0 -4px 0 rgba(0, 0, 0, .3); }

    #keys-prototype {
        position: fixed;
        bottom: 12px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 1000;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 10px;
        background: #ff2bd6;
        color: #000;
        border-radius: 999px;
        font: 600 13px/1 system-ui, sans-serif;
        box-shadow: 0 4px 16px rgba(0, 0, 0, .5);
    }
    #keys-prototype button { font: inherit; border: 0; background: #000; color: #fff; border-radius: 999px; width: 26px; height: 26px; cursor: pointer; }
</style>
<div id="keys-prototype">
    <button type="button" data-step="-1" aria-label="Previous variant">←</button>
    <span></span>
    <button type="button" data-step="1" aria-label="Next variant">→</button>
</div>
<script>
    (() => {
        const variants = { A: 'Keycap', B: 'Framed', C: 'Tile', D: 'Inverted', E: 'Light tile', F: 'Cool light tile', G: 'Bevelled cool tile' };
        const keys = Object.keys(variants);
        const bar = document.getElementById('keys-prototype');
        const show = (key) => {
            document.documentElement.dataset.keys = key;
            bar.querySelector('span').textContent = `${key} (${variants[key]})`;
            const url = new URL(location.href);
            url.searchParams.set('variant', key);
            history.replaceState(null, '', url);
        };
        const step = (by) => show(keys[(keys.indexOf(document.documentElement.dataset.keys) + by + keys.length) % keys.length]);
        const initial = new URLSearchParams(location.search).get('variant');
        show(keys.includes(initial) ? initial : 'A');
        bar.addEventListener('mousedown', (event) => event.preventDefault());
        bar.addEventListener('click', (event) => event.target.dataset.step && step(Number(event.target.dataset.step)));
        addEventListener('keydown', (event) => {
            if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
                step(event.key === 'ArrowLeft' ? -1 : 1);
            }
        });
    })();
</script>
