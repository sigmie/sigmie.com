import { test, expect } from '@playwright/test';

// Needs the server started with AGENT_CHAT_ENABLED=true. The agent endpoint is
// mocked, so no request reaches the model.
const reply = [
    { type: 'start', messageId: 'a1' },
    { type: 'text-start', id: 't1' },
    { type: 'text-delta', id: 't1', delta: 'Use newSearch().' },
    { type: 'text-end', id: 't1' },
    { type: 'finish' },
].map((part) => `data: ${JSON.stringify(part)}\n\n`).join('') + 'data: [DONE]\n\n';

test.describe('Docs chat', () => {
    test.beforeEach(async ({ page }) => {
        await page.route('**/api/agent/chat', (route) => route.fulfill({
            status: 200,
            headers: { 'content-type': 'text/event-stream', 'x-vercel-ai-ui-message-stream': 'v1' },
            body: reply,
        }));
    });

    test('loads only when opened, focuses, and keeps the conversation across close and page visits', async ({ page }) => {
        const chunks = [];
        page.on('request', (request) => /ChatWidget/.test(request.url()) && chunks.push(request.url()));

        await page.goto('/docs/v2/search');
        const launcher = page.getByRole('button', { name: 'Open documentation chat' });
        await expect(launcher).toBeVisible();
        expect(chunks).toEqual([]);

        await launcher.click();
        const input = page.getByPlaceholder('Ask a question…');
        await expect(input).toBeFocused();
        expect(chunks.length).toBeGreaterThan(0);

        await input.fill('How do I search?');
        await input.press('Enter');
        const chat = page.getByRole('complementary', { name: 'Documentation chat' });
        await expect(chat.getByText('Use newSearch().')).toBeVisible();

        await chat.getByRole('button', { name: 'Close chat' }).click();
        await expect(launcher).toBeFocused();
        await expect(input).not.toBeFocused();

        await launcher.click();
        await expect(chat.getByText('How do I search?')).toBeVisible();

        await page.locator('aside nav').first().getByRole('link', { name: 'Facets', exact: true }).click();
        await expect(page).toHaveURL(/\/docs\/v2\/facets$/);
        await expect(chat.getByText('Use newSearch().')).toBeVisible();
    });

    test('a closed panel takes no focus and Escape closes an open one', async ({ page }) => {
        await page.goto('/docs/v2/search');
        await page.getByRole('button', { name: 'Open documentation chat' }).click();
        const input = page.getByPlaceholder('Ask a question…');
        await expect(input).toBeFocused();

        await page.keyboard.press('Escape');
        await expect(page.locator('aside[aria-label="Documentation chat"]')).toHaveAttribute('inert', '');
        await input.focus();
        await expect(input).not.toBeFocused();
    });
});
