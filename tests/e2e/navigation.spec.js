import { test, expect } from '@playwright/test';

test.describe('Page navigation', () => {
    test('homepage loads', async ({ page }) => {
        const response = await page.goto('/');
        expect(response?.status()).toBe(200);
        await expect(page).toHaveTitle(/Sigmie/);
    });

    test('docs page loads with sidebar + content', async ({ page }) => {
        await page.goto('/docs/v2/introduction');
        await expect(page.locator('article h1').first()).toBeVisible();
        await expect(page.locator('article')).toBeVisible();
        await expect(page.getByRole('button', { name: /Copy page/i })).toBeVisible();
    });

    test('docs root redirects to default version', async ({ page }) => {
        const response = await page.goto('/docs');
        expect(response?.status()).toBe(200);
        await expect(page).toHaveURL(/\/docs\/v2\/introduction/);
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
