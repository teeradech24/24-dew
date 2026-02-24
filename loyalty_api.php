<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$userId = $_SESSION['user_id'] ?? 0;

// Tier config
function getTierConfig($tier) {
    $tiers = [
        'bronze'  => ['label' => '🥉 Bronze',  'rate' => 1.0,  'min_spent' => 0,      'color' => '#cd7f32'],
        'silver'  => ['label' => '🥈 Silver',  'rate' => 1.5,  'min_spent' => 10000,  'color' => '#c0c0c0'],
        'gold'    => ['label' => '🥇 Gold',    'rate' => 2.0,  'min_spent' => 30000,  'color' => '#ffd700'],
        'diamond' => ['label' => '💎 Diamond', 'rate' => 3.0,  'min_spent' => 100000, 'color' => '#b9f2ff'],
    ];
    return $tiers[$tier] ?? $tiers['bronze'];
}

function calculateTier($totalSpent) {
    if ($totalSpent >= 100000) return 'diamond';
    if ($totalSpent >= 30000) return 'gold';
    if ($totalSpent >= 10000) return 'silver';
    return 'bronze';
}

function earnPoints($totalAmount, $tier) {
    $config = getTierConfig($tier);
    return (int)floor($totalAmount / 100 * $config['rate']);
}

switch ($action) {
    case 'get_points':
        if (!$userId) { echo json_encode(['ok' => false, 'msg' => 'กรุณาเข้าสู่ระบบ']); exit; }
        
        $stmt = $pdo->prepare("SELECT loyalty_points, total_spent, loyalty_tier FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user) { echo json_encode(['ok' => false, 'msg' => 'ไม่พบผู้ใช้']); exit; }
        
        $tier = $user['loyalty_tier'] ?: 'bronze';
        $tierConfig = getTierConfig($tier);
        
        // Next tier info
        $nextTier = null;
        $progress = 100;
        $tierOrder = ['bronze', 'silver', 'gold', 'diamond'];
        $currentIdx = array_search($tier, $tierOrder);
        if ($currentIdx < 3) {
            $next = $tierOrder[$currentIdx + 1];
            $nextConfig = getTierConfig($next);
            $nextTier = ['name' => $next, 'label' => $nextConfig['label'], 'min_spent' => $nextConfig['min_spent']];
            $prevMin = getTierConfig($tier)['min_spent'];
            $range = $nextConfig['min_spent'] - $prevMin;
            $progress = min(100, (($user['total_spent'] - $prevMin) / $range) * 100);
        }
        
        // Recent transactions
        $txStmt = $pdo->prepare("SELECT * FROM loyalty_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
        $txStmt->execute([$userId]);
        $transactions = $txStmt->fetchAll();
        
        echo json_encode([
            'ok' => true,
            'points' => (int)$user['loyalty_points'],
            'total_spent' => (float)$user['total_spent'],
            'tier' => $tier,
            'tier_label' => $tierConfig['label'],
            'tier_color' => $tierConfig['color'],
            'tier_rate' => $tierConfig['rate'],
            'next_tier' => $nextTier,
            'progress' => round($progress, 1),
            'transactions' => $transactions,
        ]);
        break;

    case 'calculate_earn':
        $amount = (float)($_POST['amount'] ?? 0);
        $tier = 'bronze';
        if ($userId) {
            $stmt = $pdo->prepare("SELECT loyalty_tier FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $u = $stmt->fetch();
            if ($u) $tier = $u['loyalty_tier'] ?: 'bronze';
        }
        $points = earnPoints($amount, $tier);
        $config = getTierConfig($tier);
        echo json_encode([
            'ok' => true,
            'points' => $points,
            'tier' => $tier,
            'tier_label' => $config['label'],
            'rate' => $config['rate'],
        ]);
        break;

    case 'redeem_preview':
        if (!$userId) { echo json_encode(['ok' => false, 'msg' => 'กรุณาเข้าสู่ระบบ']); exit; }
        $pointsToUse = (int)($_POST['points'] ?? 0);
        $stmt = $pdo->prepare("SELECT loyalty_points FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $u = $stmt->fetch();
        if (!$u) { echo json_encode(['ok' => false, 'msg' => 'ไม่พบผู้ใช้']); exit; }
        
        if ($pointsToUse > $u['loyalty_points']) {
            echo json_encode(['ok' => false, 'msg' => 'แต้มไม่เพียงพอ (มี ' . $u['loyalty_points'] . ' แต้ม)']);
            exit;
        }
        // 1 point = 1 baht
        $discount = $pointsToUse;
        echo json_encode(['ok' => true, 'points' => $pointsToUse, 'discount' => $discount, 'remaining' => $u['loyalty_points'] - $pointsToUse]);
        break;

    default:
        echo json_encode(['ok' => false, 'msg' => 'Unknown action']);
}
