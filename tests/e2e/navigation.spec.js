import { test, expect } from '@playwright/test';

test.describe('Page navigation', () => {
    test('homepage loads', async ({ page }) => {
        const response = await page.goto('/');
        expect(response?.status()).toBe(200);
        await expect(page).toHaveTitle(/Sigmie/);
    });

    test('docs page loads with sidebar + content', async ({ page }) => {
        await page.goto('/docs/v2/introduction');
        await expect(page.locator('main h1')).toBeVisible();
        await expect(page.locator('article')).toBeVisible();
        await expect(page.getByRole('button', { name: /Copy page/i })).toBeVisible();
    });

    test('docs root redirects to default version', async ({ page }) => {
        const response = await page.goto('/docs');
        expect(response?.status()).toBe(200);
        await expect(page).toHaveURL(/\/docs\/v2\/introduction/);
    });

    test('TOC links are native fragments: hash, history and the heading clears the navbar', async ({ page }) => {
        await page.goto('/docs/v2/search');
        const toc = page.locator('aside').getByRole('navigation', { name: 'On this page' });

        await toc.getByRole('link', { name: 'Filter', exact: true }).click();
        await expect(page).toHaveURL(/\/docs\/v2\/search#filter$/);
        await expect.poll(() => page.locator('#filter').evaluate((el) => Math.round(el.getBoundingClientRect().top))).toBeGreaterThanOrEqual(64);
        await expect(page.locator('#filter')).toBeInViewport();

        await page.goBack();
        await expect(page).toHaveURL(/\/docs\/v2\/search$/);
    });

    test('headings keep their text and ids after mount, old content- links still land', async ({ page }) => {
        await page.goto('/docs/v2/search#content-filter');
        await expect(page.locator('h2#filter')).toHaveText('#Filter');
        await expect(page.locator('h2#filter a.heading-permalink#content-filter')).toHaveCount(1);
        await expect(page.locator('#content-filter')).toBeInViewport();
    });

    test('unversioned markdown links stay markdown', async ({ request }) => {
        const response = await request.get('/docs/search.md');
        expect(response.url()).toMatch(/\/docs\/v2\/search\.md$/);
        expect(response.headers()['content-type']).toContain('text/markdown');
    });

    test('sitemap.xml serves valid XML with multiple URLs', async ({ request }) => {
        const response = await request.get('/sitemap.xml');
        expect(response.status()).toBe(200);
        expect(response.headers()['content-type']).toContain('xml');
        const body = await response.text();
        expect(body).toContain('<urlset');
        expect(body).toMatch(/<loc>.*\/docs\/v2\/.+<\/loc>/);
        const urlCount = (body.match(/<url>/g) || []).length;
        expect(urlCount).toBeGreaterThan(20);
    });
});
