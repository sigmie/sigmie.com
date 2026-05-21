import { test, expect } from '@playwright/test';

test.describe('Dark mode toggle', () => {
    test('toggle switches html.dark class', async ({ page }) => {
        await page.goto('/docs/v2/introduction');

        const startState = await page.evaluate(() => document.documentElement.classList.contains('dark'));

        await page.getByTitle(/Switch to (light|dark) mode/i).click();

        const afterState = await page.evaluate(() => document.documentElement.classList.contains('dark'));
        expect(afterState).not.toBe(startState);
    });

    test('default surface uses DESIGN.md canvas-white in light mode', async ({ page }) => {
        await page.goto('/docs/v2/introduction');

        await page.evaluate(() => {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        });
        await page.reload();

        const bg = await page.evaluate(() => getComputedStyle(document.body).backgroundColor);
        // canvas-white = #ffffff
        expect(bg).toBe('rgb(255, 255, 255)');
    });
});
