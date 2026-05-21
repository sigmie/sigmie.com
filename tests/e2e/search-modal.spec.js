import { test, expect } from '@playwright/test';

// Wait for Navbar onMounted handlers to register
const gotoDocs = async (page) => {
    await page.goto('/docs/v2/introduction', { waitUntil: 'networkidle' });
    await expect(page.getByRole('button', { name: /Search documentation/i }).first()).toBeVisible();
};

test.describe('Search modal (Cmd+K)', () => {
    test('opens via keyboard shortcut from docs page', async ({ page }) => {
        await gotoDocs(page);
        await page.keyboard.press('Control+k');
        const input = page.getByPlaceholder(/Search documentation/i);
        await expect(input).toBeVisible();
        await expect(input).toBeFocused();
    });

    test('opens via clicking the navbar search button', async ({ page }) => {
        await gotoDocs(page);
        await page.getByRole('button', { name: /Search documentation/i }).first().click();
        const input = page.getByPlaceholder(/Search documentation/i);
        await expect(input).toBeVisible();
        await expect(input).toBeFocused();
    });

    test('Escape closes the modal', async ({ page }) => {
        await gotoDocs(page);
        await page.keyboard.press('Control+k');
        const input = page.getByPlaceholder(/Search documentation/i);
        await expect(input).toBeVisible();
        await page.keyboard.press('Escape');
        await expect(input).toBeHidden();
    });

    test('typing fires the docs search API', async ({ page }) => {
        await gotoDocs(page);
        await page.keyboard.press('Control+k');
        const input = page.getByPlaceholder(/Search documentation/i);
        await expect(input).toBeVisible();

        const reqPromise = page.waitForRequest(
            (req) => req.url().includes('/api/search/docs') && req.method() === 'POST',
            { timeout: 5_000 },
        );
        await input.fill('mappings');
        const req = await reqPromise;
        expect(req.postDataJSON()).toMatchObject({ query: 'mappings' });
    });
});
