<?php
$pageTitle = 'Submit Report';
require_once '../config/config.php';
requireRole('student');

$studentId = $_SESSION['user_id'];
$schoolId = $_SESSION['school_id'];
$error = '';
$success = '';

// Get schools for dropdown
$schools = $db->fetchAll("SELECT id, name FROM schools WHERE status = 'active' ORDER BY name");

if ($_POST) {
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $selectedSchoolId = (int)$_POST['school_id'];
    
    if (empty($title) || empty($description) || empty($selectedSchoolId)) {
        $error = 'Please fill in all required fields.';
    } elseif (!isset($_FILES['report_file']) || $_FILES['report_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please select a file to upload.';
    } else {
        // Verify selected school exists
        $school = $db->fetchOne("SELECT id FROM schools WHERE id = ? AND status = 'active'", [$selectedSchoolId]);
        
        if (!$school) {
            $error = 'Selected school is not valid.';
        } else {
            // Handle file upload
            $uploadResult = uploadFile($_FILES['report_file']);
            
            if (!$uploadResult['success']) {
                $error = $uploadResult['error'];
            } else {
                // Save report to database
                $reportData = [
                    'title' => $title,
                    'description' => $description,
                    'student_id' => $studentId,
                    'school_id' => $selectedSchoolId,
                    'file_path' => $uploadResult['file_path'],
                    'file_name' => $uploadResult['file_name'],
                    'file_size' => $uploadResult['file_size'],
                    'status' => 'submitted'
                ];
                
                $reportId = $db->insert('reports', $reportData);
                
                if ($reportId) {
                    logActivity($db, $studentId, 'student', 'submit_report', "Submitted report: $title");
                    
                    redirect('my_reports.php', 'Report submitted successfully! Your report is now awaiting review.', 'success');
                } else {
                    // Delete uploaded file if database insert failed
                    deleteFile($uploadResult['file_path']);
                    $error = 'Failed to submit report. Please try again.';
                }
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
                    <i class="fas fa-plus me-2"></i>Submit Capstone Report
                </h4>
                <p class="text-muted mb-0 mt-2">Upload your capstone report for review and evaluation</p>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div class="form-group">
                        <label for="title" class="form-label">Report Title *</label>
                        <input type="text" class="form-control" id="title" name="title" 
                               value="<?php echo htmlspecialchars($title ?? ''); ?>"
                               placeholder="Enter a descriptive title for your capstone report" required>
                        <div class="invalid-feedback">Please enter a report title.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">Description *</label>
                        <textarea class="form-control" id="description" name="description" rows="4" 
                                  placeholder="Provide a brief description of your capstone project..." required><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                        <div class="invalid-feedback">Please provide a description.</div>
                        <div class="form-text">Describe the main objectives, methodology, and key findings of your capstone project.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="school_id" class="form-label">Select School *</label>
                        <select class="form-select" id="school_id" name="school_id" required>
                            <option value="">Choose the school for submission</option>
                            <?php foreach ($schools as $school): ?>
                                <option value="<?php echo $school['id']; ?>" 
                                        <?php echo (isset($selectedSchoolId) && $selectedSchoolId == $school['id']) ? 'selected' : 
                                                  (isset($_SESSION['school_id']) && $_SESSION['school_id'] == $school['id'] ? 'selected' : ''); ?>>
                                    <?php echo htmlspecialchars($school['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select a school.</div>
                        <div class="form-text">Select the school where you want to submit your capstone report.</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="report_file" class="form-label">Report File *</label>
                        <div class="file-upload-area" id="fileUploadArea">
                            <i class="fas fa-cloud-upload-alt file-upload-icon"></i>
                            <h6 class="mt-2 mb-1">Choose File or Drag & Drop</h6>
                            <p class="text-muted mb-2">PDF, DOC, DOCX files only</p>
                            <p class="text-muted mb-0"><small>Maximum file size: 50MB</small></p>
                        </div>
                        <input type="file" class="form-control d-none" id="report_file" name="report_file" 
                               accept=".pdf,.doc,.docx" required>
                        <div class="invalid-feedback">Please select a file to upload.</div>
                    </div>
                    
                    <div class="academic-section">
                        <h6><i class="fas fa-info-circle me-2"></i>Submission Guidelines</h6>
                        <ul class="mb-0">
                            <li>Ensure your report is in final format before submission</li>
                            <li>Include all required sections as per your institution's guidelines</li>
                            <li>File should be properly formatted and readable</li>
                            <li>Once submitted, you cannot modify the file until review is complete</li>
                            <li>You will receive notifications about the review status</li>
                        </ul>
                    </div>
                    
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="confirm_submission" required>
                        <label class="form-check-label" for="confirm_submission">
                            I confirm that this is my original work and I understand that it will be reviewed according to academic standards. *
                        </label>
                        <div class="invalid-feedback">You must confirm the submission terms.</div>
                    </div>
                    
                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane me-2"></i>Submit Report
                        </button>
                        <a href="dashboard.php" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // File upload area functionality is handled in main.js
    // Additional form-specific functionality can be added here
    
    // Character counter for description
    const description = document.getElementById('description');
    const charCounter = document.createElement('div');
    charCounter.className = 'form-text text-end';
    charCounter.id = 'charCounter';
    description.parentNode.appendChild(charCounter);
    
    function updateCharCounter() {
        const length = description.value.length;
        charCounter.textContent = `${length} characters`;
        
        if (length < 50) {
            charCounter.className = 'form-text text-end text-warning';
        } else {
            charCounter.className = 'form-text text-end text-muted';
        }
    }
    
    description.addEventListener('input', updateCharCounter);
    updateCharCounter();
    
    // Form submission confirmation
    document.querySelector('form').addEventListener('submit', function(e) {
        if (this.checkValidity()) {
            const confirmed = confirm('Are you sure you want to submit this report? Once submitted, you cannot modify it until the review is complete.');
            if (!confirmed) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            const submitBtn = document.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
            submitBtn.disabled = true;
            
            // Re-enable button after some time in case of errors
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 30000);
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
