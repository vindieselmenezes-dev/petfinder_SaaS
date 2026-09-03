const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
    testDir: './tests/e2e',
    timeout: 30_000,
    use: {
        baseURL: process.env.E2E_BASE_URL || 'http://localhost/petfinder-SaaS/public',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure'
    },
    reporter: [['list'], ['html', { open: 'never' }]]
});
