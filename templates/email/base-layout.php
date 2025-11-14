<?php
/**
 * Base email layout template for all system emails.
 * This provides a consistent professional design across all notifications.
 * 
 * Usage in other templates:
 * require_once 'base-layout.php';
 * $layoutContent = getEmailLayout([
 *     'title' => 'Email Title',
 *     'headerColor' => '#667eea', // optional, defaults to purple gradient
 *     'content' => '<p>Email content HTML</p>',
 *     'footer' => 'Additional footer text' // optional
 * ]);
 * echo $layoutContent;
 */

function getEmailLayout($config = []) {
    $title = $config['title'] ?? 'Capstone Tracker Notification';
    $headerColor = $config['headerColor'] ?? '#667eea';
    $headerColorLight = $config['headerColorLight'] ?? '#764ba2';
    $content = $config['content'] ?? '';
    $footerText = $config['footer'] ?? '&copy; ' . date('Y') . ' Capstone Tracker. All rights reserved.';
    $appName = $config['appName'] ?? 'Capstone Tracker';
    $baseUrl = $config['baseUrl'] ?? 'https://safespeak.local';

    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #2c3e50;
            background-color: #f5f7fa;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 650px;
            margin: 0 auto;
            padding: 16px;
        }
        .email-wrapper {
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        .header {
            background: linear-gradient(135deg, {$headerColor} 0%, {$headerColorLight} 100%);
            color: #fff;
            padding: 28px 24px;
            text-align: center;
        }
        .header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            letter-spacing: -0.5px;
        }
        .content {
            padding: 28px 24px;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 12px;
            font-weight: 500;
        }
        .intro-text {
            color: #555;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.7;
        }
        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #2c3e50;
            margin-top: 18px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        .info-table tr {
            border-bottom: 1px solid #e8ecf1;
        }
        .info-table tr:last-child {
            border-bottom: none;
        }
        .info-table td {
            padding: 12px 0;
            font-size: 14px;
        }
        .info-table td:first-child {
            font-weight: 600;
            color: #34495e;
            width: 140px;
        }
        .info-table td:last-child {
            color: #555;
        }
        .alert {
            padding: 14px;
            margin: 16px 0;
            border-radius: 4px;
            font-size: 13px;
            line-height: 1.6;
        }
        .alert-warning {
            background-color: #fef3cd;
            border-left: 4px solid #ffc107;
            color: #856404;
        }
        .alert-info {
            background-color: #d1ecf1;
            border-left: 4px solid #17a2b8;
            color: #0c5460;
        }
        .alert-success {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            color: #155724;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: {$headerColor};
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            margin: 12px 0;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .footer {
            background-color: #f8f9fb;
            padding: 20px 24px;
            text-align: center;
            border-top: 1px solid #e8ecf1;
        }
        .footer-text {
            font-size: 12px;
            color: #888;
            margin: 0;
            line-height: 1.6;
        }
        .footer-link {
            color: {$headerColor};
            text-decoration: none;
        }
        .footer-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="email-wrapper">
            {$content}
            <div class="footer">
                <p class="footer-text">{$footerText}</p>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
}

/**
 * Helper function to build a section with a title and info table
 */
function buildInfoSection($title, $rows) {
    $html = '<div class="section-title">' . htmlspecialchars($title) . '</div>';
    $html .= '<table class="info-table">';
    foreach ($rows as $label => $value) {
        $html .= '<tr><td>' . htmlspecialchars($label) . '</td><td>' . htmlspecialchars($value ?? '—') . '</td></tr>';
    }
    $html .= '</table>';
    return $html;
}
?>
