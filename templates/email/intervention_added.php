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
    body { font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial, sans-serif; color: #333; }
    .container { max-width: 680px; margin: 0 auto; padding: 24px; }
    .header { background:#0d6efd; color: #fff; padding: 18px; border-radius: 6px 6px 0 0; }
    .content { border: 1px solid #e9ecef; border-top: none; padding: 20px; background: #fff; }
    .info-box { background:#f8f9fa; padding:12px; border-left: 4px solid #0d6efd; margin: 12px 0; border-radius: 4px; }
    .info-row { margin: 8px 0; }
    .info-label { font-weight: 600; color: #495057; }
    .btn { display:inline-block; padding:10px 16px; background:#0d6efd; color:#fff; text-decoration:none; border-radius:6px; }
    .muted { color:#6c757d; font-size:13px; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h2 style="margin:0;font-weight:600;"><?php echo htmlspecialchars($appName ?? 'Application'); ?></h2>
    </div>
    <div class="content">
      <p>Hi <?php echo htmlspecialchars($studentName); ?>,</p>
      <p>An intervention session has been recorded for your report titled <strong><?php echo htmlspecialchars($reportTitle); ?></strong>.</p>

      <div class="info-box">
        <div class="info-row">
          <span class="info-label">Session Date:</span> <?php echo htmlspecialchars($sessionDate); ?>
        </div>
        <?php if (!empty($counselorName)): ?>
          <div class="info-row">
            <span class="info-label">Counselor:</span> <?php echo htmlspecialchars($counselorName); ?>
          </div>
        <?php endif; ?>
        <?php if (!empty($outcome)): ?>
          <div class="info-row">
            <span class="info-label">Outcome:</span> <?php echo htmlspecialchars($outcome); ?>
          </div>
        <?php endif; ?>
      </div>

      <?php if (!empty($notes)): ?>
        <p><strong>Session Notes:</strong></p>
        <div style="background:#f8f9fa;padding:12px;border-radius:6px;margin:8px 0;"><?php echo nl2br(htmlspecialchars($notes)); ?></div>
      <?php endif; ?>

      <p class="muted">This intervention is part of the support services provided to help address your report. If you have any questions or concerns, please contact your school's counselor or administrator.</p>
      <hr>
      <p class="muted" style="font-size:12px">This message was sent by <?php echo htmlspecialchars($appName ?? 'the system'); ?> — <a href="<?php echo htmlspecialchars($baseUrl); ?>"><?php echo htmlspecialchars($baseUrl); ?></a></p>
    </div>
  </div>
</body>
</html>
