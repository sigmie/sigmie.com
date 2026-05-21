import { test } from '@playwright/test';

const pages = [
    { path: '/search', name: 'search' },
    { path: '/blog', name: 'blog' },
    { path: '/blog/a-different-approach', name: 'post' },
    { path: '/docs/v2/introduction', name: 'docs-intro' },
    { path: '/docs/v2/search', name: 'docs-search' },
    { path: '/docs/v2/installation', name: 'docs-installation' },
    { path: '/resumes', name: 'resumes' },
];

for (const { path, name } of pages) {
    test(`screenshot ${name}`, async ({ page }, testInfo) => {
        await page.goto(path, { waitUntil: 'networkidle' });
        await page.waitForTimeout(400);
        await page.screenshot({
            path: `tests/e2e/__screenshots__/${testInfo.project.name}-${name}.png`,
            fullPage: true,
        });
    });
}

test('screenshot search modal open on docs page', async ({ page }, testInfo) => {
    await page.goto('/docs/v2/introduction', { waitUntil: 'networkidle' });
    await page.waitForTimeout(400);
    await page.keyboard.press('Control+k');
    await page.waitForTimeout(300);
    await page.screenshot({
        path: `tests/e2e/__screenshots__/${testInfo.project.name}-search-modal.png`,
        fullPage: false,
    });
});
