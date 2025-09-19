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
    body { font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial, sans-serif; color: #333; }
    .container { max-width: 680px; margin: 0 auto; padding: 24px; }
    .header { background:#198754; color: #fff; padding: 18px; border-radius: 6px 6px 0 0; }
    .content { border: 1px solid #e9ecef; border-top: none; padding: 20px; background: #fff; }
    .btn { display:inline-block; padding:10px 16px; background:#198754; color:#fff; text-decoration:none; border-radius:6px; }
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
      <p>The status of your report titled <strong><?php echo htmlspecialchars($title); ?></strong> has been updated to <strong><?php echo htmlspecialchars($statusLabel); ?></strong>.</p>
      <?php if (!empty($comments)): ?>
        <p><strong>Comments from reviewer:</strong></p>
        <div style="background:#f8f9fa;padding:12px;border-radius:6px;margin:8px 0;"><?php echo nl2br(htmlspecialchars($comments)); ?></div>
      <?php endif; ?>

      <p>You can view the full details and any next steps by visiting your report:</p>
      <p><a class="btn" href="<?php echo htmlspecialchars($reportUrl); ?>">View My Report</a></p>

      <p class="muted">If you did not expect this change or need support, please contact your school's admin or system administrator.</p>
      <hr>
      <p class="muted" style="font-size:12px">This message was sent by <?php echo htmlspecialchars($appName ?? 'the system'); ?> — <a href="<?php echo htmlspecialchars($baseUrl); ?>"><?php echo htmlspecialchars($baseUrl); ?></a></p>
    </div>
  </div>
</body>
</html>
