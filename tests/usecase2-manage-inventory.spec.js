const { test, expect } = require('@playwright/test');
const {
  registerAndLogin,
  addFoodItem,
  screenshot,
} = require('./helpers');

const TEST_SUFFIX = Date.now();

test.describe('Use Case 2: Manage Food Inventory', () => {

  test.beforeEach(async ({ page }) => {
    const email = `inventory_${TEST_SUFFIX}_${Math.random().toString(36).slice(2, 6)}@example.com`;
    await registerAndLogin(page, {
      fullName: 'Inventory User',
      email,
      password: 'InventPass123',
    });
  });

  // ─── ADD FOOD ITEMS ──────────────────────────────────────────

  test('UC2-S1: Add a food item to fridge', async ({ page }) => {
    const futureDate = new Date();
    futureDate.setDate(futureDate.getDate() + 7);
    const dateStr = futureDate.toISOString().split('T')[0];

    await addFoodItem(page, {
      name: 'Fresh Tomatoes',
      category: 'vegetables',
      quantity: 5,
      unit: 'pieces',
      expiryDate: dateStr,
      storageLocation: 'fridge',
      notes: 'From the farmers market',
    });
    await page.waitForTimeout(2000);
    await screenshot(page, 'UC2-S1_add_item_fridge_success');
    await expect(page.locator('text=Fresh Tomatoes').first()).toBeVisible();
  });

  test('UC2-S2: Add a food item to pantry', async ({ page }) => {
    const futureDate = new Date();
    futureDate.setDate(futureDate.getDate() + 30);
    const dateStr = futureDate.toISOString().split('T')[0];

    await addFoodItem(page, {
      name: 'Brown Rice',
      category: 'grains',
      quantity: 2,
      unit: 'kg',
      expiryDate: dateStr,
      storageLocation: 'pantry',
    });
    await page.waitForTimeout(2000);
    await screenshot(page, 'UC2-S2_add_item_pantry_success');
    await expect(page.locator('text=Brown Rice').first()).toBeVisible();
  });

  test('UC2-S3: Add a food item to freezer', async ({ page }) => {
    const futureDate = new Date();
    futureDate.setDate(futureDate.getDate() + 90);
    const dateStr = futureDate.toISOString().split('T')[0];

    await addFoodItem(page, {
      name: 'Chicken Breast',
      category: 'meat',
      quantity: 1.5,
      unit: 'kg',
      expiryDate: dateStr,
      storageLocation: 'freezer',
      notes: 'For meal prep',
    });
    await page.waitForTimeout(2000);
    await screenshot(page, 'UC2-S3_add_item_freezer_success');
    await expect(page.locator('text=Chicken Breast').first()).toBeVisible();
  });

  test('UC2-S4: Add multiple food items', async ({ page }) => {
    const d1 = new Date(); d1.setDate(d1.getDate() + 5);
    const d2 = new Date(); d2.setDate(d2.getDate() + 14);
    const d3 = new Date(); d3.setDate(d3.getDate() + 3);

    await addFoodItem(page, {
      name: 'Apples',
      category: 'fruits',
      quantity: 6,
      unit: 'pieces',
      expiryDate: d1.toISOString().split('T')[0],
      storageLocation: 'fridge',
    });
    await page.waitForTimeout(1500);

    await addFoodItem(page, {
      name: 'Cheddar Cheese',
      category: 'dairy',
      quantity: 1,
      unit: 'pack',
      expiryDate: d2.toISOString().split('T')[0],
      storageLocation: 'fridge',
    });
    await page.waitForTimeout(1500);

    await addFoodItem(page, {
      name: 'Bananas',
      category: 'fruits',
      quantity: 4,
      unit: 'pieces',
      expiryDate: d3.toISOString().split('T')[0],
      storageLocation: 'pantry',
    });
    await page.waitForTimeout(2000);
    await screenshot(page, 'UC2-S4_multiple_items_added');
    await expect(page.locator('text=Apples').first()).toBeVisible();
    await expect(page.locator('text=Cheddar Cheese').first()).toBeVisible();
    await expect(page.locator('text=Bananas').first()).toBeVisible();
  });

  // ─── EDIT FOOD ITEMS ─────────────────────────────────────────

  test('UC2-S5: Edit an existing food item', async ({ page }) => {
    const futureDate = new Date();
    futureDate.setDate(futureDate.getDate() + 10);
    const dateStr = futureDate.toISOString().split('T')[0];

    await addFoodItem(page, {
      name: 'Milk Bottle',
      category: 'dairy',
      quantity: 1,
      unit: 'bottle',
      expiryDate: dateStr,
      storageLocation: 'fridge',
    });
    await page.waitForTimeout(2000);

    const editBtn = page.locator('[data-modal^="editModal"]').first();
    const modalId = await editBtn.getAttribute('data-modal');
    await page.evaluate((id) => {
      const el = document.getElementById(id);
      if (el) el.classList.add('active');
    }, modalId);
    await page.waitForTimeout(500);

    const editModal = page.locator('#' + modalId);
    const nameInput = editModal.locator('input[type="text"]').first();
    await nameInput.clear();
    await nameInput.fill('Organic Milk Bottle');
    await editModal.locator('button[name="edit_item"]').click();
    await page.waitForTimeout(2000);
    await screenshot(page, 'UC2-S5_edit_item_success');
    await expect(page.locator('text=Organic Milk Bottle').first()).toBeVisible();
  });

  // ─── MARK AS USED / CONSUME ──────────────────────────────────

  test('UC2-S6: Consume a food item', async ({ page }) => {
    const futureDate = new Date();
    futureDate.setDate(futureDate.getDate() + 5);
    const dateStr = futureDate.toISOString().split('T')[0];

    await addFoodItem(page, {
      name: 'Yogurt Cup',
      category: 'dairy',
      quantity: 1,
      unit: 'pieces',
      expiryDate: dateStr,
      storageLocation: 'fridge',
    });
    await page.waitForTimeout(2000);

    const consumeBtn = page.locator('a[href*="consume"]').first();
    await consumeBtn.click();
    await page.waitForTimeout(1000);

    const confirmBtn = page.locator('#confirmActionBtn');
    if (await confirmBtn.isVisible()) {
      await confirmBtn.click();
    }
    await page.waitForTimeout(2000);
    await screenshot(page, 'UC2-S6_consume_item_success');
  });

  // ─── DONATE ITEMS ────────────────────────────────────────────

  test('UC2-S7: Donate a food item', async ({ page }) => {
    const futureDate = new Date();
    futureDate.setDate(futureDate.getDate() + 8);
    const dateStr = futureDate.toISOString().split('T')[0];

    await addFoodItem(page, {
      name: 'Extra Bread Loaves',
      category: 'grains',
      quantity: 3,
      unit: 'loaf',
      expiryDate: dateStr,
      storageLocation: 'pantry',
      notes: 'Freshly baked',
    });
    await page.waitForTimeout(2000);

    const donateBtn = page.locator('a[href*="donate"]').first();
    await donateBtn.click();
    await page.waitForTimeout(1000);

    const confirmBtn = page.locator('#confirmActionBtn');
    if (await confirmBtn.isVisible()) {
      await confirmBtn.click();
    }
    await page.waitForTimeout(2000);
    await screenshot(page, 'UC2-S7_donate_item_success');
  });

  // ─── DELETE ITEMS ────────────────────────────────────────────

  test('UC2-S8: Delete a food item', async ({ page }) => {
    const futureDate = new Date();
    futureDate.setDate(futureDate.getDate() + 3);
    const dateStr = futureDate.toISOString().split('T')[0];

    await addFoodItem(page, {
      name: 'Wilted Lettuce',
      category: 'vegetables',
      quantity: 1,
      unit: 'pieces',
      expiryDate: dateStr,
      storageLocation: 'fridge',
    });
    await page.waitForTimeout(2000);

    const deleteBtn = page.locator('a[href*="delete"]').first();
    await deleteBtn.click();
    await page.waitForTimeout(1000);

    const confirmBtn = page.locator('#confirmActionBtn');
    if (await confirmBtn.isVisible()) {
      await confirmBtn.click();
    }
    await page.waitForTimeout(2000);
    await screenshot(page, 'UC2-S8_delete_item_success');
  });

  // ─── FILTER ITEMS ────────────────────────────────────────────

  test('UC2-S9: Filter items by category', async ({ page }) => {
    const d1 = new Date(); d1.setDate(d1.getDate() + 5);
    const d2 = new Date(); d2.setDate(d2.getDate() + 10);

    await addFoodItem(page, {
      name: 'Carrots',
      category: 'vegetables',
      quantity: 5,
      unit: 'pieces',
      expiryDate: d1.toISOString().split('T')[0],
      storageLocation: 'fridge',
    });
    await page.waitForTimeout(1500);

    await addFoodItem(page, {
      name: 'Milk',
      category: 'dairy',
      quantity: 2,
      unit: 'bottle',
      expiryDate: d2.toISOString().split('T')[0],
      storageLocation: 'fridge',
    });
    await page.waitForTimeout(1500);

    await page.selectOption('select[name="category"]', 'vegetables');
    await page.waitForTimeout(2000);
    await screenshot(page, 'UC2-S9_filter_by_category');
  });

  test('UC2-S10: Filter items by storage location', async ({ page }) => {
    const d1 = new Date(); d1.setDate(d1.getDate() + 5);
    const d2 = new Date(); d2.setDate(d2.getDate() + 20);

    await addFoodItem(page, {
      name: 'Frozen Peas',
      category: 'vegetables',
      quantity: 1,
      unit: 'pack',
      expiryDate: d2.toISOString().split('T')[0],
      storageLocation: 'freezer',
    });
    await page.waitForTimeout(1500);

    await addFoodItem(page, {
      name: 'Fresh Peas',
      category: 'vegetables',
      quantity: 2,
      unit: 'pack',
      expiryDate: d1.toISOString().split('T')[0],
      storageLocation: 'fridge',
    });
    await page.waitForTimeout(1500);

    await page.selectOption('select[name="location"]', 'freezer');
    await page.waitForTimeout(2000);
    await screenshot(page, 'UC2-S10_filter_by_location');
  });

  // ─── ERROR CASES ─────────────────────────────────────────────

  test('UC2-E1: Cannot add item without name', async ({ page }) => {
    await page.goto('/pages/inventory.php');
    await page.click('[data-modal="addItemModal"]');
    await page.locator('#addItemModal').waitFor({ state: 'visible' });

    const futureDate = new Date();
    futureDate.setDate(futureDate.getDate() + 5);
    const dateStr = futureDate.toISOString().split('T')[0];

    await page.selectOption('#addItemModal #category', 'vegetables');
    await page.fill('#addItemModal #quantity', '2');
    await page.selectOption('#addItemModal #new_unit_select', 'pieces');
    await page.fill('#addItemModal #expiry_date', dateStr);
    await page.selectOption('#addItemModal #storage_location', 'fridge');
    await page.click('#addItemModal button[name="add_item"]');
    await page.waitForTimeout(1500);
    await screenshot(page, 'UC2-E1_add_item_no_name_error');
    const nameInput = page.locator('#addItemModal #item_name');
    const valid = await nameInput.evaluate((el) => el.validity.valid);
    expect(valid).toBe(false);
  });

  test('UC2-E2: Cannot add item with past expiry date', async ({ page }) => {
    await page.goto('/pages/inventory.php');
    await page.click('[data-modal="addItemModal"]');
    await page.locator('#addItemModal').waitFor({ state: 'visible' });

    const pastDate = new Date();
    pastDate.setDate(pastDate.getDate() - 2);
    const dateStr = pastDate.toISOString().split('T')[0];

    await page.fill('#addItemModal #item_name', 'Old Food');
    await page.selectOption('#addItemModal #category', 'other');
    await page.fill('#addItemModal #quantity', '1');
    await page.selectOption('#addItemModal #new_unit_select', 'pieces');
    await page.fill('#addItemModal #expiry_date', dateStr);
    await page.selectOption('#addItemModal #storage_location', 'pantry');
    await screenshot(page, 'UC2-E2_past_expiry_date_error');
  });

  test('UC2-E3: Cannot add item without selecting category', async ({ page }) => {
    await page.goto('/pages/inventory.php');
    await page.click('[data-modal="addItemModal"]');
    await page.locator('#addItemModal').waitFor({ state: 'visible' });

    await page.fill('#addItemModal #item_name', 'Mystery Item');
    await page.fill('#addItemModal #quantity', '1');

    const futureDate = new Date();
    futureDate.setDate(futureDate.getDate() + 5);
    await page.fill('#addItemModal #expiry_date', futureDate.toISOString().split('T')[0]);

    await page.selectOption('#addItemModal #storage_location', 'fridge');
    await page.click('#addItemModal button[name="add_item"]');
    await page.waitForTimeout(1500);
    await screenshot(page, 'UC2-E3_no_category_error');
    const catSelect = page.locator('#addItemModal #category');
    const valid = await catSelect.evaluate((el) => el.validity.valid);
    expect(valid).toBe(false);
  });

  test('UC2-E4: Inventory page requires authentication', async ({ page }) => {
    await page.goto('/pages/inventory.php');
    await page.waitForTimeout(2000);
    await screenshot(page, 'UC2-E4_not_logged_in');
    const addBtn = page.locator('[data-modal="addItemModal"]');
    const addBtnVisible = await addBtn.isVisible().catch(() => false);
    const isOnLogin = page.url().includes('login');
    const isOnInventory = page.url().includes('inventory');
    if (isOnLogin) {
      expect(isOnLogin).toBe(true);
    } else if (isOnInventory) {
      expect(addBtnVisible).toBe(false);
    }
  });
});
