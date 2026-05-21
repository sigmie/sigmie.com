import { test, expect } from '@playwright/test';

test.describe('Mobile responsive', () => {
    test('docs hamburger opens mobile sidebar', async ({ page }) => {
        await page.goto('/docs/v2/introduction');
        // Mobile-only top-bar button is one of the unlabeled svg buttons; target by its container
        const trigger = page.locator('.lg\\:hidden button').first();
        await expect(trigger).toBeVisible();
        await trigger.click();
        // Sidebar nav links should now be visible
        await expect(page.getByRole('link', { name: /Installation/i }).first()).toBeVisible();
    });

    test('search page is usable on mobile', async ({ page }) => {
        await page.goto('/search');
        await expect(page.getByRole('heading', { name: /Search the docs/i })).toBeVisible();
        await expect(page.getByRole('button', { name: /AI-Powered Search/i })).toBeVisible();
    });
});
