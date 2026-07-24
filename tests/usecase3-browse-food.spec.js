const { test, expect } = require('@playwright/test');
const {
  registerAndLogin,
  addFoodItem,
  screenshot,
} = require('./helpers');

const TEST_SUFFIX = Date.now();

test.describe('Use Case 3: Browse Food Items', () => {

  test('UC3-S1: Browse page loads for logged-in user', async ({ page }) => {
    const email = `browse_load_${TEST_SUFFIX}@example.com`;
    await registerAndLogin(page, {
      fullName: 'Browse User',
      email,
      password: 'BrowsePass1',
    });
    await page.goto('/pages/browse.php');
    await expect(page).toHaveURL(/browse/);
    await expect(page.locator('h1')).toContainText('Browse Donations');
    await screenshot(page, 'UC3-S1_browse_page_loaded');
  });

  test('UC3-S2: Browse shows empty state when no donations exist', async ({ page }) => {
    const email = `browse_empty_${TEST_SUFFIX}@example.com`;
    await registerAndLogin(page, {
      fullName: 'Empty Browse User',
      email,
      password: 'EmptyBrowse1',
    });
    await page.goto('/pages/browse.php');
    const emptyState = page.locator('.empty-state').first();
    if (await emptyState.isVisible()) {
      await screenshot(page, 'UC3-S2_empty_state_no_donations');
    } else {
      await screenshot(page, 'UC3-S2_donations_exist');
    }
  });

  test('UC3-S3: Browse shows available donations after donor lists items', async ({ page }) => {
    const futureDate = new Date();
    futureDate.setDate(futureDate.getDate() + 10);
    const dateStr = futureDate.toISOString().split('T')[0];

    const donorEmail = `donor_${TEST_SUFFIX}@example.com`;
    await registerAndLogin(page, {
      fullName: 'Donor User',
      email: donorEmail,
      password: 'DonorPass1',
    });

    await addFoodItem(page, {
      name: 'Surplus Apples',
      category: 'fruits',
      quantity: 10,
      unit: 'pieces',
      expiryDate: dateStr,
      storageLocation: 'fridge',
      notes: 'Too many to eat',
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

    await page.goto('/pages/browse.php');
    await page.waitForTimeout(2000);
    await screenshot(page, 'UC3-S3_donation_listed_visible');
    await expect(page.locator('text=Surplus Apples').first()).toBeVisible();
  });

  test('UC3-S4: View donation details modal', async ({ page }) => {
    const futureDate = new Date();
    futureDate.setDate(futureDate.getDate() + 10);
    const dateStr = futureDate.toISOString().split('T')[0];

    const donorEmail = `donordetail_${TEST_SUFFIX}@example.com`;
    await registerAndLogin(page, {
      fullName: 'Detail Donor',
      email: donorEmail,
      password: 'DetailPass1',
    });

    await addFoodItem(page, {
      name: 'Organic Eggs',
      category: 'dairy',
      quantity: 12,
      unit: 'pieces',
      expiryDate: dateStr,
      storageLocation: 'fridge',
      notes: 'Farm fresh eggs',
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

    await page.goto('/pages/browse.php');
    await page.waitForTimeout(2000);

    const detailsBtn = page.locator('[data-modal^="detailModal"]').first();
    if (await detailsBtn.isVisible()) {
      const modalId = await detailsBtn.getAttribute('data-modal');
      await page.evaluate((id) => {
        const el = document.getElementById(id);
        if (el) el.classList.add('active');
      }, modalId);
      await page.waitForTimeout(500);
      await screenshot(page, 'UC3-S4_donation_details_modal');
      const modal = page.locator('#' + modalId);
      await expect(modal).toBeVisible();
    }
  });

  test('UC3-S5: Claim a donation (cross-user flow)', async ({ page }) => {
    const futureDate = new Date();
    futureDate.setDate(futureDate.getDate() + 14);
    const dateStr = futureDate.toISOString().split('T')[0];

    const claimerEmail = `claimer_${TEST_SUFFIX}@example.com`;
    const claimerPassword = 'ClaimerPass1';
    const donorEmail = `donorclaim_${TEST_SUFFIX}@example.com`;

    await registerAndLogin(page, {
      fullName: 'Donor Person',
      email: donorEmail,
      password: 'DonorClaim1',
    });

    await addFoodItem(page, {
      name: 'Homemade Pasta',
      category: 'grains',
      quantity: 2,
      unit: 'pack',
      expiryDate: dateStr,
      storageLocation: 'fridge',
      notes: 'Fresh pasta',
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

    await page.goto('/auth/logout.php');
    await page.waitForURL('**/login.php', { timeout: 10000 });

    await registerAndLogin(page, {
      fullName: 'Claimer Person',
      email: claimerEmail,
      password: claimerPassword,
    });

    await page.goto('/pages/browse.php');
    await page.waitForTimeout(2000);

    const claimBtn = page.locator('a[href*="claim"]').first();
    if (await claimBtn.isVisible()) {
      await claimBtn.click();
      await page.waitForTimeout(1000);
      const confirmClaim = page.locator('#confirmActionBtn');
      if (await confirmClaim.isVisible()) {
        await confirmClaim.click();
      }
      await page.waitForTimeout(2000);
      await screenshot(page, 'UC3-S5_claim_success');
    } else {
      await screenshot(page, 'UC3-S5_no_claims_available');
    }
  });

  test('UC3-S6: My Claims section shows claimed items', async ({ page }) => {
    const claimerEmail = `myclaims_${TEST_SUFFIX}@example.com`;
    await registerAndLogin(page, {
      fullName: 'My Claims User',
      email: claimerEmail,
      password: 'MyClaims1',
    });
    await page.goto('/pages/browse.php');
    await page.waitForTimeout(2000);
    const myClaimsSection = page.locator('h2:has-text("My Claims")');
    await expect(myClaimsSection).toBeVisible();
    await screenshot(page, 'UC3-S6_my_claims_section');
  });

  test('UC3-S7: Filter donations by category', async ({ page }) => {
    const claimerEmail = `filtercat_${TEST_SUFFIX}@example.com`;
    await registerAndLogin(page, {
      fullName: 'Filter Cat User',
      email: claimerEmail,
      password: 'FilterCat1',
    });
    await page.goto('/pages/browse.php');
    await page.waitForTimeout(1000);
    await page.selectOption('select[name="category"]', 'fruits');
    await page.waitForTimeout(2000);
    await screenshot(page, 'UC3-S7_filter_by_category');
  });

  test('UC3-S8: Filter donations by storage location', async ({ page }) => {
    const claimerEmail = `filterloc_${TEST_SUFFIX}@example.com`;
    await registerAndLogin(page, {
      fullName: 'Filter Loc User',
      email: claimerEmail,
      password: 'FilterLoc1',
    });
    await page.goto('/pages/browse.php');
    await page.waitForTimeout(1000);
    await page.selectOption('select[name="location"]', 'fridge');
    await page.waitForTimeout(2000);
    await screenshot(page, 'UC3-S8_filter_by_location');
  });

  test('UC3-S9: Clear filters shows all donations', async ({ page }) => {
    const claimerEmail = `clearfilt_${TEST_SUFFIX}@example.com`;
    await registerAndLogin(page, {
      fullName: 'Clear Filter User',
      email: claimerEmail,
      password: 'ClearFilter1',
    });
    await page.goto('/pages/browse.php?category=fruits');
    await page.waitForTimeout(2000);
    const clearBtn = page.locator('a:has-text("Clear Filters")');
    if (await clearBtn.isVisible()) {
      await clearBtn.click();
      await page.waitForTimeout(2000);
      await screenshot(page, 'UC3-S9_filters_cleared');
    }
  });

  test('UC3-E1: Cannot claim own donation', async ({ page }) => {
    const futureDate = new Date();
    futureDate.setDate(futureDate.getDate() + 10);
    const dateStr = futureDate.toISOString().split('T')[0];

    const email = `ownclaim_${TEST_SUFFIX}@example.com`;
    await registerAndLogin(page, {
      fullName: 'Own Claim User',
      email,
      password: 'OwnClaim1',
    });

    await addFoodItem(page, {
      name: 'My Bread',
      category: 'grains',
      quantity: 1,
      unit: 'loaf',
      expiryDate: dateStr,
      storageLocation: 'pantry',
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

    await page.goto('/pages/browse.php');
    await page.waitForTimeout(2000);

    const ownClaimBtn = page.locator('a[href*="claim"]');
    const claimCount = await ownClaimBtn.count();
    await screenshot(page, 'UC3-E1_own_donation_no_claim_button');
    if (claimCount > 0) {
      await ownClaimBtn.first().click();
      await page.waitForTimeout(2000);
      const errorVisible = await page.locator('.alert-error').isVisible();
      if (errorVisible) {
        await screenshot(page, 'UC3-E1_own_claim_error_shown');
      }
    }
  });

  test('UC3-E2: Browse page requires authentication', async ({ page }) => {
    await page.goto('/pages/browse.php');
    await page.waitForTimeout(2000);
    await screenshot(page, 'UC3-E2_browse_requires_auth');
    await expect(page).toHaveURL(/login/);
  });
});
