// Run against the authenticated local workbench with NODE_PATH pointing to Playwright.
// FILAFORGE_EDITOR_URL and FILAFORGE_ACCESS_FILE select the local fixture and its credentials.
const fs = require('node:fs');
const assert = require('node:assert/strict');
const { chromium } = require('playwright');
const access = fs.readFileSync(process.env.FILAFORGE_ACCESS_FILE, 'utf8');
const editorUrl = process.env.FILAFORGE_EDITOR_URL;
const origin = new URL(editorUrl).origin;
const email = access.match(/^Email: (.+)$/m)[1];
const password = access.match(/^Password: (.+)$/m)[1];
(async () => {
    const browser = await chromium.launch({ executablePath: process.env.CHROME_PATH || '/opt/google/chrome/chrome', headless: true, args: ['--no-sandbox'] });
    try {
        const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.goto(origin + '/admin/login', { waitUntil: 'domcontentloaded' });
        await page.locator('input[type=email]').fill(email);
        await page.locator('input[type=password]').fill(password);
        await page.locator('button[type=submit]').click();
        await page.waitForURL(/\/admin\/?$/);
        await page.goto(editorUrl, { waitUntil: 'domcontentloaded' });
        await page.waitForFunction(() => document.querySelector('.ff-studio')?._x_dataStack?.[0]?.pickerLoading === false);
        // A repeated iframe load/connect must keep subsequent edits visible without a refresh.
        const canvas = page.frameLocator('iframe[x-ref="canvas"]');
        const canvasNodes = canvas.locator('[data-node]');
        await canvasNodes.first().waitFor();
        const beforeInsert = await canvasNodes.count();
        await page.evaluate(() => {
            const editor = document.querySelector('.ff-studio')._x_dataStack[0];
            editor.autosavePaused = true;
            editor.connect();
        });
        await page.waitForFunction(() => document.querySelector('.ff-studio')._x_dataStack[0].canvasReady);
        await page.evaluate(() => document.querySelector('.ff-studio')._x_dataStack[0].insert('text', 'root'));
        await page.waitForFunction(expected => document.querySelector('.ff-studio')._x_dataStack[0].nodes().length === expected, beforeInsert + 1);
        await canvasNodes.nth(beforeInsert).waitFor();
        assert.equal(await canvasNodes.count(), beforeInsert + 1, 'Edits must reach the canvas after reconnecting');
        for (const type of ['heading', 'container', 'grid', 'columns', 'table', 'image', 'button']) {
            const before = await canvasNodes.count();
            await page.evaluate(type => document.querySelector('.ff-studio')._x_dataStack[0].insert(type, 'root'), type);
            await canvasNodes.nth(before).waitFor();
        }
        const expected = await canvasNodes.count();
        await page.evaluate(() => {
            const frame = document.querySelector('iframe[x-ref="canvas"]');
            frame.srcdoc = frame.srcdoc;
        });
        await canvasNodes.nth(expected - 1).waitFor();
        assert.equal(await canvasNodes.count(), expected, 'Reloading the iframe must restore the current document');
        const editingFrame = await page.locator('iframe[x-ref="canvas"]').elementHandle();
        await page.evaluate(() => {
            const editor = document.querySelector('.ff-studio')._x_dataStack[0];
            editor.select(editor.nodes().filter(node => node.type === 'heading').at(-1).id);
            editor.editInline();
        });
        await canvas.locator('[contenteditable="true"]').waitFor();
        await page.keyboard.type(' Pending inline edit');
        await page.evaluate(() => document.querySelector('.ff-studio')._x_dataStack[0].render());
        await page.waitForTimeout(2700);
        assert.equal(await editingFrame.evaluate(frame => frame === document.querySelector('iframe[x-ref="canvas"]')), true);
        await canvas.locator('[contenteditable="true"]').waitFor();
        await page.keyboard.press('Escape');
        await page.waitForFunction(() => document.querySelector('.ff-studio')._x_dataStack[0].canvasTimer === null);
        const snapshot = await page.evaluate(() => {
            const editor = document.querySelector('.ff-studio')._x_dataStack[0];
            return { document: JSON.stringify(editor.document), past: editor.past.length, version: editor.version };
        });
        // A load event alone is insufficient: a blank iframe never answers the handshake.
        await page.evaluate(() => {
            document.querySelector('iframe[x-ref="canvas"]').srcdoc = '<!doctype html><html><body></body></html>';
        });
        await canvasNodes.first().waitFor({ state: 'detached' });
        await canvasNodes.nth(expected - 1).waitFor();
        await page.waitForFunction(() => {
            const editor = document.querySelector('.ff-studio')._x_dataStack[0];
            return editor.canvasReady && editor.canvasTimer === null;
        });
        assert.equal(await canvasNodes.count(), expected, 'A blank iframe must recover without refreshing the editor');
        assert.deepEqual(await page.evaluate(() => {
            const editor = document.querySelector('.ff-studio')._x_dataStack[0];
            return { document: JSON.stringify(editor.document), past: editor.past.length, version: editor.version };
        }), snapshot, 'Recovering the frame must preserve unsaved content and undo history');
        // Simulate a silent channel failure while a new edit is waiting to be painted.
        await page.evaluate(() => {
            const editor = document.querySelector('.ff-studio')._x_dataStack[0];
            editor.port.close();
            editor.insert('text', 'root');
        });
        await canvasNodes.nth(expected).waitFor();
        assert.equal(await canvasNodes.count(), expected + 1, 'A dropped render must recover the latest edit');
        const frame = await page.locator('iframe[x-ref="canvas"]').elementHandle();
        await page.evaluate(async () => {
            const editor = document.querySelector('.ff-studio')._x_dataStack[0];
            await editor.$wire.$refresh();
            await editor.$wire.revisionHistory();
        });
        assert.equal(await frame.evaluate(frame => frame === document.querySelector('iframe[x-ref="canvas"]')), true,
            'Livewire updates must retain the canvas iframe');
        assert.equal(await canvasNodes.count(), expected + 1, 'Livewire updates must preserve the current canvas');
        // Enable persistence checks only for a disposable fixture, never an existing user's page.
        if (process.env.FILAFORGE_CANVAS_SAVE === '1') {
            for (let iteration = 0; iteration < 3; iteration++) {
                await page.locator('.ff-bar button[x-on\\:click="save()"]').click();
                await page.waitForFunction(() => {
                    const editor = document.querySelector('.ff-studio')._x_dataStack[0];
                    return !editor.busy && !editor.dirty;
                });
                await page.locator('[data-preview-toggle]').click();
                await page.waitForFunction(() => {
                    const editor = document.querySelector('.ff-studio')._x_dataStack[0];
                    return editor.previewMode && !editor.previewBusy;
                });
                const preview = page.frameLocator('iframe[x-ref="preview"]');
                await preview.locator('h2').first().waitFor();
                assert.ok((await preview.locator('body').innerText()).trim().length > 0, 'Saved preview must contain the document');
                assert.equal(await page.locator('iframe[x-ref="preview"]').isVisible(), true);
                await page.locator('[data-preview-toggle]').click();
                await canvasNodes.nth(expected).waitFor();
                assert.equal(await canvasNodes.count(), expected + 1, 'Returning from preview must retain the editing canvas');
            }
        }
        assert.deepEqual(errors, []);
        console.log(JSON.stringify({ reconnect: true, insertions: 9, iframeReload: true, inlineEditingPreserved: true, blankFrameRecovery: true, droppedRenderRecovery: true, livewireUpdates: true, savedPreviews: process.env.FILAFORGE_CANVAS_SAVE === '1' ? 3 : 0, errors }));
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
