const { expect } = require('@playwright/test');

const BASE_URL = 'http://localhost/mealmate/mealmate-webapp';

async function registerUser(page, { fullName, email, password }) {
  await page.goto('/auth/register.php');
  await page.fill('#full_name', fullName);
  await page.fill('#email', email);
  await page.fill('#password', password);
  await page.fill('#confirm_password', password);
  await page.click('button[type="submit"]');
}

async function getVerificationCode(page) {
  const infoAlert = page.locator('.alert-info');
  if (await infoAlert.isVisible()) {
    const text = await infoAlert.textContent();
    const match = text.match(/(\d{6})/);
    if (match) return match[1];
  }
  return null;
}

async function verifyEmail(page, code) {
  await page.waitForURL('**/verify.php', { timeout: 10000 });
  await page.fill('#code', code);
  await page.click('button[name="verify_code"]');
}

async function loginUser(page, { email, password }) {
  await page.goto('/auth/login.php');
  await page.fill('#email', email);
  await page.fill('#password', password);
  await page.click('button[type="submit"]');
}

async function registerAndLogin(page, { fullName, email, password }) {
  await registerUser(page, { fullName, email, password });
  const code = await getVerificationCode(page);
  if (code) {
    await verifyEmail(page, code);
    await page.waitForURL('**/pages/dashboard.php', { timeout: 10000 });
  }
}

async function addFoodItem(page, {
  name, category, quantity, unit, expiryDate, storageLocation, notes
}) {
  await page.goto('/pages/inventory.php');
  await page.click('[data-modal="addItemModal"]');
  await page.locator('#addItemModal').waitFor({ state: 'visible' });
  await page.fill('#addItemModal #item_name', name);
  await page.selectOption('#addItemModal #category', category);
  await page.fill('#addItemModal #quantity', String(quantity));
  await page.selectOption('#addItemModal #new_unit_select', unit || 'pieces');
  await page.fill('#addItemModal #expiry_date', expiryDate);
  await page.selectOption('#addItemModal #storage_location', storageLocation);
  if (notes) {
    await page.fill('#addItemModal #notes', notes);
  }
  await page.click('#addItemModal button[name="add_item"]');
}

async function screenshot(page, name) {
  const path = `tests/screenshots/${name}.png`;
  await page.screenshot({ path, fullPage: true });
  return path;
}

module.exports = {
  BASE_URL,
  registerUser,
  getVerificationCode,
  verifyEmail,
  loginUser,
  registerAndLogin,
  addFoodItem,
  screenshot,
};
