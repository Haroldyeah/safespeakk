<?php
// Variables expected:
// $studentName, $statusLabel, $comments, $title, $reportUrl, $appName, $baseUrl
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    * { box-sizing: border-box; }
    body { 
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; 
      color: #2c3e50;
      line-height: 1.6;
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
      background-color: #fff;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .header { 
      background: linear-gradient(135deg, #198754 0%, #157347 100%);
      color: #fff; 
      padding: 28px 24px; 
      text-align: center;
      border-radius: 8px 8px 0 0;
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
      margin-top: 16px;
      margin-bottom: 10px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
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
    .info-box { 
      background: #f8f9fb; 
      padding: 14px; 
      border-left: 4px solid #198754;
      margin: 12px 0; 
      border-radius: 4px; 
    }
    .comments-box {
      background: #f8f9fb;
      padding: 14px;
      border-left: 4px solid #0d6efd;
      margin: 12px 0;
      border-radius: 4px;
      font-size: 14px;
    }
    .btn { 
      display: inline-block; 
      padding: 10px 20px; 
      background: #198754; 
      color: #fff; 
      text-decoration: none; 
      border-radius: 6px;
      font-weight: 600;
      font-size: 14px;
      margin: 12px 0;
    }
    .btn:hover { opacity: 0.9; }
    .footer {
      background-color: #f8f9fb;
      padding: 20px 24px;
      text-align: center;
      border-top: 1px solid #e8ecf1;
    }
    .footer-text { 
      color: #888; 
      font-size: 12px; 
      margin: 0;
      line-height: 1.6;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="email-wrapper">
      <div class="header">
        <h2>Report Status Updated</h2>
      </div>
      <div class="content">
        <p class="greeting">Hi <?php echo htmlspecialchars($studentName); ?>,</p>
        <p class="intro-text">The status of your report has been updated. Please review the details below.</p>

        <div class="section-title">Report Details</div>
        <div class="info-box">
          <div><strong><?php echo htmlspecialchars($title); ?></strong></div>
          <div style="margin-top: 8px;">
            <span style="font-weight: 600; color: #34495e;">New Status:</span>
            <div class="status-badge"><?php echo htmlspecialchars($statusLabel); ?></div>
          </div>
        </div>

        <?php if (!empty($comments)): ?>
          <div class="section-title">Feedback from Reviewer</div>
          <div class="comments-box">
            <?php echo nl2br(htmlspecialchars($comments)); ?>
          </div>
        <?php endif; ?>

        <p style="text-align: center;">
          <a class="btn" href="<?php echo htmlspecialchars($reportUrl); ?>">View Your Report</a>
        </p>

        <p style="font-size: 12px; color: #888;">If you have questions or concerns, please reach out to your school administrator or system support.</p>
      </div>
      <div class="footer">
        <p class="footer-text">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($appName ?? 'Safespeak'); ?>. All rights reserved.</p>
      </div>
    </div>
  </div>
</body>
</html>
