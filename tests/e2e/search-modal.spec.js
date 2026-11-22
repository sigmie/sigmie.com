import { test, expect } from '@playwright/test';

const SEARCH_FIXTURE = {
    results: [
        {
            _id: '1',
            title: 'Semantic fields on OpenSearch',
            version: 'v2',
            page: 'opensearch',
            page_title: 'OpenSearch',
            url: '/docs/v2/opensearch#semantic-fields-on-opensearch',
            content: 'Define semantic fields the same way // [tl! highlight] you do for Elasticsearch.',
        },
        {
            _id: '2',
            title: 'Semantic fields',
            version: 'v2',
            page: 'mappings',
            page_title: 'Mappings & Properties',
            url: '/docs/v2/mappings#semantic-fields',
            content: 'semantic() adds a vector field to a text field.',
        },
    ],
    total: 2,
};

// Wait for the navbar's onMounted handlers to register
const gotoDocs = async (page) => {
    await page.goto('/docs/v2/introduction', { waitUntil: 'networkidle' });
    await expect(page.getByRole('button', { name: /Search docs/i }).first()).toBeVisible();
};

const openWithKeyboard = async (page) => {
    await page.keyboard.press('Control+k');
    const input = page.getByRole('combobox', { name: 'Search documentation' });
    await expect(input).toBeFocused();
    return input;
};

test.describe('Search dialog (Cmd+K)', () => {
    test('opens via keyboard shortcut and lists suggested pages', async ({ page }) => {
        await gotoDocs(page);
        await openWithKeyboard(page);
        await expect(page.getByText('Suggested')).toBeVisible();
        await expect(page.getByRole('option').first()).toContainText('Quick start');
        await expect(page.getByRole('option').first()).toHaveAttribute('aria-selected', 'true');
    });

    test('opens via clicking the navbar search button', async ({ page }) => {
        await gotoDocs(page);
        await page.getByRole('button', { name: /Search docs/i }).first().click();
        await expect(page.getByRole('combobox', { name: 'Search documentation' })).toBeFocused();
    });

    test('Escape closes the dialog', async ({ page }) => {
        await gotoDocs(page);
        await openWithKeyboard(page);
        await page.keyboard.press('Escape');
        await expect(page.getByRole('dialog')).toBeHidden();
    });

    test('typing fires the docs search API', async ({ page }) => {
        await gotoDocs(page);
        const input = await openWithKeyboard(page);

        const reqPromise = page.waitForRequest(
            (req) => req.url().includes('/api/search/docs') && req.method() === 'POST',
            { timeout: 5_000 },
        );
        await input.fill('mappings');
        const req = await reqPromise;
        expect(req.postDataJSON()).toMatchObject({ query: 'mappings' });
    });

    test('results show the page title and a clean snippet, and Enter opens the selected one', async ({ page }) => {
        await page.route('**/api/search/docs', (route) => route.fulfill({ json: SEARCH_FIXTURE }));
        await gotoDocs(page);
        const input = await openWithKeyboard(page);
        await input.fill('semantic');

        const options = page.getByRole('option');
        await expect(page.getByText('2 results')).toBeVisible();
        await expect(options.first()).toContainText('OpenSearch');
        await expect(options.first()).not.toContainText('[tl!');
        await expect(options.first().locator('strong').first()).toHaveText(/semantic/i);

        await page.keyboard.press('ArrowDown');
        await expect(options.nth(1)).toHaveAttribute('aria-selected', 'true');
        await page.keyboard.press('Enter');
        await expect(page).toHaveURL(/\/docs\/v2\/mappings#semantic-fields$/);
        await expect(page.getByRole('dialog')).toBeHidden();
    });
});
