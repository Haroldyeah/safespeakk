<?php
/**
 * Security utilities for password strength and brute force protection
 */

/**
 * Check password strength
 * Returns: weak, medium, strong
 */
function checkPasswordStrength($password) {
    $strength = 'weak';
    $score = 0;

    // Length checks
    if (strlen($password) >= 8) $score += 1;
    if (strlen($password) >= 12) $score += 1;
    if (strlen($password) >= 16) $score += 1;

    // Character variety checks
    if (preg_match('/[a-z]/', $password)) $score += 1;
    if (preg_match('/[A-Z]/', $password)) $score += 1;
    if (preg_match('/[0-9]/', $password)) $score += 1;
    if (preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\|`~]/', $password)) $score += 2;

    // Determine strength
    if ($score >= 5) {
        $strength = 'strong';
    } elseif ($score >= 3) {
        $strength = 'medium';
    } else {
        $strength = 'weak';
    }

    return $strength;
}

/**
 * Get failed attempt count from database
 * Counts failed attempts in the last hour from the given identifier
 */
function getFailedAttempts($identifier) {
    global $db;
    
    // Count failed attempts in the last hour
    $result = $db->fetchOne(
        "SELECT COUNT(*) as count FROM login_attempts WHERE identifier = ? AND attempt_type = 'failed' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
        [$identifier]
    );
    
    return intval($result['count'] ?? 0);
}

/**
 * Record failed login attempt
 */
function recordFailedAttempt($identifier) {
    global $db;
    
    // Log to database for audit purposes
    try {
        $db->query(
            "INSERT INTO login_attempts (identifier, attempt_type, ip_address, created_at) VALUES (?, ?, ?, NOW())",
            [$identifier, 'failed', $_SERVER['REMOTE_ADDR'] ?? 'unknown']
        );
    } catch (Exception $e) {
        // Silently fail if database is unavailable, still show error to user via login logic
    }
}

/**
 * Check if account is locked due to brute force
 * Lock after 5 failed attempts within lockout window
 * Lockout times escalate with more attempts
 */
function isAccountLocked($identifier) {
    $attempts = getFailedAttempts($identifier);
    return $attempts >= 5;
}

/**
 * Get lockout duration in seconds based on number of attempts
 * Escalates over time: 5min -> 15min -> 30min -> 1hr -> 2hrs
 */
function getLockoutDuration($attempts) {
    if ($attempts < 5) return 0; // Not locked
    if ($attempts < 10) return 5 * 60; // 5-9 attempts: 5 minutes
    if ($attempts < 15) return 15 * 60; // 10-14 attempts: 15 minutes
    if ($attempts < 20) return 30 * 60; // 15-19 attempts: 30 minutes
    if ($attempts < 25) return 60 * 60; // 20-24 attempts: 1 hour
    return 120 * 60; // 25+ attempts: 2 hours
}

/**
 * Get lockout time remaining in seconds
 * Calculates time until the oldest failed attempt is outside the lockout window
 */
function getLockoutTimeRemaining($identifier) {
    global $db;
    
    $attempts = getFailedAttempts($identifier);
    if ($attempts < 5) {
        return 0; // Account not locked
    }
    
    // Get the oldest failed attempt within the lockout window
    $result = $db->fetchOne(
        "SELECT MIN(created_at) as oldest_attempt FROM login_attempts WHERE identifier = ? AND attempt_type = 'failed' AND created_at > DATE_SUB(NOW(), INTERVAL 2 HOUR)",
        [$identifier]
    );
    
    if (!$result || !$result['oldest_attempt']) {
        return 0;
    }
    
    // Calculate lockout duration based on current attempt count
    $lockoutDuration = getLockoutDuration($attempts);
    
    // Calculate seconds until lockout expires
    $oldestTime = strtotime($result['oldest_attempt']);
    $lockoutExpires = $oldestTime + $lockoutDuration;
    $secondsRemaining = $lockoutExpires - time();
    
    return max(0, $secondsRemaining);
}

/**
 * Clear failed attempts for identifier
 */
function clearFailedAttempts($identifier) {
    global $db;
    
    try {
        $db->query(
            "DELETE FROM login_attempts WHERE identifier = ? AND attempt_type = 'failed' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            [$identifier]
        );
    } catch (Exception $e) {
        // Silently fail if database is unavailable
    }
}

/**
 * Record successful login
 */
function recordSuccessfulLogin($identifier) {
    global $db;
    
    clearFailedAttempts($identifier);
    
    try {
        $db->query(
            "INSERT INTO login_attempts (identifier, attempt_type, ip_address, created_at) VALUES (?, ?, ?, NOW())",
            [$identifier, 'success', $_SERVER['REMOTE_ADDR'] ?? 'unknown']
        );
    } catch (Exception $e) {
        // Silently fail if database is unavailable
    }
}

/**
 * Get login attempt history (for admin audit)
 */
function getLoginAttemptHistory($identifier, $limit = 10) {
    global $db;
    
    try {
        return $db->fetchAll(
            "SELECT * FROM login_attempts WHERE identifier = ? ORDER BY created_at DESC LIMIT ?",
            [$identifier, $limit]
        );
    } catch (Exception $e) {
        return [];
    }
}
?>
