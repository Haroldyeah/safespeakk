<?php
// Variables expected:
// $schoolName, $studentName, $studentEmail, $title, $dateOfIncident, $description, $reportUrl, $appName, $baseUrl
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
      background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
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
    .info-box { 
      background: #f8f9fb; 
      padding: 14px; 
      border-left: 4px solid #0d6efd;
      margin: 12px 0; 
      border-radius: 4px; 
    }
    .info-row { 
      margin: 8px 0;
      font-size: 14px;
    }
    .info-label { 
      font-weight: 600; 
      color: #34495e;
      display: inline-block;
      width: 140px;
    }
    .btn { 
      display: inline-block; 
      padding: 10px 20px; 
      background: #0d6efd; 
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
    .footer-link {
      color: #0d6efd;
      text-decoration: none;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="email-wrapper">
      <div class="header">
        <h2>New Report Submitted</h2>
      </div>
      <div class="content">
        <p class="greeting">Good day,</p>
        <p class="intro-text">A new report has been submitted in <?php echo htmlspecialchars($appName ?? 'Capstone Tracker'); ?> and requires your attention.</p>

        <div class="section-title">Reporter Details</div>
        <div class="info-box">
          <div class="info-row">
            <span class="info-label">Name:</span> <strong><?php echo htmlspecialchars($studentName); ?></strong>
          </div>
          <div class="info-row">
            <span class="info-label">Email:</span> <?php echo htmlspecialchars($studentEmail); ?>
          </div>
        </div>

        <div class="section-title">Report Information</div>
        <div class="info-box">
          <div class="info-row">
            <span class="info-label">Type:</span> <?php echo htmlspecialchars($title); ?>
          </div>
          <div class="info-row">
            <span class="info-label">Date of Incident:</span> <?php echo htmlspecialchars($dateOfIncident); ?>
          </div>
        </div>

        <div class="section-title">Description</div>
        <div class="info-box">
          <?php echo nl2br(htmlspecialchars($description)); ?>
        </div>

        <p style="text-align: center;">
          <a class="btn" href="<?php echo htmlspecialchars($reportUrl); ?>">Review Report in System</a>
        </p>

        <p style="font-size: 12px; color: #888;">For questions or concerns, please contact your system administrator.</p>
      </div>
      <div class="footer">
        <p class="footer-text">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($appName ?? 'Safespeak'); ?>. All rights reserved.</p>
      </div>
    </div>
  </div>
</body>
</html>
