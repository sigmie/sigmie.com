import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/e2e',
    timeout: 30_000,
    expect: { timeout: 5_000 },
    fullyParallel: false,
    workers: 1,
    reporter: [['list']],
    use: {
        baseURL: process.env.PLAYWRIGHT_BASE_URL || 'http://127.0.0.1:8765',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        viewport: { width: 1440, height: 900 },
        ignoreHTTPSErrors: true,
    },
    projects: [
        {
            name: 'desktop-chromium',
            use: { ...devices['Desktop Chrome'], viewport: { width: 1440, height: 900 } },
            testIgnore: /mobile\.spec\.js/,
        },
        {
            name: 'mobile-chromium',
            use: { ...devices['Pixel 7'] },
            testMatch: /(mobile|screenshots)\.spec\.js/,
        },
    ],
});
