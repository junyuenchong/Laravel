import { defineConfig } from '@playwright/test'

// Force HTTP in local/dev to avoid SSL protocol errors
const isCI = !!process.env.CI
const baseURL =
    process.env.E2E_BASE_URL ||
    (isCI ? 'https://localhost:5173' : 'http://localhost:5173')

export default defineConfig({
    testDir: './e2e',
    timeout: 60_000,
    retries: isCI ? 1 : 0,
    projects: [
        {
            name: 'chromium',
            use: { browserName: 'chromium' },
        },
    ],
    use: {
        baseURL,
        ignoreHTTPSErrors: true, // Needed for HTTPS in CI
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
})
