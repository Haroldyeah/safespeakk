
<?php
$pageTitle = 'Edit Report';
require_once '../config/config.php';
requireRole('student');

$studentId = $_SESSION['user_id'];
$error = '';
$success = '';

// Get Report ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('my_reports.php', 'Invalid report ID.', 'danger');
}
$reportId = (int)$_GET['id'];

// Fetch the report from the database
$report = $db->fetchOne("SELECT * FROM reports WHERE id = ? AND student_id = ?", [$reportId, $studentId]);

// If report not found or doesn't belong to the student, redirect
if (!$report) {
    redirect('my_reports.php', 'Report not found or you do not have permission to edit it.', 'danger');
}

// Fetch existing evidence
$existing_evidence = $db->fetchAll("SELECT id, file_name, file_path FROM report_evidence WHERE report_id = ?", [$reportId]);

// Get schools for dropdown
$schools = $db->fetchAll("SELECT id, name FROM schools WHERE status = 'active' ORDER BY name");

// Initialize variables with report data
$title = $report['title'];
$description = $report['description'];
$dateOfIncident = $report['date_of_incident'];
$selectedSchoolId = $report['school_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0) {
    $error = 'The uploaded files are too large. Please try smaller files. (The total size of all files combined may be exceeding the server limit).';
} elseif ($_POST) {
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $selectedSchoolId = (int)$_POST['school_id'];
    $dateOfIncident = $_POST['date_of_incident'] ?? '';
    
    if (empty($title) || empty($description) || empty($selectedSchoolId) || empty($dateOfIncident)) {
        $error = 'Please fill in all required fields.';
    } else {
        // Verify selected school exists
        $school = $db->fetchOne("SELECT id FROM schools WHERE id = ? AND status = 'active'", [$selectedSchoolId]);
        
        if (!$school) {
            $error = 'Selected school is not valid.';
        } else {
            // Update report data
            $reportData = [
                'title' => $title,
                'description' => $description,
                'date_of_incident' => $dateOfIncident,
                'school_id' => $selectedSchoolId,
            ];
            
            $db->update('reports', $reportData, 'id = ?', [$reportId]);

            // Re-run analyzer on update and attempt to persist severity/recommended actions
            try {
                $analysis = analyze_report([
                    'title' => $title,
                    'description' => $description,
                    'date_of_incident' => $dateOfIncident,
                    'student_id' => $report['student_id'] ?? null,
                    'school_id' => $selectedSchoolId
                ], 0);

                $updateData = [];
                if (!empty($analysis['severity'])) $updateData['severity'] = $analysis['severity'];
                if (!empty($analysis['suggested_actions'])) $updateData['recommended_actions'] = $analysis['suggested_actions'];

                if (!empty($updateData)) {
                    try {
                        $db->update('reports', $updateData, 'id = ?', [$reportId]);
                    } catch (Exception $e) {
                        // ignore if column missing
                    }
                }
            } catch (Throwable $t) {
                // ignore analyzer failures
            }
            
            // Handle file uploads
            if (isset($_FILES['report_files']) && !empty($_FILES['report_files']['name'][0])) {
                if (count($_FILES['report_files']['name']) > MAX_EVIDENCE_FILES) {
                    $error = 'You can upload a maximum of ' . MAX_EVIDENCE_FILES . ' files.';
                } elseif (count($_FILES['report_files']['name']) < count($_FILES['report_files']['tmp_name'])) { // Check if some files were not fully uploaded
                    $error = 'Some files were not uploaded. This might be due to server limits (e.g., max_file_uploads in php.ini). Please try uploading fewer files or contact support.';
                } else {
                    $allFilesUploaded = true;
                    foreach ($_FILES['report_files']['name'] as $key => $fileName) {
                        if ($_FILES['report_files']['error'][$key] === UPLOAD_ERR_OK) {
                            $file = [
                                'name' => $_FILES['report_files']['name'][$key],
                                'type' => $_FILES['report_files']['type'][$key],
                                'tmp_name' => $_FILES['report_files']['tmp_name'][$key],
                                'error' => $_FILES['report_files']['error'][$key],
                                'size' => $_FILES['report_files']['size'][$key]
                            ];
                            $uploadResult = uploadFile($file);

                            if ($uploadResult['success']) {
                                $evidenceData = [
                                    'report_id' => $reportId,
                                    'file_path' => $uploadResult['file_path'],
                                    'file_name' => $uploadResult['file_name'],
                                    'file_size' => $uploadResult['file_size']
                                ];
                                $db->insert('report_evidence', $evidenceData);
                            } else {
                                $allFilesUploaded = false;
                                $error = 'Failed to upload one or more files. ' . $uploadResult['error'];
                                break;
                            }
                        } else {
                            $allFilesUploaded = false;
                            $phpUploadErrors = [
                                UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                                UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
                                UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
                                UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
                                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
                                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                                UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
                            ];
                            $error = $phpUploadErrors[$_FILES['report_files']['error'][$key]] ?? 'Unknown upload error.';
                            break;
                        }
                    }
                }
            }
            
            if (empty($error)) {
                logActivity($db, $studentId, 'student', 'edit_report', "Edited report: $title");
                redirect('my_reports.php', 'Report updated successfully!', 'success');
            }
        }
    }
}

