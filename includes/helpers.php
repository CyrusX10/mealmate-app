<?php
/**
 * Small presentational helpers shared by any page that renders a food item
 * card (Dashboard "Recent Items", Inventory, Browse Donations).
 */

function category_icon(string $category): string {
    $icons = [
        'fruits'     => 'fa-apple-whole',
        'vegetables' => 'fa-carrot',
        'dairy'      => 'fa-cheese',
        'meat'       => 'fa-drumstick-bite',
        'grains'     => 'fa-wheat-awn',
        'other'      => 'fa-box-open',
    ];
    return $icons[$category] ?? 'fa-box-open';
}

function storage_icon(string $location): string {
    $icons = [
        'fridge'  => 'fa-snowflake',
        'freezer' => 'fa-icicles',
        'pantry'  => 'fa-door-closed',
    ];
    return $icons[$location] ?? 'fa-box';
}

/**
 * Returns ['class' => expiry-safe|expiry-warning|expiry-danger, 'label' => ...]
 * for a given number of days left until expiry.
 */
function expiry_badge(int $days_left): array {
    if ($days_left < 0) {
        return ['class' => 'expiry-danger', 'label' => 'Expired', 'icon' => 'fa-triangle-exclamation'];
    }
    if ($days_left === 0) {
        return ['class' => 'expiry-warning', 'label' => 'Today', 'icon' => 'fa-clock'];
    }
    if ($days_left <= 3) {
        return ['class' => 'expiry-warning', 'label' => $days_left . ' day' . ($days_left === 1 ? '' : 's') . ' left', 'icon' => 'fa-clock'];
    }
    return ['class' => 'expiry-safe', 'label' => $days_left . ' days left', 'icon' => 'fa-circle-check'];
}

/** Preset unit options shown in the Add/Edit Food Item forms. */
function common_units(): array {
    return ['pieces', 'kg', 'g', 'L', 'mL', 'pack', 'dozen', 'bunch', 'can', 'loaf', 'jar', 'bottle'];
}
