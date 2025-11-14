<?php
/**
 * Email template for user profile updates.
 *
 * @var array $user The user whose profile was updated.
 * @var array $updatedBy The user who performed the update.
 * @var string $userRole The role of the user whose profile was updated.
 * @var array $changes Optional array of changes: ['fieldName' => ['old' => '...', 'new' => '...']]
 * @var string $timestamp Optional timestamp of update (defaults to current time)
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Update Notification</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #2c3e50;
            background-color: #f5f7fa;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        .changes-section {
            background-color: #f0f4f8;
            border-left: 4px solid #667eea;
            padding: 14px;
            margin: 16px 0;
            border-radius: 4px;
        }
        .change-item {
            margin-bottom: 12px;
            font-size: 14px;
        }
        .change-item:last-child {
            margin-bottom: 0;
        }
        .field-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 4px;
        }
        .change-row {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }
        .before, .after {
            flex: 1;
        }
        .value-box {
            background-color: #ffffff;
            border: 1px solid #e0e6ed;
            padding: 8px 10px;
            border-radius: 4px;
            font-size: 13px;
            color: #555;
            word-break: break-word;
        }
        .before-label, .after-label {
            font-size: 12px;
            font-weight: 600;
            color: #888;
            margin-bottom: 4px;
        }
        .warning-box {
            background-color: #fef3cd;
            border-left: 4px solid #ffc107;
            padding: 12px 14px;
            margin: 16px 0;
            border-radius: 4px;
            font-size: 13px;
            color: #856404;
            line-height: 1.6;
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
            color: #667eea;
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
            <div class="header">
                <h2>Profile Update Notification</h2>
            </div>
            <div class="content">
                <p class="greeting">Good day,</p>
                <p class="intro-text">A profile was recently updated in the Capstone Tracker system. Please review the details below.</p>

                <div class="section-title">Profile Information</div>
                <table class="info-table">
                    <tr>
                        <td>Name</td>
                        <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                    </tr>
                    <tr>
                        <td>Email</td>
                        <td><?php echo htmlspecialchars($user['email'] ?? '—'); ?></td>
                    </tr>
                    <tr>
                        <td>Role</td>
                        <td><?php echo htmlspecialchars($userRole); ?></td>
                    </tr>
                </table>

                <?php if (!empty($changes) && is_array($changes)): ?>
                    <div class="section-title" style="margin-top: 20px;">Changes Made</div>
                    <div class="changes-section">
                        <?php foreach ($changes as $fieldName => $changeData): ?>
                            <div class="change-item">
                                <div class="field-label"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $fieldName))); ?></div>
                                <div class="change-row">
                                    <div class="before">
                                        <div class="before-label">Before:</div>
                                        <div class="value-box"><?php echo htmlspecialchars($changeData['old'] ?? '—'); ?></div>
                                    </div>
                                    <div class="after">
                                        <div class="after-label">After:</div>
                                        <div class="value-box"><?php echo htmlspecialchars($changeData['new'] ?? '—'); ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="section-title" style="margin-top: 20px;">Update Details</div>
                <table class="info-table">
                    <tr>
                        <td>Updated By</td>
                        <td><?php echo htmlspecialchars($updatedBy['first_name'] . ' ' . $updatedBy['last_name']); ?></td>
                    </tr>
                    <tr>
                        <td>Updated By Email</td>
                        <td><?php echo htmlspecialchars($updatedBy['email'] ?? '—'); ?></td>
                    </tr>
                    <tr>
                        <td>Date & Time</td>
                        <td><?php echo htmlspecialchars($timestamp ?? date('Y-m-d H:i:s')); ?></td>
                    </tr>
                </table>

                <div class="warning-box">
                    ⚠️ If you did not authorize this change or have concerns, please contact your system administrator immediately.
                </div>

                <p style="font-size: 12px; color: #888; margin-top: 16px;">This is an automated message from Capstone Tracker. Please do not reply directly to this email.</p>
            </div>
            <div class="footer">
                <p class="footer-text">&copy; <?php echo date('Y'); ?> Capstone Tracker. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
