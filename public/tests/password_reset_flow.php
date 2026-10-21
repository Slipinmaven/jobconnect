<?php
/**
 * Password Reset Complete Flow Test
 * Tests the entire password reset process from creation to verification
 */

$basePath = dirname(__DIR__, 2);

// Load dependencies
require_once $basePath . '/vendor/autoload.php';

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable($basePath);
$dotenv->load();

// Build container and get services
require_once $basePath . '/app/Helpers/container.php';
$container = buildContainer();

use App\Services\TokenService;
use App\Services\AuthService;
use App\Helpers\CryptoHelper;
use App\Repositories\TwoFactorCodeRepository;

// Get services
$tokenService = $container->get(TokenService::class);
$authService = $container->get(AuthService::class);
$crypto = $container->get(CryptoHelper::class);
$twoFactorRepo = $container->get(TwoFactorCodeRepository::class);
$pdo = $container->get(\PDO::class);

// Test user
$userId = 6;
$email = 'jasngigi2@gmail.com';

echo "=== Password Reset Complete Flow Test ===\n\n";

// STEP 1: Create a raw code and hash it manually
echo "STEP 1: Create raw code and hash it\n";
$rawCode = '123456'; // Use a fixed code for easier debugging
$hashedCode = hash('sha256', $rawCode);
echo "  Raw Code: $rawCode\n";
echo "  Hashed Code: $hashedCode\n\n";

// STEP 2: Store it in the database
echo "STEP 2: Store code in database\n";
$sql = "INSERT INTO two_factor_codes (user_id, type, code_hash, expires_at, attempts, created_at, updated_at) 
        VALUES (:user_id, :type, :code_hash, DATE_ADD(NOW(), INTERVAL 30 MINUTE), 0, NOW(), NOW())";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':user_id' => $userId,
    ':type' => 'password_reset',
    ':code_hash' => $hashedCode
]);
$codeId = $pdo->lastInsertId();
echo "  Stored code ID: $codeId\n\n";

// STEP 3: Retrieve and verify the code exists
echo "STEP 3: Retrieve code from database\n";
$sql = "SELECT id, user_id, type, code_hash, expires_at, attempts FROM two_factor_codes WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$codeId]);
$dbCode = $stmt->fetch(\PDO::FETCH_ASSOC);

if ($dbCode) {
    echo "  ✓ Code found in database\n";
    echo "    - ID: {$dbCode['id']}\n";
    echo "    - User ID: {$dbCode['user_id']}\n";
    echo "    - Type: {$dbCode['type']}\n";
    echo "    - Code Hash: {$dbCode['code_hash']}\n";
    echo "    - Expires At: {$dbCode['expires_at']}\n";
    echo "    - Attempts: {$dbCode['attempts']}\n";
    
    // Verify hash matches
    echo "\n  Hash Comparison:\n";
    echo "    - Stored Hash: {$dbCode['code_hash']}\n";
    echo "    - Computed Hash: $hashedCode\n";
    echo "    - Match: " . ($dbCode['code_hash'] === $hashedCode ? 'YES ✓' : 'NO ✗') . "\n";
} else {
    echo "  ✗ Code NOT found in database\n";
    exit(1);
}
echo "\n";

// STEP 4: Test the validateCode method from repository
echo "STEP 4: Test repository->validateCode() method\n";
$isValid = $twoFactorRepo->validateCode($userId, 'password_reset', $hashedCode);
echo "  Result: " . ($isValid ? 'TRUE ✓' : 'FALSE ✗') . "\n";

