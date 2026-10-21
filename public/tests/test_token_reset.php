<?php
$basePath = dirname(__DIR__, 2);

// Load dependencies
require_once $basePath . '/vendor/autoload.php';

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable($basePath);
$dotenv->load();

// Build container
require_once $basePath . '/app/Helpers/container.php';
$container = buildContainer();

use App\Services\TokenService;
use App\Services\AuthService;
use App\Helpers\CryptoHelper;

// Get services
$tokenService = $container->get(TokenService::class);
$authService = $container->get(AuthService::class);
$crypto = $container->get(CryptoHelper::class);

// Test user
$userId = 6; // Replace with a real user ID
$email = 'jasngigi2@gmail.com';

echo "=== Testing Password Reset Token Encryption ===\n\n";

// Step 1: Create token
echo "Step 1: Creating password reset token...\n";
$resetData = $tokenService->createTwoFactorCode($userId, 'password_reset', 30);
$rawCode = $resetData['code'];
echo "Raw code generated: $rawCode\n";
echo "Code ID: {$resetData['code_id']}\n\n";

// Step 2: Encrypt token
echo "Step 2: Encrypting token...\n";
$encryptedToken = $crypto->encrypt($rawCode);
echo "Encrypted token (binary) length: " . strlen($encryptedToken) . " bytes\n";
echo "IV (12) + Tag (16) + Ciphertext (" . (strlen($encryptedToken) - 28) . ")\n\n";

// Step 3: Base64 encode
echo "Step 3: Base64 encoding...\n";
$token = base64_encode($encryptedToken);
echo "Token (base64): " . substr($token, 0, 50) . "...\n";
echo "Token length: " . strlen($token) . " chars\n\n";

// Step 4: Test decryption
echo "Step 4: Testing decryption...\n";
$binaryToken = base64_decode($token, true);
echo "Base64 decoded length: " . strlen($binaryToken) . " bytes\n";

if ($binaryToken === false) {
    echo "ERROR: Base64 decode failed!\n";
    exit(1);
}

$decryptedCode = $crypto->decrypt($binaryToken);
echo "Decrypted code: " . ($decryptedCode ?? 'NULL') . "\n";
echo "Does it match original? " . ($decryptedCode === $rawCode ? 'YES ✓' : 'NO ✗') . "\n\n";

// Step 5: Verify with tokenService (non-destructive)
echo "Step 5: Validating token with service (non-destructive)...\n";
$isValid = $tokenService->validateTwoFactorCode($userId, 'password_reset', $decryptedCode);
echo "TokenService->validateTwoFactorCode() returned: " . ($isValid ? 'true' : 'false') . "\n";

// Debug: Call repository directly
$twoFactorRepo = $container->get(\App\Repositories\TwoFactorCodeRepository::class);
$hashedCodeForRepo = $tokenService->hashToken($decryptedCode);
$isValidDirect = $twoFactorRepo->validateCode($userId, 'password_reset', $hashedCodeForRepo);
echo "Repository->validateCode() returned: " . ($isValidDirect ? 'true' : 'false') . "\n";

// Also check the database directly
$pdo = $container->get(\PDO::class);
$stmt = $pdo->prepare("SELECT id, user_id, type, code_hash, expires_at FROM two_factor_codes WHERE user_id = ? AND type = 'password_reset' ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$userId]);
$dbCode = $stmt->fetch(\PDO::FETCH_ASSOC);

if ($dbCode) {
    $expectedHash = hash('sha256', $decryptedCode);
    echo "Debug Info:\n";
    echo "  Raw code: $decryptedCode\n";
    echo "  Expected hash: $expectedHash\n";
    echo "  DB hash: {$dbCode['code_hash']}\n";
    echo "  Expires at: {$dbCode['expires_at']}\n";
    echo "  Hash match: " . ($expectedHash === $dbCode['code_hash'] ? 'YES' : 'NO') . "\n";
} else {
    echo "Debug: No code found in database for user $userId\n";
}

echo "\n";

// Step 6: Test full flow
echo "Step 6: Testing verifyPasswordResetToken...\n";
$result = $authService->verifyPasswordResetToken($email, $token);
echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
