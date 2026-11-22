import { test, expect } from '@playwright/test';

test.describe('Mobile responsive', () => {
    test('docs hamburger opens mobile sidebar', async ({ page }) => {
        await page.goto('/docs/v2/introduction');
        const trigger = page.getByRole('button', { name: 'Open menu' });
        await expect(trigger).toBeVisible();
        await trigger.click();
        // The menu lists the docs pages on phones
        await expect(page.getByRole('navigation', { name: 'Mobile' }).getByRole('link', { name: 'Installation' })).toBeVisible();
    });

});
