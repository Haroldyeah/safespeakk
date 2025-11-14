<?php
// Variables expected:
// $studentName, $reportTitle, $sessionDate, $counselorName, $notes, $outcome, $appName, $baseUrl
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
      background: linear-gradient(135deg, #0dcaf0 0%, #0aa8c7 100%);
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
    .info-row { 
      margin: 8px 0;
      font-size: 14px;
      padding: 8px 0;
      border-bottom: 1px solid #e8ecf1;
    }
    .info-row:last-child {
      border-bottom: none;
    }
    .info-label { 
      font-weight: 600; 
      color: #34495e;
      display: inline-block;
      width: 140px;
    }
    .info-box { 
      background: #f8f9fb; 
      padding: 14px; 
      border-left: 4px solid #0dcaf0;
      margin: 12px 0; 
      border-radius: 4px; 
    }
    .notes-box {
      background: #f8f9fb;
      padding: 14px;
      border-left: 4px solid #0dcaf0;
      margin: 12px 0;
      border-radius: 4px;
      font-size: 14px;
    }
    .outcome-badge {
      display: inline-block;
      padding: 8px 12px;
      background: #cfe2ff;
      color: #084298;
      border-radius: 4px;
      font-weight: 600;
      font-size: 13px;
      margin: 8px 0;
    }
    .support-text {
      background: #d1ecf1;
      border-left: 4px solid #0dcaf0;
      padding: 12px 14px;
      margin: 14px 0;
      border-radius: 4px;
      font-size: 13px;
      color: #0c5460;
      line-height: 1.6;
    }
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
        <h2>Intervention Session Recorded</h2>
      </div>
      <div class="content">
        <p class="greeting">Hi <?php echo htmlspecialchars($studentName); ?>,</p>
        <p class="intro-text">An intervention session has been recorded for your report. This is part of the support services provided to you.</p>

        <div class="section-title">Session Information</div>
        <div class="info-box">
          <div class="info-row">
            <span class="info-label">Report:</span> <strong><?php echo htmlspecialchars($reportTitle); ?></strong>
          </div>
          <div class="info-row">
            <span class="info-label">Session Date:</span> <?php echo htmlspecialchars($sessionDate); ?>
          </div>
          <?php if (!empty($counselorName)): ?>
            <div class="info-row">
              <span class="info-label">Counselor:</span> <?php echo htmlspecialchars($counselorName); ?>
            </div>
          <?php endif; ?>
        </div>

        <?php if (!empty($outcome)): ?>
          <div class="section-title">Outcome</div>
          <div style="margin-bottom: 12px;">
            <div class="outcome-badge"><?php echo htmlspecialchars($outcome); ?></div>
          </div>
        <?php endif; ?>

        <?php if (!empty($notes)): ?>
          <div class="section-title">Session Notes</div>
          <div class="notes-box">
            <?php echo nl2br(htmlspecialchars($notes)); ?>
          </div>
        <?php endif; ?>

        <div class="support-text">
          💡 This intervention is part of our commitment to supporting your well-being. If you have any questions or need additional support, please reach out to your school counselor or administrator.
        </div>

        <p style="font-size: 12px; color: #888;">For additional resources or concerns, please contact your school's support services.</p>
      </div>
      <div class="footer">
        <p class="footer-text">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($appName ?? 'Capstone Tracker'); ?>. All rights reserved.</p>
      </div>
    </div>
  </div>
</body>
</html>
