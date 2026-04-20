import { test, expect } from '@playwright/test';

async function waitForHydration(page: import('@playwright/test').Page) {
	await page.waitForLoadState('networkidle');
	await page.getByLabel(/Language/i).waitFor();
}

test.describe('Theme toggle', () => {
	test('cycles light → dark → system', async ({ page }) => {
		await page.goto('/');
		await page.evaluate(() => {
			localStorage.setItem('mode-watcher-mode', 'light');
			localStorage.setItem('burnnote-locale', 'en');
		});
		await page.reload();
		await waitForHydration(page);

		const toggle = page.getByRole('button', { name: /Theme:/i });
		await expect(toggle).toBeVisible();
		await expect(page.locator('html')).not.toHaveClass(/\bdark\b/);

		// light → dark
		await toggle.click();
		await expect(page.locator('html')).toHaveClass(/\bdark\b/, { timeout: 2000 });

		// dark → system → light (cycle back)
		await toggle.click();
		await toggle.click();
		await expect(page.locator('html')).not.toHaveClass(/\bdark\b/, { timeout: 2000 });
	});
});
