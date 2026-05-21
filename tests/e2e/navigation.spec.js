import { test, expect } from '@playwright/test';

test.describe('Page navigation', () => {
    test('homepage loads', async ({ page }) => {
        const response = await page.goto('/');
        expect(response?.status()).toBe(200);
        await expect(page).toHaveTitle(/Sigmie/);
    });

    test('search page renders hero + mode toggle', async ({ page }) => {
        await page.goto('/search');
        await expect(page.getByRole('heading', { name: /Search the docs/i })).toBeVisible();
        await expect(page.getByRole('button', { name: /AI-Powered Search/i })).toBeVisible();
        await expect(page.getByRole('button', { name: /Standard Search/i })).toBeVisible();
    });

    test('blog index lists posts and links navigate to a post', async ({ page }) => {
        await page.goto('/blog');
        await expect(page.getByRole('heading', { level: 1 }).first()).toBeVisible();
        const firstPost = page.locator('main a[href^="/blog/"]').first();
        await expect(firstPost).toBeVisible();
        await firstPost.click();
        await page.waitForURL(/\/blog\/.+/);
        await expect(page.locator('article header h1')).toBeVisible();
        await expect(page.getByRole('link', { name: /Back to blog/i })).toBeVisible();
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

    test('resumes page renders form', async ({ page }) => {
        await page.goto('/resumes');
        await expect(page.getByLabel(/Job title/i)).toBeVisible();
        await expect(page.getByLabel(/Job description/i)).toBeVisible();
        await expect(page.getByRole('button', { name: /Find candidates/i })).toBeVisible();
    });

    test('sitemap.xml serves valid XML with multiple URLs', async ({ request }) => {
        const response = await request.get('/sitemap.xml');
        expect(response.status()).toBe(200);
        expect(response.headers()['content-type']).toContain('xml');
        const body = await response.text();
        expect(body).toContain('<urlset');
        expect(body).toMatch(/<loc>.*\/docs\/v2\/.+<\/loc>/);
        expect(body).toMatch(/<loc>.*\/blog\/.+<\/loc>/);
        const urlCount = (body.match(/<url>/g) || []).length;
        expect(urlCount).toBeGreaterThan(20);
    });
});
