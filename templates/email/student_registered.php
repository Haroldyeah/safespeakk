<?php
/**
 * Email template for new student registration notification to school.
 * Sent when a student verifies their email and completes registration.
 *
 * @var array $student The student who registered (user record)
 * @var array $school The school record
 * @var string $appName The application name
 * @var string $registrationDate The date and time of registration
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Student Registration Notification</title>
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
        .student-card {
            background-color: #f0f4f8;
            border-left: 4px solid #667eea;
            padding: 14px;
            margin: 16px 0;
            border-radius: 4px;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 12px;
            background: #d4edda;
            color: #155724;
            border-radius: 4px;
            font-weight: 600;
            font-size: 13px;
            margin: 8px 0;
        }
        .action-box {
            background-color: #e7f3ff;
            border-left: 4px solid #0d6efd;
            padding: 12px 14px;
            margin: 16px 0;
            border-radius: 4px;
            font-size: 13px;
            color: #004085;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="email-wrapper">
            <div class="header">
                <h2>New Student Registration</h2>
            </div>
            <div class="content">
                <p class="greeting">Good day,</p>
                <p class="intro-text">A new student has successfully verified their email and completed registration in <?php echo htmlspecialchars($appName ?? 'Capstone Tracker'); ?>. Their account is now active.</p>

                <div class="section-title">Student Information</div>
                <div class="student-card">
                    <table class="info-table">
                        <tr>
                            <td>Full Name</td>
                            <td><strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong></td>
                        </tr>
                        <tr>
                            <td>Email</td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                        </tr>
                        <tr>
                            <td>Student ID</td>
                            <td><?php echo htmlspecialchars($student['student_id'] ?? '—'); ?></td>
                        </tr>
                        <tr>
                            <td>Username</td>
                            <td><?php echo htmlspecialchars($student['username']); ?></td>
                        </tr>
                        <tr>
                            <td>Account Status</td>
                            <td><span class="status-badge">✓ Active & Verified</span></td>
                        </tr>
                    </table>
                </div>

                <div class="section-title">Registration Details</div>
                <table class="info-table">
                    <tr>
                        <td>Registration Date</td>
                        <td><?php echo htmlspecialchars($registrationDate ?? date('Y-m-d H:i:s')); ?></td>
                    </tr>
                    <tr>
                        <td>School</td>
                        <td><?php echo htmlspecialchars($school['name'] ?? '—'); ?></td>
                    </tr>
                </table>

                <div class="action-box">
                    ℹ️ <strong>Next Steps:</strong> This student can now access the system and begin submitting reports. You may want to review their account information and ensure they are enrolled in the correct school and grade level.
                </div>

                <p style="font-size: 12px; color: #888; margin-top: 16px;">This is an automated notification from <?php echo htmlspecialchars($appName ?? 'Capstone Tracker'); ?>. Please do not reply directly to this email.</p>
            </div>
            <div class="footer">
                <p class="footer-text">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($appName ?? 'Capstone Tracker'); ?>. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>