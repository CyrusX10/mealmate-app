<?php
$page_title = 'Stop food going to waste';
require_once 'includes/header.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/pages/dashboard.php');
    exit;
}

require_once 'includes/navbar.php';
?>

<section class="hero">
    <div class="hero-copy">
        <span class="eyebrow"><i class="fa-solid fa-leaf"></i> Household food waste, solved</span>
        <h1>Your fridge already knows<br>what tonight's dinner should be.</h1>
        <p class="hero-lede">MealMate tracks what you've bought, warns you before it expires, and helps you plan meals — or pass surplus food on — before it hits the bin.</p>
        <div class="hero-actions">
            <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-primary btn-lg">Get started free <i class="fa-solid fa-arrow-right"></i></a>
            <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-ghost btn-lg">I already have an account</a>
        </div>
    </div>

    <div class="hero-receipt">
        <div class="receipt receipt-standalone" aria-hidden="true">
            <div class="receipt-title">WEEKLY IMPACT</div>
            <div class="receipt-row"><span>Items tracked</span><span>18</span></div>
            <div class="receipt-row"><span>Saved from waste</span><span>4.2 kg</span></div>
            <div class="receipt-row"><span>Donations made</span><span>3</span></div>
            <div class="receipt-divider"></div>
            <div class="receipt-row receipt-total"><span>Meals rescued</span><span>11</span></div>
        </div>
    </div>
</section>

<section class="feature-grid">
    <div class="feature-card">
        <div class="feature-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
        <h3>Manage your inventory</h3>
        <p>Log what's in the fridge, freezer, and pantry — quantity, category, and expiry date included.</p>
    </div>
    <div class="feature-card">
        <div class="feature-icon"><i class="fa-solid fa-bell"></i></div>
        <h3>Get expiry alerts</h3>
        <p>Automatic notifications before items go bad, so nothing quietly rots in the back of a shelf.</p>
    </div>
    <div class="feature-card">
        <div class="feature-icon"><i class="fa-solid fa-hand-holding-heart"></i></div>
        <h3>Donate the surplus</h3>
        <p>Turn items you won't use into a donation listing your community can browse and claim.</p>
    </div>
    <div class="feature-card">
        <div class="feature-icon"><i class="fa-solid fa-utensils"></i></div>
        <h3>Plan your week</h3>
        <p>Build a weekly meal plan that prioritises ingredients closest to their expiry date.</p>
    </div>
    <div class="feature-card">
        <div class="feature-icon"><i class="fa-solid fa-chart-simple"></i></div>
        <h3>See your impact</h3>
        <p>Track food saved and donations made over time, with a breakdown by category.</p>
    </div>
    <div class="feature-card">
        <div class="feature-icon"><i class="fa-solid fa-shield-halved"></i></div>
        <h3>Stay in control</h3>
        <p>Two-factor authentication and configurable listing visibility keep your account and data private.</p>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
