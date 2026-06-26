<?php
require_once 'config/db.php';

$results = [];

// ── Loan Products (already working) ──────────────────────────
try {
    $pdo->exec("INSERT IGNORE INTO loan_products (name, min_amount, max_amount, interest_rate, duration_months, is_active) VALUES
        ('Quick Loan', 5000, 50000, 5.0, 3, 1),
        ('Standard Loan', 50000, 500000, 8.5, 6, 1),
        ('Business Loan', 500000, 5000000, 12.0, 12, 1)");
    $results[] = '✅ Loan products inserted';
} catch (Exception $e) { $results[] = '❌ Loan products: ' . $e->getMessage(); }

// ── Gift Cards (brand, country, face_value, selling_price, buyback_price, stock) ──
try {
    $pdo->exec("INSERT IGNORE INTO giftcard_products (brand, country, face_value, selling_price, buyback_price, stock) VALUES
        ('Amazon', 'US', 10.00, 9500.00, 8500.00, 100),
        ('Amazon', 'US', 25.00, 23500.00, 21000.00, 100),
        ('iTunes', 'US', 15.00, 14000.00, 12500.00, 50),
        ('iTunes', 'US', 25.00, 23000.00, 20500.00, 50),
        ('Google Play', 'US', 10.00, 9200.00, 8200.00, 80),
        ('Google Play', 'US', 25.00, 22800.00, 20300.00, 80)");
    $results[] = '✅ Gift cards inserted';
} catch (Exception $e) { $results[] = '❌ Gift cards: ' . $e->getMessage(); }

// ── Invest Products (name, min_invest, expected_return_rate, duration_days, risk_level, is_active) ──
try {
    $pdo->exec("INSERT IGNORE INTO invest_products (name, min_invest, expected_return_rate, duration_days, risk_level, is_active) VALUES
        ('Fixed Savings Plan', 10000.00, 8.50, 90, 'low', 1),
        ('Growth Fund', 50000.00, 15.00, 180, 'medium', 1),
        ('Aggressive Portfolio', 100000.00, 25.00, 365, 'high', 1),
        ('Starter Plan', 5000.00, 5.00, 30, 'low', 1)");
    $results[] = '✅ Invest products inserted';
} catch (Exception $e) { $results[] = '❌ Invest products: ' . $e->getMessage(); }
// Stocks table
try {
    $pdo->exec("INSERT IGNORE INTO stocks (symbol, company_name, current_price, logo_url) VALUES
        ('DANGCEM', 'Dangote Cement', 285.50, 'https://logo.clearbit.com/dangotecement.com'),
        ('MTNN', 'MTN Nigeria', 198.00, 'https://logo.clearbit.com/mtn.com'),
        ('ZENITHBANK', 'Zenith Bank', 36.50, 'https://logo.clearbit.com/zenithbank.com'),
        ('GTCO', 'GTBank', 42.00, 'https://logo.clearbit.com/gtbank.com'),
        ('AIRTELAFRI', 'Airtel Africa', 1850.00, 'https://logo.clearbit.com/airtel.com'),
        ('BUACEMENT', 'BUA Cement', 122.00, 'https://logo.clearbit.com/buacement.com'),
        ('NESTLE', 'Nestle Nigeria', 900.00, 'https://logo.clearbit.com/nestle.com'),
        ('SEPLAT', 'Seplat Energy', 4200.00, 'https://logo.clearbit.com/seplatpetroleum.com')
    ");
    $results[] = '✅ Stocks inserted';
} catch (Exception $e) { $results[] = '❌ Stocks: ' . $e->getMessage(); }
try {
    $pdo->exec("INSERT IGNORE INTO savings_plans (name, type, min_amount, interest_rate, duration_days, is_active) VALUES
        ('Daily Flex', 'flexible', 500.00, 8.00, 30, 1),
        ('Weekly Plan', 'flexible', 1000.00, 10.00, 90, 1),
        ('3 Month Fixed', 'fixed', 5000.00, 12.00, 90, 1),
        ('6 Month Fixed', 'fixed', 10000.00, 14.00, 180, 1),
        ('Yearly Fixed', 'fixed', 20000.00, 18.00, 365, 1),
        ('Student Savings', 'flexible', 200.00, 6.00, 30, 1)");
    $results[] = '✅ Savings plans inserted';
} catch (Exception $e) { $results[] = '❌ Savings plans: ' . $e->getMessage(); }


echo '<pre>=== SendNaw Data Setup ===\n\n';
foreach ($results as $r) echo $r . "\n";
echo "\nDone!";
?>