import { test, expect } from '@playwright/test'

test('login and view items list', async ({ page }) => {
    await page.goto('/login')

    // Ensure CSRF cookies are set for the browser context
    const csrf = await page.request.get('/api/csrf-cookie')
    expect(csrf.status(), 'csrf-cookie status').toBe(204)

    await page.getByLabel('Email').fill('test@example.com')
    await page.getByLabel('Password').fill('password')
    const loginResponse = page.waitForResponse((r) => r.url().includes('/api/auth/login'))
    await page.getByRole('button', { name: 'Sign in' }).click()
    const res = await loginResponse
    expect(res.status(), 'login status').toBe(200)

    await expect(page).toHaveURL(/\/items/)
    await expect(page.getByText('Items', { exact: true })).toBeVisible()

    // Load items (seeded on backend)
    await expect(page.getByRole('button', { name: /Load more|No more/ })).toBeVisible()
})

