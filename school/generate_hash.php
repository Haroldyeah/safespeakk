<?php
$passwords = [
    'i_love_cebu_philippines123',
    'daanbantayannhs_123',
    'brightminds_admin123',
    'sla_admin_123',
    'smpaAdmin_123',
    'doveracademyAdmin_2025',
    'constancioPAdmin_2025'
];

foreach ($passwords as $plain) {
    echo "Password: $plain<br>";
    echo "Hash: " . password_hash($plain, PASSWORD_DEFAULT) . "<br><br>";
}
