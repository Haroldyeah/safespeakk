<?php
// Variables expected:
// $studentName, $reportTitle, $sessionDate, $counselorName, $notes, $outcome, $appName, $baseUrl
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <!-- Inlined CSS for better email client compatibility -->
</head>
<body style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; color: #2c3e50; line-height: 1.6; background-color: #f5f7fa; margin: 0; padding: 0;">
  <div style="box-sizing: border-box; width: 100%; max-width: 650px; margin: 0 auto; padding: 16px;">
    <div style="box-sizing: border-box; background-color: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
      <div style="box-sizing: border-box; background: linear-gradient(135deg, #0dcaf0 0%, #0aa8c7 100%); color: #fff; padding: 28px 24px; text-align: center; border-radius: 8px 8px 0 0;">
        <h2 style="box-sizing: border-box; margin: 0; font-size: 24px; font-weight: 600; letter-spacing: -0.5px;">Intervention Session Recorded</h2>
      </div>
      <div style="box-sizing: border-box; padding: 28px 24px;">
        <p style="box-sizing: border-box; font-size: 16px; margin-bottom: 12px; font-weight: 500;">Hi <?php echo htmlspecialchars($studentName); ?>,</p>
        <p style="box-sizing: border-box; color: #555; margin-bottom: 20px; font-size: 14px; line-height: 1.7;">An intervention session has been recorded for your report. This is part of the support services provided to you.</p>

        <div style="box-sizing: border-box; font-size: 14px; font-weight: 700; color: #2c3e50; margin-top: 16px; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;">Session Information</div>
        <div style="box-sizing: border-box; background: #f8f9fb; padding: 14px; border-left: 4px solid #0dcaf0; margin: 12px 0; border-radius: 4px;">
          <div style="box-sizing: border-box; margin: 8px 0; font-size: 14px; padding: 8px 0; border-bottom: 1px solid #e8ecf1;">
            <span style="box-sizing: border-box; font-weight: 600; color: #34495e; display: inline-block; width: 140px;">Report:</span> <strong style="box-sizing: border-box;"><?php echo htmlspecialchars($reportTitle); ?></strong>
          </div>
          <div style="box-sizing: border-box; margin: 8px 0; font-size: 14px; padding: 8px 0; border-bottom: 1px solid #e8ecf1;">
            <span style="box-sizing: border-box; font-weight: 600; color: #34495e; display: inline-block; width: 140px;">Session Date:</span> <?php echo htmlspecialchars($sessionDate); ?>
          </div>
          <?php if (!empty($counselorName)): ?>
            <div style="box-sizing: border-box; margin: 8px 0; font-size: 14px; padding: 8px 0; border-bottom: none;">
              <span style="box-sizing: border-box; font-weight: 600; color: #34495e; display: inline-block; width: 140px;">Counselor:</span> <?php echo htmlspecialchars($counselorName); ?>
            </div>
          <?php endif; ?>
        </div>

        <?php if (!empty($outcome)): ?>
          <div style="box-sizing: border-box; font-size: 14px; font-weight: 700; color: #2c3e50; margin-top: 16px; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;">Outcome</div>
          <div style="margin-bottom: 12px;">
            <div style="box-sizing: border-box; display: inline-block; padding: 8px 12px; background: #cfe2ff; color: #084298; border-radius: 4px; font-weight: 600; font-size: 13px; margin: 8px 0;"><?php echo htmlspecialchars($outcome); ?></div>
          </div>
        <?php endif; ?>

        <?php if (!empty($notes)): ?>
          <div style="box-sizing: border-box; font-size: 14px; font-weight: 700; color: #2c3e50; margin-top: 16px; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;">Session Notes</div>
          <div style="box-sizing: border-box; background: #f8f9fb; padding: 14px; border-left: 4px solid #0dcaf0; margin: 12px 0; border-radius: 4px; font-size: 14px;">
            <?php echo nl2br(htmlspecialchars($notes)); ?>
          </div>
        <?php endif; ?>

        <div style="box-sizing: border-box; background: #d1ecf1; border-left: 4px solid #0dcaf0; padding: 12px 14px; margin: 14px 0; border-radius: 4px; font-size: 13px; color: #0c5460; line-height: 1.6;">
          💡 This intervention is part of our commitment to supporting your well-being. If you have any questions or need additional support, please reach out to your school counselor or administrator.
        </div>

        <p style="font-size: 12px; color: #888;">For additional resources or concerns, please contact your school's support services.</p>
      </div>
      <div style="box-sizing: border-box; background-color: #f8f9fb; padding: 20px 24px; text-align: center; border-top: 1px solid #e8ecf1;">
        <p style="box-sizing: border-box; color: #888; font-size: 12px; margin: 0; line-height: 1.6;">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($appName ?? 'Safespeak'); ?>. All rights reserved.</p>
      </div>
    </div>
  </div>
</body>
</html>
