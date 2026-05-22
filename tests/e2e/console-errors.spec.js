import { test, expect } from '@playwright/test';

const pages = [
    { path: '/', name: 'home' },
    { path: '/docs/v2/introduction', name: 'docs-intro' },
    { path: '/docs/v2/installation', name: 'docs-installation' },
];

for (const { path, name } of pages) {
    test(`${name} loads without console errors`, async ({ page }) => {
        const errors = [];
        page.on('console', (msg) => {
            if (msg.type() === 'error') errors.push(msg.text());
        });
        page.on('pageerror', (err) => errors.push(err.message));

        await page.goto(path, { waitUntil: 'networkidle' });
        await page.waitForTimeout(500);

        // Filter out expected/noisy errors (missing assets, demo backend 500s on /)
        const fatal = errors.filter(
            (e) =>
                !/Failed to load resource/i.test(e) &&
                !/favicon/i.test(e) &&
                !/og-image/i.test(e) &&
                !/twitter-card/i.test(e) &&
                !/manifest/i.test(e) &&
                !/AxiosError.*status code 5\d\d/i.test(e) &&
                !/Search error|Image query error|Cart initialization error/i.test(e),
        );

        expect(fatal, `Console errors on ${path}: \n${fatal.join('\n')}`).toEqual([]);
    });
}
