import { defineConfig } from '@playwright/test'

export default defineConfig({
    testDir: './e2e',
    timeout: 60_000,
    retries: process.env.CI ? 1 : 0,
    projects: [
        {
            name: 'chromium',
            use: { browserName: 'chromium' },
        },
    ],
    use: {
        baseURL: process.env.E2E_BASE_URL ?? 'https://localhost:5173',
        ignoreHTTPSErrors: true,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
})