require_once '../includes/header.php';
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">
                    <i class="fas fa-edit me-2"></i>Edit Bullying Report
                </h4>
                <p class="text-muted mb-0 mt-2">Update the details of your report.</p>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div class="mb-4">
                        <label for="title" class="form-label small fw-semibold">Type of Bullying *</label>
                        <select class="form-select" id="title" name="title" required>
                            <option value="">Select the type of bullying</option>
                            <option value="Physical Bullying" <?php echo ($title == 'Physical Bullying') ? 'selected' : ''; ?>>Physical Bullying</option>
                            <option value="Verbal Bullying" <?php echo ($title == 'Verbal Bullying') ? 'selected' : ''; ?>>Verbal Bullying</option>
                            <option value="Social Bullying" <?php echo ($title == 'Social Bullying') ? 'selected' : ''; ?>>Social Bullying (e.g., exclusion, rumors)</option>
                            <option value="Cyberbullying" <?php echo ($title == 'Cyberbullying') ? 'selected' : ''; ?>>Cyberbullying (online harassment)</option>
                            <option value="Prejudicial Bullying" <?php echo ($title == 'Prejudicial Bullying') ? 'selected' : ''; ?>>Prejudicial Bullying (based on race, religion, etc.)</option>
                            <option value="Sexual Bullying" <?php echo ($title == 'Sexual Bullying') ? 'selected' : ''; ?>>Sexual Bullying</option>
                            <option value="Other" <?php echo ($title == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                        <div class="invalid-feedback">Please select the type of bullying.</div>
                    </div>

                    <div class="mb-4">
                        <label for="description" class="form-label small fw-semibold">Description *</label>
                        <textarea class="form-control" id="description" name="description" rows="5" placeholder="Provide a clear, factual description of the incident..." required><?php echo htmlspecialchars($description); ?></textarea>
                        <div class="form-text text-muted">Keep it factual and concise. Include who, when, where, and witnesses if any.</div>
                        <div class="invalid-feedback">Please provide a description.</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="date_of_incident" class="form-label small fw-semibold">Date and Time of Incident *</label>
                            <input type="datetime-local" class="form-control" id="date_of_incident" name="date_of_incident" required value="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($dateOfIncident))); ?>">
                            <div class="form-text text-muted">If unknown, provide your best estimate.</div>
                            <div class="invalid-feedback">Please provide the date and time of the incident.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="school_id" class="form-label small fw-semibold">Submit To (School) *</label>
                            <select class="form-select" id="school_id" name="school_id" required>
                                <option value="">Choose the school for submission</option>
                                <?php foreach ($schools as $school): ?>
                                    <option value="<?php echo $school['id']; ?>" <?php echo ($selectedSchoolId == $school['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($school['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text text-muted">Select the school responsible for reviewing this report.</div>
                            <div class="invalid-feedback">Please select a school.</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Existing Evidence</label>
                        <div class="row">
                            <?php foreach ($existing_evidence as $evidence): ?>
                                <div class="col-md-4 mb-2">
                                    <a href="<?php echo BASE_URL . 'uploads/' . $evidence['file_path']; ?>" target="_blank"> <?php echo htmlspecialchars($evidence['file_name']); ?></a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <style>
                        @media (max-width: 767.98px) {
                            #fileUploadArea {
                                display: none;
                            }
                            #report_files {
                                display: block !important;
                            }
                        }
                        #fileUploadArea {
                            max-width: 500px;
                            margin: 0 auto;
                        }
                    </style>
                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Add More Evidence (Files / Photos)</label>
                        <label for="report_files" id="fileUploadArea" class="border rounded p-3 text-center" style="background:#fafafa; cursor:pointer;">
                            <div id="filePrompt" class="text-muted">
                                <i class="fas fa-cloud-upload-alt fa-2x"></i>
                                <p class="mb-0 mt-2">Tap to select files or drag & drop here</p>
                                <small class="text-muted">PDF, DOC, JPG, PNG, MP4 and other common formats. Max 50MB per file, up to <?php echo MAX_EVIDENCE_FILES; ?> files.</small>
                            </div>
                            <div id="filePreview" style="display:none; margin-top:12px; text-align:center;"></div>
                        </label>
                        <input type="file" class="form-control d-none" id="report_files" name="report_files[]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.mp4,.mov,.avi,.wmv,.mkv" multiple>
                        <div class="invalid-feedback">Please select at least one file to upload.</div>
                    </div>

                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>Update Report
                        </button>
                        <a href="my_reports.php" class="btn btn-outline-secondary btn-lg">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileArea = document.getElementById('fileUploadArea');
    const fileInput = document.getElementById('report_files');
    const filePreview = document.getElementById('filePreview');
    const filePrompt = document.getElementById('filePrompt');

    fileInput.addEventListener('change', function(e) {
        handleFiles(this.files);
    });

    function handleFiles(files) {
        filePreview.innerHTML = '';
        filePrompt.style.display = 'none';
        filePreview.style.display = 'block';

        if (files.length === 0) {
            filePrompt.style.display = 'block';
            filePreview.style.display = 'none';
            return;
        }

        Array.from(files).forEach(file => {
            const fileContainer = document.createElement('div');
            fileContainer.className = 'd-flex align-items-center mb-2 p-2 border rounded';
            fileContainer.innerHTML = `<strong>${file.name}</strong><small class="ms-2">(${(file.size / 1024 / 1024).toFixed(2)} MB)</small>`;
            filePreview.appendChild(fileContainer);
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
