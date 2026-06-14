<?php


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$PERMISSIONS = [
    'admin'   => ['view','add','edit','delete','adjust','report','manage_users'],
    'manager' => ['view','add','edit','delete','adjust','report'],
    'staff'   => ['view','adjust'],
];


function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}


function can(string $action): bool {
    global $PERMISSIONS;
    $role = $_SESSION['role'] ?? 'staff';
    return in_array($action, $PERMISSIONS[$role] ?? []);
}


function require_permission(string $action) {
    if (!can($action)) {
        $_SESSION['flash_error'] = '⛔ คุณไม่มีสิทธิ์ทำรายการนี้';
        header('Location: index.php');
        exit;
    }
}


function role_badge(string $role): string {
    $map = [
        'admin'   => ['label'=>'Admin',   'style'=>'background:#ede9fe;color:#6d28d9'],
        'manager' => ['label'=>'Manager', 'style'=>'background:#dbeafe;color:#1d4ed8'],
        'staff'   => ['label'=>'Staff',   'style'=>'background:#dcfce7;color:#166534'],
    ];
    $r = $map[$role] ?? ['label'=>$role, 'style'=>'background:#f1f5f9;color:#64748b'];
    return "<span style=\"display:inline-block;padding:2px 10px;border-radius:20px;font-size:12px;font-weight:700;{$r['style']}\">{$r['label']}</span>";
}


require_login();
