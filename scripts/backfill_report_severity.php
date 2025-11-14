<?php
// Simple backfill script to compute severity and suggested actions for existing reports.
// Run from command line: php scripts/backfill_report_severity.php
require_once __DIR__ . '/../config/config.php';

// Fetch all reports
$reports = $db->fetchAll("SELECT r.*, u.first_name, u.last_name FROM reports r LEFT JOIN users u ON r.student_id = u.id");

if (empty($reports)) {
    echo "No reports found.\n";
    exit(0);
}

$total = count($reports);
$updated = 0;
foreach ($reports as $r) {
    $evidenceCount = $db->fetchOne("SELECT COUNT(*) as count FROM report_evidence WHERE report_id = ?", [$r['id']])['count'] ?? 0;
    try {
        $analysis = analyze_report($r, (int)$evidenceCount);
        $updateData = [];
        if (!empty($analysis['severity'])) $updateData['severity'] = $analysis['severity'];
        if (!empty($analysis['suggested_actions'])) $updateData['recommended_actions'] = $analysis['suggested_actions'];
        if (!empty($updateData)) {
            try {
                $db->update('reports', $updateData, 'id = ?', [$r['id']]);
                $updated++;
                echo "Updated report #{$r['id']} (severity={$analysis['severity']})\n";
            } catch (Exception $e) {
                echo "Failed to update report #{$r['id']}: " . $e->getMessage() . "\n";
            }
        }
    } catch (Throwable $t) {
        echo "Analyzer failed for report #{$r['id']}: " . $t->getMessage() . "\n";
    }
}

echo "Done. Processed: $total, updated: $updated\n";
