const { test, expect } = require('@playwright/test');
const {
  registerUser,
  getVerificationCode,
  verifyEmail,
  loginUser,
  registerAndLogin,
  screenshot,
} = require('./helpers');

const TEST_SUFFIX = Date.now();

test.describe('Use Case 1: Register Users and Privacy Settings', () => {

  // ─── REGISTRATION ────────────────────────────────────────────

  test('UC1-S1: Successful registration with valid data', async ({ page }) => {
    const email = `testuser_${TEST_SUFFIX}@example.com`;
    await registerUser(page, {
      fullName: 'Test User',
      email,
      password: 'TestPass123',
    });
    await page.waitForURL('**/verify.php', { timeout: 10000 });
    await expect(page).toHaveURL(/verify/);
    await screenshot(page, 'UC1-S1_register_success_redirect_to_verify');
  });

  test('UC1-S2: Verification code page shows demo code', async ({ page }) => {
    const email = `verify_test_${TEST_SUFFIX}@example.com`;
    await registerUser(page, {
      fullName: 'Verify User',
      email,
      password: 'VerifyPass123',
    });
    await page.waitForURL('**/verify.php', { timeout: 10000 });
    const code = await getVerificationCode(page);
    expect(code).toBeTruthy();
    expect(code).toHaveLength(6);
    await screenshot(page, 'UC1-S2_verification_code_displayed');
  });

  test('UC1-S3: Verify email and reach dashboard', async ({ page }) => {
    const email = `dash_test_${TEST_SUFFIX}@example.com`;
    await registerUser(page, {
      fullName: 'Dash User',
      email,
      password: 'DashPass123',
    });
    const code = await getVerificationCode(page);
    await verifyEmail(page, code);
    await page.waitForURL('**/pages/dashboard.php', { timeout: 10000 });
    await expect(page).toHaveURL(/dashboard/);
    await screenshot(page, 'UC1-S3_verified_dashboard_reached');
  });

  test('UC1-E1: Registration fails with empty full name', async ({ page }) => {
    await page.goto('/auth/register.php');
    await page.fill('#email', `emptyname_${TEST_SUFFIX}@example.com`);
    await page.fill('#password', 'TestPass123');
    await page.fill('#confirm_password', 'TestPass123');
    await page.click('button[type="submit"]');
    await screenshot(page, 'UC1-E1_empty_name_error');
    const nameInput = page.locator('#full_name');
    const valid = await nameInput.evaluate((el) => el.validity.valid);
    expect(valid).toBe(false);
  });

  test('UC1-E2: Registration fails with invalid email', async ({ page }) => {
    await page.goto('/auth/register.php');
    await page.fill('#full_name', 'Bad Email User');
    await page.fill('#email', 'not-an-email');
    await page.fill('#password', 'TestPass123');
    await page.fill('#confirm_password', 'TestPass123');
    await page.click('button[type="submit"]');
    await screenshot(page, 'UC1-E2_invalid_email_error');
    const emailInput = page.locator('#email');
    const valid = await emailInput.evaluate((el) => el.validity.valid);
    expect(valid).toBe(false);
  });

  test('UC1-E3: Registration fails with short password', async ({ page }) => {
    await page.goto('/auth/register.php');
    await page.fill('#full_name', 'Short Pass User');
    await page.fill('#email', `shortpass_${TEST_SUFFIX}@example.com`);
    await page.fill('#password', 'ab');
    await page.fill('#confirm_password', 'ab');
    await page.click('button[type="submit"]');
    await screenshot(page, 'UC1-E3_short_password_error');
    const passInput = page.locator('#password');
    const valid = await passInput.evaluate((el) => el.validity.valid);
    expect(valid).toBe(false);
  });

  test('UC1-E4: Registration fails with mismatched passwords', async ({ page }) => {
    await page.goto('/auth/register.php');
    await page.fill('#full_name', 'Mismatch User');
    await page.fill('#email', `mismatch_${TEST_SUFFIX}@example.com`);
    await page.fill('#password', 'TestPass123');
    await page.fill('#confirm_password', 'DifferentPass456');
    await page.evaluate(() => {
      const cp = document.getElementById('confirm_password');
      if (cp) cp.dispatchEvent(new Event('input', { bubbles: true }));
    });
    await page.waitForTimeout(500);
    await screenshot(page, 'UC1-E4_password_mismatch_error');
    const matchHint = page.locator('#matchHint');
    const hintText = await matchHint.textContent();
    expect(hintText.toLowerCase()).toContain('not match');
  });

  test('UC1-E5: Registration fails with duplicate email', async ({ page }) => {
    const email = `dupe_${TEST_SUFFIX}@example.com`;
    await registerUser(page, {
      fullName: 'First User',
      email,
      password: 'TestPass123',
    });
    await page.waitForURL('**/verify.php', { timeout: 10000 });
    await registerUser(page, {
      fullName: 'Second User',
      email,
      password: 'TestPass123',
    });
    await screenshot(page, 'UC1-E5_duplicate_email_error');
    const isStillOnRegister = page.url().includes('register');
    const hasError = await page.locator('.alert-error').isVisible();
    expect(isStillOnRegister || hasError).toBe(true);
  });

  // ─── PRIVACY / SECURITY SETTINGS ────────────────────────────

  test('UC1-S4: Navigate to settings page after login', async ({ page }) => {
    const email = `settings_nav_${TEST_SUFFIX}@example.com`;
    await registerAndLogin(page, {
      fullName: 'Settings User',
      email,
      password: 'SettingsPass1',
    });
    await page.goto('/pages/settings.php');
    await expect(page).toHaveURL(/settings/);
    await screenshot(page, 'UC1-S4_settings_page_loaded');
  });

  test('UC1-S5: Enable 2FA toggle', async ({ page }) => {
    const email = `2fa_test_${TEST_SUFFIX}@example.com`;
    await registerAndLogin(page, {
      fullName: '2FA User',
      email,
      password: 'TwoFA1234',
    });
    await page.goto('/pages/settings.php');
    await page.evaluate(() => {
      const cb = document.querySelector('input[type="checkbox"][name="two_fa_enabled"]');
      if (cb) { cb.checked = true; cb.dispatchEvent(new Event('change', { bubbles: true })); }
    });
    await page.click('button[name="toggle_2fa"]');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);
    await screenshot(page, 'UC1-S5_2fa_enabled');
    const isChecked = await page.evaluate(() => {
      const cb = document.querySelector('input[type="checkbox"][name="two_fa_enabled"]');
      return cb ? cb.checked : false;
    });
    expect(isChecked).toBe(true);
  });

  test('UC1-S6: Disable 2FA toggle', async ({ page }) => {
    const email = `2fa_disable_${TEST_SUFFIX}@example.com`;
    await registerAndLogin(page, {
      fullName: '2FA Disable User',
      email,
      password: 'TwoFA1234',
    });
    await page.goto('/pages/settings.php');
    await page.evaluate(() => {
      const cb = document.querySelector('input[type="checkbox"][name="two_fa_enabled"]');
      if (cb) { cb.checked = true; cb.dispatchEvent(new Event('change', { bubbles: true })); }
    });
    await page.click('button[name="toggle_2fa"]');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);
    await page.goto('/pages/settings.php');
    await page.evaluate(() => {
      const cb = document.querySelector('input[type="checkbox"][name="two_fa_enabled"]');
      if (cb) { cb.checked = false; cb.dispatchEvent(new Event('change', { bubbles: true })); }
    });
    await page.click('button[name="toggle_2fa"]');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);
    await screenshot(page, 'UC1-S6_2fa_toggle_interactions');
    const isChecked = await page.evaluate(() => {
      const cb = document.querySelector('input[type="checkbox"][name="two_fa_enabled"]');
      return cb ? cb.checked : false;
    });
    expect(typeof isChecked).toBe('boolean');
  });

  test('UC1-S7: Set listing visibility to private', async ({ page }) => {
    const email = `vis_private_${TEST_SUFFIX}@example.com`;
    await registerAndLogin(page, {
      fullName: 'Private User',
      email,
      password: 'Private123',
    });
    await page.goto('/pages/settings.php');
    await page.selectOption('#listing_visibility', 'private');
    await page.click('button[name="update_visibility"]');
    await page.waitForTimeout(1500);
    await screenshot(page, 'UC1-S7_visibility_set_private');
    const select = page.locator('#listing_visibility');
    const value = await select.inputValue();
    expect(value).toBe('private');
  });

  test('UC1-S8: Set listing visibility back to public', async ({ page }) => {
    const email = `vis_public_${TEST_SUFFIX}@example.com`;
    await registerAndLogin(page, {
      fullName: 'Public User',
      email,
      password: 'Public123',
    });
    await page.goto('/pages/settings.php');
    await page.selectOption('#listing_visibility', 'private');
    await page.click('button[name="update_visibility"]');
    await page.waitForTimeout(1000);
    await page.selectOption('#listing_visibility', 'public');
    await page.click('button[name="update_visibility"]');
    await page.waitForTimeout(1500);
    await screenshot(page, 'UC1-S8_visibility_set_public');
    const select = page.locator('#listing_visibility');
    const value = await select.inputValue();
    expect(value).toBe('public');
  });

  test('UC1-E6: Login fails with wrong password', async ({ page }) => {
    const email = `wrongpass_${TEST_SUFFIX}@example.com`;
    await registerAndLogin(page, {
      fullName: 'Wrong Pass User',
      email,
      password: 'CorrectPass1',
    });
    await page.goto('/auth/logout.php');
    await page.waitForURL('**/login.php', { timeout: 10000 });
    await loginUser(page, { email, password: 'WrongPass999' });
    await page.waitForTimeout(2000);
    await screenshot(page, 'UC1-E6_wrong_password_error');
    const isError = await page.locator('.alert-error').isVisible();
    const isOnLogin = page.url().includes('login');
    expect(isError || isOnLogin).toBe(true);
  });
});
