import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright Configuration for Automated Documentation Screenshots.
 * Focuses strictly on Chromium for speed, efficiency, and zero unnecessary browser downloads.
 */
export default defineConfig({
    testDir: './tests/e2e',
    timeout: 60 * 1000,
    expect: {
        timeout: 10 * 1000,
    },
    fullyParallel: false,
    workers: 1,
    reporter: [['list']],
    use: {
        baseURL: process.env.DOCS_BASE_URL || 'http://127.0.0.1:8000',
        headless: true,
        ignoreHTTPSErrors: true,
        locale: 'id-ID',
        timezoneId: 'Asia/Makassar', // WITA UTC+8
    },
    projects: [
        {
            name: 'documentation-generator',
            use: {
                ...devices['Desktop Chrome'],
                viewport: { width: 1440, height: 900 },
                deviceScaleFactor: 2, // 2x retina clarity for crystal sharp manual screenshots
            },
        },
    ],
    webServer: {
        command: 'php artisan serve --port=8000',
        url: 'http://127.0.0.1:8000/login',
        reuseExistingServer: true,
        timeout: 120 * 1000,
    },
});

