const { test, expect } = require('@playwright/test');

test('TC-UC1-01: Valid registration form loads', async ({ page }) => {
  await page.goto('http://localhost/mealmate/mealmate-webapp/auth/register.php');
  await expect(page).toHaveTitle(/MealMate/);
});

test('TC-UC1-02: Empty form shows validation error', async ({ page }) => {
  await page.goto('http://localhost/mealmate/mealmate-webapp/auth/register.php');
  await page.click('button[type="submit"]');
  const errorVisible = await page.locator('input:invalid').count();
  expect(errorVisible).toBeGreaterThan(0);
});

test('TC-UC1-03: Valid registration submission', async ({ page }) => {
  await page.goto('http://localhost/mealmate/mealmate-webapp/auth/register.php');
  await page.fill('input[name="full_name"]', 'Playwright Test');
  await page.fill('input[name="email"]', 'playwright@test.com');
  await page.fill('input[name="password"]', 'Test1234');
  await page.fill('input[name="confirm_password"]', 'Test1234');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(2000);
  const url = page.url();
  expect(url).toContain('verify');
});

test('TC-UC1-04: Login page loads correctly', async ({ page }) => {
  await page.goto('http://localhost/mealmate/mealmate-webapp/auth/login.php');
  await expect(page.locator('input[name="email"]')).toBeVisible();
  await expect(page.locator('input[name="password"]')).toBeVisible();
});

test('TC-UC1-05: Wrong password shows error', async ({ page }) => {
  await page.goto('http://localhost/mealmate/mealmate-webapp/auth/login.php');
  await page.fill('input[name="email"]', 'test@test.com');
  await page.fill('input[name="password"]', 'WrongPass99');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(1000);
  const pageContent = await page.content();
  expect(pageContent).toContain('Invalid');
});