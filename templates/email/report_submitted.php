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
    body { font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial, sans-serif; color: #333; }
    .container { max-width: 680px; margin: 0 auto; padding: 24px; }
    .header { background:#0d6efd; color: #fff; padding: 18px; border-radius: 6px 6px 0 0; }
    .content { border: 1px solid #e9ecef; border-top: none; padding: 20px; background: #fff; }
    .btn { display:inline-block; padding:10px 16px; background:#0d6efd; color:#fff; text-decoration:none; border-radius:6px; }
    .meta { background:#f8f9fa; padding:12px; border-radius:6px; margin:12px 0; }
    table { width:100%; border-collapse:collapse; }
    td { vertical-align: top; padding:6px 0; }
    .muted { color:#6c757d; font-size:13px; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h2 style="margin:0;font-weight:600;"><?php echo htmlspecialchars($appName ?? 'Application'); ?></h2>
    </div>
    <div class="content">
      <p>Dear <?php echo htmlspecialchars($schoolName); ?> Team,</p>
      <p>A new report has been submitted by <strong><?php echo htmlspecialchars($studentName); ?></strong> (<?php echo htmlspecialchars($studentEmail); ?>).</p>

      <div class="meta">
        <table>
          <tr><td style="width:160px;"><strong>Report Type</strong></td><td><?php echo htmlspecialchars($title); ?></td></tr>
          <tr><td><strong>Date of Incident</strong></td><td><?php echo htmlspecialchars($dateOfIncident); ?></td></tr>
          <tr><td><strong>Description</strong></td><td><?php echo nl2br(htmlspecialchars($description)); ?></td></tr>
        </table>
      </div>

      <p>Please review the report in your <?php echo htmlspecialchars($appName ?? 'dashboard'); ?>.</p>

      <p><a class="btn" href="<?php echo htmlspecialchars($reportUrl); ?>">View Report</a></p>

      <p class="muted">If you have any questions about this notification, please contact your system administrator.</p>
      <hr>
      <p class="muted" style="font-size:12px">This message was sent by <?php echo htmlspecialchars($appName ?? 'the system'); ?> — <a href="<?php echo htmlspecialchars($baseUrl); ?>"><?php echo htmlspecialchars($baseUrl); ?></a></p>
    </div>
  </div>
</body>
</html>
