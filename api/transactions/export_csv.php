<?php
require_once '../../config/db.php';

// Same token validation as history.php (copy the block)
// ... get $userId and apply the same filters (without limit)

// After fetching $transactions (unlimited), output CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="transactions_' . date('Y-m-d') . '.csv"');
$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Type', 'Amount', 'Currency', 'Description', 'Date', 'Status']);
foreach ($transactions as $tx) {
    fputcsv($output, [$tx['id'], $tx['type'], $tx['amount'], $tx['currency'], $tx['description'], $tx['created_at'], $tx['status']]);
}
fclose($output);
