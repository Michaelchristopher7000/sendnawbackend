<?php
require_once 'totp.php'; // include the TOTP class
require_once '../../config/db.php';

// [Authentication block – same as before, get $userId]
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';
$token = '';
if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) $token = $matches[1];
if (!$token) exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
$stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = ?");
$stmt->execute([$token]);
$row = $stmt->fetch();
if (!$row) exit(json_encode(['success' => false, 'message' => 'Invalid token']));
$userId = $row['user_id'];

// Get user email for OTP URL
$stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
$stmt->execute([$userId]);
$email = $stmt->fetchColumn();

// Generate new secret
$secret = TOTP::generateSecret(16); // 16 characters base32

// Store secret in database (will be used when enabling)
$stmt = $pdo->prepare("UPDATE users SET two_factor_secret = ? WHERE id = ?");
$stmt->execute([$secret, $userId]);

// Create OTP URL for Google Authenticator
$otpUrl = "otpauth://totp/SendNaw:{$email}?secret={$secret}&issuer=SendNaw";

// Optional: generate QR code as data URL (using Google Charts API)
$qrUrl = "https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=" . urlencode($otpUrl);
echo json_encode(['success' => true, 'secret' => $secret, 'otp_url' => $otpUrl, 'qr_url' => $qrUrl]);
