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
        await page.waitForFunction(() => document.querySelector('.ff-studio')?._x_dataStack?.[0]?.sectionItems?.length > 0);
        // A repeated iframe load/connect must keep subsequent edits visible without a refresh.
        const canvasNodes = page.frameLocator('iframe[x-ref="canvas"]').locator('[data-node]');
        await canvasNodes.first().waitFor();
        const beforeInsert = await canvasNodes.count();
        await page.evaluate(() => {
            const editor = document.querySelector('.ff-studio')._x_dataStack[0];
            editor.autosavePaused = true;
            editor.connect();
        });
        await page.waitForTimeout(100);
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
        assert.deepEqual(errors, []);
        console.log(JSON.stringify({ reconnect: true, insertions: 8, iframeReload: true, errors }));
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