if (!$isValid) {
    // Debug: Query manually
    echo "\n  DEBUG: Manual query to see what validateCode sees:\n";
    $sql = "SELECT * FROM two_factor_codes 
            WHERE user_id = ? 
            AND type = ? 
            AND expires_at > NOW() 
            AND attempts < 3
            ORDER BY created_at DESC 
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId, 'password_reset_test']);
    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "    - Found code: {$result['id']}\n";
        echo "    - Hash match: " . ($result['code_hash'] === $hashedCode ? 'YES' : 'NO') . "\n";
    } else {
        echo "    - No code found matching query criteria\n";
        
        // Check why
        echo "\n  Checking query conditions:\n";
        $checks = $pdo->prepare("SELECT 
            user_id, type, 
            expires_at, NOW() as now_time,
            expires_at > NOW() as expires_valid,
            attempts, 
            attempts < 3 as attempts_valid
            FROM two_factor_codes WHERE id = ?"
        );
        $checks->execute([$codeId]);
        $check = $checks->fetch(\PDO::FETCH_ASSOC);
        
        if ($check) {
            echo "    - User ID match ({$check['user_id']} = $userId): " . ($check['user_id'] == $userId ? 'YES' : 'NO') . "\n";
            echo "    - Type match: password_reset_test\n";
            echo "    - Expires at: {$check['expires_at']}\n";
            echo "    - Now: {$check['now_time']}\n";
            echo "    - Not expired: " . ($check['expires_valid'] ? 'YES' : 'NO') . "\n";
            echo "    - Attempts ({$check['attempts']} < 3): " . ($check['attempts_valid'] ? 'YES' : 'NO') . "\n";
        }
    }
}
echo "\n";

// STEP 5: Test with TokenService methods
echo "STEP 5: Test TokenService methods\n";

// Test validateTwoFactorCode (non-destructive)
$isValid = $tokenService->validateTwoFactorCode($userId, 'password_reset', $rawCode);
echo "  validateTwoFactorCode(): " . ($isValid ? 'TRUE ✓' : 'FALSE ✗') . "\n";

// Test verifyTwoFactorCode (destructive)
$isValid = $tokenService->verifyTwoFactorCode($userId, 'password_reset', $rawCode);
echo "  verifyTwoFactorCode(): " . ($isValid ? 'TRUE ✓' : 'FALSE ✗') . "\n";

// Check if code was deleted
$stmt = $pdo->prepare("SELECT id FROM two_factor_codes WHERE id = ?");
$stmt->execute([$codeId]);
$stillExists = $stmt->fetch(\PDO::FETCH_ASSOC);
echo "  Code still exists after verify: " . ($stillExists ? 'YES' : 'NO ✓') . "\n";

echo "\n";

// STEP 6: Test encryption/decryption with the flow
echo "STEP 6: Test Full Password Reset Flow with Encryption\n";

// Create a new raw code
$newRawCode = $tokenService->generateNumericCode();
$newHashedCode = $tokenService->hashToken($newRawCode);

// Insert to database
$sql = "INSERT INTO two_factor_codes (user_id, type, code_hash, expires_at, attempts, created_at, updated_at) 
        VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE), 0, NOW(), NOW())";
$stmt = $pdo->prepare($sql);
$stmt->execute([$userId, 'password_reset', $newHashedCode]);
$newCodeId = $pdo->lastInsertId();

echo "  Created code: $newRawCode\n";

// Encrypt it
$encryptedToken = $crypto->encrypt($newRawCode);
$token = base64_encode($encryptedToken);
echo "  Encrypted & Base64: " . substr($token, 0, 40) . "...\n";

// Decrypt it
$binaryToken = base64_decode($token, true);
$decryptedCode = $crypto->decrypt($binaryToken);
echo "  Decrypted: $decryptedCode\n";
echo "  Match original: " . ($decryptedCode === $newRawCode ? 'YES ✓' : 'NO ✗') . "\n";

// Verify with service
$isValid = $tokenService->validateTwoFactorCode($userId, 'password_reset', $decryptedCode);
echo "  Encryption flow validation: " . ($isValid ? 'SUCCESS ✓' : 'FAILED ✗') . "\n";

echo "\n=== Test Complete ===\n";

// Cleanup
$pdo->prepare("DELETE FROM two_factor_codes WHERE id IN (?, ?)")->execute([$codeId, $newCodeId]);
echo "Cleaned up test data.\n";
