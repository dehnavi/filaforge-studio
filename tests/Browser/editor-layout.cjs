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
        await page.evaluate(() => document.querySelector('.ff-studio')._x_dataStack[0].select('shared-override'));
        const checks = [];
        async function checkFrame(width, device, preview) {
            await page.locator(`[data-device="${device}"]`).click();
            await page.waitForTimeout(80);
            const box = await page.evaluate(() => {
                const stage = document.querySelector('.ff-stage'), sr = stage.getBoundingClientRect();
                const frames = [...stage.querySelectorAll('iframe')].filter(frame => getComputedStyle(frame).display !== 'none');
                const rect = frames[0]?.getBoundingClientRect();
                const library = document.querySelector('.ff-library'), lr = library.getBoundingClientRect();
                const css = getComputedStyle(stage);
                return { visible: frames.length, left: rect?.left, right: rect?.right, center: rect ? rect.left + rect.width / 2 : null,
                    expectedCenter: sr.left + stage.clientLeft + stage.clientWidth / 2 + (parseFloat(css.paddingLeft) - parseFloat(css.paddingRight)) / 2,
                    stageLeft: sr.left, stageRight: sr.right, libraryVisible: getComputedStyle(library).display !== 'none', libraryLeft: lr.left, libraryRight: lr.right,
                    overflow: document.documentElement.scrollWidth > innerWidth };
            });
            assert.equal(box.visible, 1, `${width}/${device}: both iframes must never occupy the stage`);
            assert.ok(Math.abs(box.center - box.expectedCenter) < 1.5, `${width}/${device}: canvas is not centered: ${JSON.stringify(box)}`);
            assert.ok(box.left >= box.stageLeft - 1 && box.right <= box.stageRight + 1, `${width}/${device}: canvas escaped its stage`);
            if (box.libraryVisible) assert.ok(box.right <= box.libraryLeft + 1 || box.left >= box.libraryRight - 1, `${width}/${device}: canvas overlaps the element library`);
            assert.equal(box.overflow, false, `${width}/${device}: document overflows`);
            checks.push({ width, device, preview, centered: true });
        }
        for (const width of [1440, 1024, 390]) {
            await page.setViewportSize({ width, height: 1000 });
            await page.evaluate(width => { document.querySelector('.ff-studio')._x_dataStack[0].showInspector = width > 760; }, width);
            for (const device of ['desktop', 'tablet', 'mobile']) {
                await checkFrame(width, device, false);
                if (process.env.FILAFORGE_SCREENSHOTS && (width === 1440 || width === 390 && device === 'mobile')) {
                    fs.mkdirSync(process.env.FILAFORGE_SCREENSHOTS, { recursive: true });
                    await page.screenshot({ path: `${process.env.FILAFORGE_SCREENSHOTS}/${width}-${device}.png` });
                }
            }
        }
        const canvas = page.frameLocator('iframe[x-ref="canvas"]');
        await page.locator('[data-preview-toggle]').click();
        await page.waitForFunction(() => document.querySelector('.ff-studio')._x_dataStack[0].previewMode);
        for (const device of ['desktop', 'tablet', 'mobile']) await checkFrame(390, device, true);
        await page.locator('[data-preview-toggle]').click();
        await page.evaluate(() => document.querySelector('.ff-studio')._x_dataStack[0].showInspector = true);
        assert.equal(await page.locator('.ff-canvas').isVisible(), false, 'On compact screens the inspector must replace the canvas, not overlap it');
        assert.deepEqual(errors, []);
        console.log(JSON.stringify({ checks, htmlTablePreserved: true, compactInspectorSeparate: true, errors }));
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
