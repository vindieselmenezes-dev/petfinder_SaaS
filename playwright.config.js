const { defineConfig } = require('@playwright/test');

const baseUrl = (process.env.E2E_BASE_URL || 'http://localhost/petfinder-SaaS/public')
    .replace(/\/?$/, '/');

module.exports = defineConfig({
    testDir: './tests/e2e',
    timeout: 30_000,
    use: {
        baseURL: baseUrl,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure'
    },
    reporter: [['list'], ['html', { open: 'never' }]]
});
