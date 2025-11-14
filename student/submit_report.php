<?php
$pageTitle = 'Submit Report';
require_once '../config/config.php';
requireRole('student');

$studentId = $_SESSION['user_id'];
$schoolId = $_SESSION['school_id'];
$error = '';
$success = '';
$dateOfIncident = '';

// Get schools for dropdown
$schools = $db->fetchAll("SELECT id, name FROM schools WHERE status = 'active' ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0) {
    $error = 'The uploaded files are too large. Please try smaller files. (The total size of all files combined may be exceeding the server limit).';
} elseif ($_POST) {
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $selectedSchoolId = (int)$_POST['school_id'];
    $dateOfIncident = $_POST['date_of_incident'] ?? '';
    
    if (empty($title) || empty($description) || empty($selectedSchoolId) || empty($dateOfIncident)) {
        $error = 'Please fill in all required fields.';
    } elseif (!isset($_FILES['report_files']) || empty($_FILES['report_files']['name'][0])) {
        $error = 'Please select at least one file to upload.';
    } elseif (count($_FILES['report_files']['name']) > 3) {
        $error = 'You can upload a maximum of 3 files.';
    } else {
        // Verify selected school exists
        $school = $db->fetchOne("SELECT id FROM schools WHERE id = ? AND status = 'active'", [$selectedSchoolId]);
        
        if (!$school) {
            $error = 'Selected school is not valid.';
        } else {
            // Save report to database first
            $reportData = [
                'title' => $title,
                'description' => $description,
                'bully_name' => sanitizeInput($_POST['bully_name'] ?? ''),
                'date_of_incident' => $dateOfIncident,
                'student_id' => $studentId,
                'school_id' => $selectedSchoolId,
                'status' => 'submitted',
                'submission_date' => date('Y-m-d H:i:s')
            ];
            
            $reportId = $db->insert('reports', $reportData);
            
            if ($reportId) {
                $allFilesUploaded = true;
                $uploadedFilePaths = [];

                // Loop through each uploaded file
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

                        if (!$uploadResult['success']) {
                            $allFilesUploaded = false;
                            // Accumulate errors or set a general error
                            $error = 'Failed to upload one or more files. ' . $uploadResult['error'];
                            break; // Stop processing further files
                        } else {
                            $uploadedFilePaths[] = $uploadResult['file_path'];
                            // Save evidence to database
                            $evidenceData = [
                                'report_id' => $reportId,
                                'file_path' => $uploadResult['file_path'],
                                'file_name' => $uploadResult['file_name'],
                                'file_size' => $uploadResult['file_size']
                            ];
                            $db->insert('report_evidence', $evidenceData);
                        }
                    } else {
                        $allFilesUploaded = false;
                        $error = 'Error with one or more file uploads. Please try again.';
                        break;
                    }
                }

                if ($allFilesUploaded) {
                    // Run analyzer to determine severity and suggested actions
                    try {
                        $analysis = analyze_report([
                            'title' => $title,
                            'description' => $description,
                            'date_of_incident' => $dateOfIncident,
                            'student_id' => $studentId,
                            'school_id' => $selectedSchoolId
                        ], count($uploadedFilePaths));

                        $updateData = [];
                        if (!empty($analysis['severity'])) {
                            $updateData['severity'] = $analysis['severity'];
                        }
                        if (!empty($analysis['suggested_actions'])) {
                            $updateData['recommended_actions'] = $analysis['suggested_actions'];
                        }

                        if (!empty($updateData)) {
                            // Try to persist analysis result; wrap in try/catch in case DB schema doesn't have these columns yet
                            try {
                                $db->update('reports', $updateData, 'id = ?', [$reportId]);
                            } catch (Exception $e) {
                                // ignore - migration may not have been run
                            }
                        }
                    } catch (Throwable $t) {
                        // Analyzer should not break submission; ignore errors quietly
                    }
                    // Send email to school after successful report submission
                    require_once __DIR__ . '/../config/mail.php';
                    require_once __DIR__ . '/../templates/email/load_template.php';
                    $schoolInfo = $db->fetchOne("SELECT name, email FROM schools WHERE id = ?", [$selectedSchoolId]);
                    $studentInfo = $db->fetchOne("SELECT first_name, last_name, email, school_id FROM users WHERE id = ?", [$studentId]);
                    $registeredSchool = $db->fetchOne("SELECT name FROM schools WHERE id = ?", [$studentInfo['school_id']]);
                    if ($schoolInfo && $schoolInfo['email']) {
                        // Fetch SMTP settings for the school
                        $schoolSmtp = $db->fetchOne("SELECT smtp_host, smtp_port, smtp_username, smtp_password, from_email, from_name FROM schools WHERE id = ?", [$selectedSchoolId]);
                        $subject = "New Report Submitted: {$studentInfo['first_name']} {$studentInfo['last_name']}";

                        $reportUrl = rtrim(BASE_URL, '/') . '/school/manage_report.php?id=' . $reportId;
                        $body = load_email_template('report_submitted.php', [
                            'schoolName' => $schoolInfo['name'],
                            'studentName' => $studentInfo['first_name'] . ' ' . $studentInfo['last_name'],
                            'studentEmail' => $studentInfo['email'],
                            'title' => $title,
                            'dateOfIncident' => formatDate($dateOfIncident),
                            'description' => $description,
                            'reportUrl' => $reportUrl,
                            'appName' => APP_NAME,
                            'baseUrl' => BASE_URL
                        ]);

                        sendMail($schoolInfo['email'], $subject, $body, $schoolSmtp['from_email'], $schoolSmtp['from_name']);
                    }
                    logActivity($db, $studentId, 'student', 'submit_report', "Submitted report: $title");
                    redirect('my_reports.php', 'Report submitted successfully! Your report is now awaiting review.', 'success');
                } else {
                    // If any file upload failed, delete the report and all successfully uploaded files
                    $db->delete('reports', 'id = ?', [$reportId]);
                    foreach ($uploadedFilePaths as $path) {
                        deleteFile($path);
                    }
                    // Error message is already set by the loop
                }
            } else {
                $error = 'Failed to submit report. Please try again.';
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
                    <i class="fas fa-plus me-2"></i>Submit Bullying Report
                </h4>
                <p class="text-muted mb-0 mt-2">Confidentially report an incident.</p>
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
                            <option value="Physical Bullying" <?php echo (isset($title) && $title == 'Physical Bullying') ? 'selected' : ''; ?>>Physical Bullying</option>
                            <option value="Verbal Bullying" <?php echo (isset($title) && $title == 'Verbal Bullying') ? 'selected' : ''; ?>>Verbal Bullying</option>
                            <option value="Social Bullying" <?php echo (isset($title) && $title == 'Social Bullying') ? 'selected' : ''; ?>>Social Bullying (e.g., exclusion, rumors)</option>
                            <option value="Cyberbullying" <?php echo (isset($title) && $title == 'Cyberbullying') ? 'selected' : ''; ?>>Cyberbullying (online harassment)</option>
                            <option value="Prejudicial Bullying" <?php echo (isset($title) && $title == 'Prejudicial Bullying') ? 'selected' : ''; ?>>Prejudicial Bullying (based on race, religion, etc.)</option>
                            <option value="Sexual Bullying" <?php echo (isset($title) && $title == 'Sexual Bullying') ? 'selected' : ''; ?>>Sexual Bullying</option>
                            <option value="Other" <?php echo (isset($title) && $title == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                        <div class="invalid-feedback">Please select the type of bullying.</div>
                    </div>

                    <div class="mb-4">
                        <label for="bully_name" class="form-label small fw-semibold">Name of Student(s) Involved</label>
                        <input type="text" class="form-control" id="bully_name" name="bully_name" 
                               placeholder="Enter the name(s) of the student(s) involved..."
                               value="<?php echo htmlspecialchars($bully_name ?? ''); ?>">
                        <div class="form-text text-muted">This information will be kept confidential and helps in addressing the situation appropriately.</div>
                    </div>

                    <div class="mb-4">
                        <label for="description" class="form-label small fw-semibold">Description *</label>
                        <textarea class="form-control" id="description" name="description" rows="5" placeholder="Provide a clear, factual description of the incident..." required><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                        <div class="form-text text-muted">Keep it factual and concise. Include who, when, where, and witnesses if any.</div>
                        <div class="invalid-feedback">Please provide a description.</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="date_of_incident" class="form-label small fw-semibold">Date and Time of Incident *</label>
                            <input type="datetime-local" class="form-control" id="date_of_incident" name="date_of_incident" required value="<?php echo htmlspecialchars($dateOfIncident ?? ''); ?>">
                            <div class="form-text text-muted">If unknown, provide your best estimate.</div>
                            <div class="invalid-feedback">Please provide the date and time of the incident.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="school_id" class="form-label small fw-semibold">Submit To (School) *</label>
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
                            <div class="form-text text-muted">Select the school responsible for reviewing this report.</div>
                            <div class="invalid-feedback">Please select a school.</div>
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
                        <label class="form-label small fw-semibold">Evidence (Files / Photos) *</label>
                        <label for="report_files" id="fileUploadArea" class="border rounded p-3 text-center" style="background:#fafafa; cursor:pointer;">
                            <div id="filePrompt" class="text-muted">
                                <i class="fas fa-cloud-upload-alt fa-2x"></i>
                                <p class="mb-0 mt-2">Tap to select files or drag & drop here</p>
                                <small class="text-muted">PDF, DOC, JPG, PNG, HEIC, HEIF, MP4 and other common formats. Max 50MB per file, up to 3 files.</small>
                            </div>
                            <div id="filePreview" style="display:none; margin-top:12px; text-align:center;"></div>
                        </label>
                        <input type="file" class="form-control d-none" id="report_files" name="report_files[]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.heic,.heif,.mp4,.mov,.avi,.wmv,.mkv" multiple required>
                        <div class="invalid-feedback">Please select at least one file to upload.</div>
                    </div>

                    <div class="mb-3 bg-light p-3 rounded">
                        <h6 class="mb-2 small fw-semibold"><i class="fas fa-info-circle me-2"></i>Reporting Guidelines</h6>
                        <ul class="mb-0 small text-muted">
                            <li>Provide factual details and avoid speculation.</li>
                            <li>Attach any evidence that supports your report.</li>
                            <li>Reports are treated confidentially. You will receive status updates.</li>
                            <li>If you need urgent help, contact local authorities or your school immediately.</li>
                        </ul>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="confirm_submission" required>
                        <label class="form-check-label small" for="confirm_submission">I confirm that the information I am providing is accurate to the best of my knowledge.</label>
                        <div class="invalid-feedback">You must confirm the submission terms.</div>
                    </div>

                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane me-2"></i>Submit Report
                        </button>
                        <a href="dashboard.php" class="btn btn-outline-secondary btn-lg">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileArea = document.getElementById('fileUploadArea');
    const fileInput = document.getElementById('report_files'); // Changed ID
    const filePreview = document.getElementById('filePreview');
    const filePrompt = document.getElementById('filePrompt');
    const description = document.getElementById('description');

    // Character counter under description
    const charCounter = document.createElement('div');
    charCounter.className = 'form-text text-end text-muted';
    description.parentNode.appendChild(charCounter);

    function updateCharCounter() {
        const length = description.value.length;
        charCounter.textContent = `${length} characters`;
        charCounter.className = 'form-text text-end ' + (length < 50 ? 'text-warning' : 'text-muted');
    }
    description.addEventListener('input', updateCharCounter);
    updateCharCounter();

    // Click to open file dialog is now handled by the label\'s "for" attribute
    // fileArea.addEventListener('click', () => fileInput.click());

    // Drag & Drop
    ['dragenter', 'dragover'].forEach(evt => {
        fileArea.addEventListener(evt, (e) => {
            e.preventDefault();
            e.stopPropagation();
            fileArea.classList.add('border-primary');
        });
    });
    ['dragleave', 'drop'].forEach(evt => {
        fileArea.addEventListener(evt, (e) => {
            e.preventDefault();
            e.stopPropagation();
            fileArea.classList.remove('border-primary');
        });
    });

    fileArea.addEventListener('drop', (e) => {
        selectedFiles = Array.from(e.dataTransfer.files);
        fileInput.files = e.dataTransfer.files; // Update file input for form submission
        handleFiles();
    });

    let selectedFiles = []; // To store selected files for preview and removal

    fileInput.addEventListener('change', function(e) {
        selectedFiles = Array.from(this.files); // Convert FileList to Array
        handleFiles();
    });

    function handleFiles() {
        filePreview.innerHTML = ''; // Clear previous previews
        filePrompt.style.display = 'none';
        filePreview.style.display = 'block';

        if (selectedFiles.length === 0) {
            filePrompt.style.display = 'block';
            filePreview.style.display = 'none';
            return;
        }

        if (selectedFiles.length > 3) {
            alert('You can upload a maximum of 3 files.');
            selectedFiles = selectedFiles.slice(0, 3); // Take only the first 3
            // Optionally, clear the file input value to prevent submitting more than 3
            // fileInput.value = ''; // This might prevent valid files from being submitted
            // Re-assign files to input if we sliced them
            const dataTransfer = new DataTransfer();
            selectedFiles.forEach(file => dataTransfer.items.add(file));
            fileInput.files = dataTransfer.files;
        }

        selectedFiles.forEach((file, index) => {
            const maxSize = 50 * 1024 * 1024; // 50MB
            if (file.size > maxSize) {
                alert(`File ${file.name} is too large. Maximum allowed is 50MB.`);
                return; // Skip this file
            }

            const fileExt = file.name.split('.').pop().toLowerCase();
            const imageTypes = ['jpg','jpeg','png','gif','heic','heif']; // Added HEIC/HEIF
            const videoTypes = ['mp4','webm','ogg','mov','m4v','avi','wmv','flv'];

            const fileContainer = document.createElement('div');
            fileContainer.className = 'd-flex align-items-center mb-2 p-2 border rounded';
            fileContainer.style.backgroundColor = '#f8f9fa';

            let previewContent;
            if (imageTypes.includes(fileExt)) {
                const img = document.createElement('img');
                img.style.maxWidth = '80px';
                img.style.maxHeight = '80px';
                img.style.borderRadius = '4px';
                
                // Special handling for HEIC/HEIF files
                if (['heic', 'heif'].includes(fileExt)) {
                    // HEIC/HEIF files might not display directly in browsers
                    // Show a placeholder image icon instead
                    const icon = document.createElement('div');
                    icon.className = 'text-center';
                    icon.style.width = '80px';
                    icon.style.height = '80px';
                    icon.style.display = 'flex';
                    icon.style.alignItems = 'center';
                    icon.style.justifyContent = 'center';
                    icon.style.backgroundColor = '#e9ecef';
                    icon.style.borderRadius = '4px';
                    icon.innerHTML = '<i class="fas fa-image fa-2x text-muted" title="HEIC/HEIF Image"></i>';
                    previewContent = icon;
                } else {
                    img.src = URL.createObjectURL(file);
                    previewContent = img;
                }
            } else if (videoTypes.includes(fileExt)) {
                const video = document.createElement('video');
                video.controls = false; // No controls for small preview
                video.style.maxWidth = '80px';
                video.style.maxHeight = '80px';
                video.src = URL.createObjectURL(file);
                previewContent = video;
            } else {
                const icon = document.createElement('i');
                icon.className = 'fas fa-file fa-2x text-muted me-2';
                previewContent = icon;
            }

            const fileInfo = document.createElement('div');
            fileInfo.className = 'flex-grow-1 ms-2';
            fileInfo.innerHTML = `<strong>${file.name}</strong><br><small>${(file.size/1024/1024).toFixed(2)} MB</small>`;

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-sm btn-outline-danger ms-auto';
            removeBtn.innerHTML = '<i class="fas fa-times"></i>';
            removeBtn.addEventListener('click', () => {
                selectedFiles.splice(index, 1); // Remove file from array
                // Update file input's FileList
                const dataTransfer = new DataTransfer();
                selectedFiles.forEach(f => dataTransfer.items.add(f));
                fileInput.files = dataTransfer.files;
                handleFiles(); // Re-render previews
            });

            fileContainer.appendChild(previewContent);
            fileContainer.appendChild(fileInfo);
            fileContainer.appendChild(removeBtn);
            filePreview.appendChild(fileContainer);
        });
    }


    // Form submission UX
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        if (this.checkValidity()) {
            const confirmed = confirm('Are you sure you want to submit this report? Once submitted, you cannot modify it until review is complete.');
            if (!confirmed) {
                e.preventDefault();
                return false;
            }
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
            submitBtn.disabled = true;
            setTimeout(() => { submitBtn.innerHTML = originalText; submitBtn.disabled = false; }, 30000);
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>