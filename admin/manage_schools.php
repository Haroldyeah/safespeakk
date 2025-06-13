<?php
$pageTitle = 'Manage Schools';
require_once '../config/config.php';
requireRole('admin');

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_school') {
        $name = sanitizeInput($_POST['name']);
        $code = sanitizeInput($_POST['code']);
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'];
        $contactPerson = sanitizeInput($_POST['contact_person']);
        $phone = sanitizeInput($_POST['phone']);
        $address = sanitizeInput($_POST['address']);
        
        if (empty($name) || empty($code) || empty($email) || empty($password)) {
            $message = 'Please fill in all required fields.';
            $messageType = 'error';
        } else {
            // Check if school code or email already exists
            $existing = $db->fetchOne(
                "SELECT id FROM schools WHERE code = ? OR email = ?",
                [$code, $email]
            );
            
            if ($existing) {
                $message = 'School code or email already exists.';
                $messageType = 'error';
            } else {
                $schoolData = [
                    'name' => $name,
                    'code' => $code,
                    'email' => $email,
                    'password' => hashPassword($password),
                    'contact_person' => $contactPerson,
                    'phone' => $phone,
                    'address' => $address
                ];
                
                $schoolId = $db->insert('schools', $schoolData);
                
                if ($schoolId) {
                    logActivity($db, $_SESSION['user_id'], 'admin', 'add_school', "Added school: $name");
                    $message = 'School added successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to add school.';
                    $messageType = 'error';
                }
            }
        }
    } elseif ($action === 'update_school') {
        $schoolId = (int)$_POST['school_id'];
        $name = sanitizeInput($_POST['name']);
        $email = sanitizeInput($_POST['email']);
        $contactPerson = sanitizeInput($_POST['contact_person']);
        $phone = sanitizeInput($_POST['phone']);
        $address = sanitizeInput($_POST['address']);
        $status = $_POST['status'];
        
        if (empty($name) || empty($email)) {
            $message = 'Please fill in all required fields.';
            $messageType = 'error';
        } else {
            $updateData = [
                'name' => $name,
                'email' => $email,
                'contact_person' => $contactPerson,
                'phone' => $phone,
                'address' => $address,
                'status' => $status
            ];
            
            // If password is provided, hash and include it
            if (!empty($_POST['password'])) {
                $updateData['password'] = hashPassword($_POST['password']);
            }
            
            $success = $db->update('schools', $updateData, 'id = ?', [$schoolId]);
            
            if ($success) {
                logActivity($db, $_SESSION['user_id'], 'admin', 'update_school', "Updated school ID: $schoolId");
                $message = 'School updated successfully!';
                $messageType = 'success';
            } else {
                $message = 'Failed to update school.';
                $messageType = 'error';
            }
        }
    } elseif ($action === 'delete_school') {
        $schoolId = (int)$_POST['school_id'];
        
        // Check if school has reports
        $reportCount = $db->fetchOne(
            "SELECT COUNT(*) as count FROM reports WHERE school_id = ?",
            [$schoolId]
        )['count'];
        
        if ($reportCount > 0) {
            $message = 'Cannot delete school with existing reports. Set status to inactive instead.';
            $messageType = 'error';
        } else {
            $success = $db->delete('schools', 'id = ?', [$schoolId]);
            
            if ($success) {
                logActivity($db, $_SESSION['user_id'], 'admin', 'delete_school', "Deleted school ID: $schoolId");
                $message = 'School deleted successfully!';
                $messageType = 'success';
            } else {
                $message = 'Failed to delete school.';
                $messageType = 'error';
            }
        }
    }
}

// Get schools with statistics
$schools = $db->fetchAll(
    "SELECT s.*, 
            COUNT(r.id) as report_count,
            COUNT(u.id) as student_count,
            SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) as approved_count
     FROM schools s 
     LEFT JOIN reports r ON s.id = r.school_id 
     LEFT JOIN users u ON s.id = u.school_id AND u.role = 'student'
     GROUP BY s.id 
     ORDER BY s.name",
    []
);

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3 mb-3">
            <i class="fas fa-school text-primary me-2"></i>
            Manage Schools
        </h1>
        <p class="text-muted mb-0">Add, edit, and manage schools in the system</p>
    </div>
    <div class="col-auto">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSchoolModal">
            <i class="fas fa-plus me-1"></i>Add School
        </button>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType === 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Schools List -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Schools (<?php echo count($schools); ?>)</h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($schools)): ?>
            <div class="text-center py-5">
                <i class="fas fa-school fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">No schools registered</h6>
                <p class="text-muted mb-3">Add the first school to get started</p>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSchoolModal">
                    <i class="fas fa-plus me-1"></i>Add School
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>School</th>
                            <th>Contact</th>
                            <th>Statistics</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schools as $school): ?>
                            <tr>
                                <td>
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($school['name']); ?></h6>
                                        <small class="text-muted">
                                            Code: <?php echo htmlspecialchars($school['code']); ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <small>
                                            <i class="fas fa-envelope me-1"></i>
                                            <?php echo htmlspecialchars($school['email']); ?>
                                        </small>
                                        <?php if ($school['contact_person']): ?>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-user me-1"></i>
                                                <?php echo htmlspecialchars($school['contact_person']); ?>
                                            </small>
                                        <?php endif; ?>
                                        <?php if ($school['phone']): ?>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-phone me-1"></i>
                                                <?php echo htmlspecialchars($school['phone']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <span class="badge bg-primary"><?php echo $school['report_count']; ?> Reports</span>
                                        <span class="badge bg-secondary"><?php echo $school['student_count']; ?> Students</span>
                                    </div>
                                    <?php if ($school['approved_count'] > 0): ?>
                                        <small class="text-success">
                                            <?php echo $school['approved_count']; ?> approved
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $school['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($school['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" onclick="editSchool(<?php echo htmlspecialchars(json_encode($school)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" onclick="deleteSchool(<?php echo $school['id']; ?>, '<?php echo htmlspecialchars($school['name']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add School Modal -->
<div class="modal fade" id="addSchoolModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New School</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_school">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name" class="form-label">School Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="code" class="form-label">School Code *</label>
                                <input type="text" class="form-control" id="code" name="code" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password" class="form-label">Password *</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="contact_person" class="form-label">Contact Person</label>
                                <input type="text" class="form-control" id="contact_person" name="contact_person">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add School</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit School Modal -->
<div class="modal fade" id="editSchoolModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit School</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editSchoolForm">
                <input type="hidden" name="action" value="update_school">
                <input type="hidden" name="school_id" id="edit_school_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_name" class="form-label">School Name *</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_code" class="form-label">School Code</label>
                                <input type="text" class="form-control" id="edit_code" name="code" readonly>
                                <small class="text-muted">Code cannot be changed</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="edit_email" name="email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="edit_password" name="password">
                                <small class="text-muted">Leave blank to keep current password</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_contact_person" class="form-label">Contact Person</label>
                                <input type="text" class="form-control" id="edit_contact_person" name="contact_person">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="edit_phone" name="phone">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_status" class="form-label">Status</label>
                                <select class="form-select" id="edit_status" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_address" class="form-label">Address</label>
                        <textarea class="form-control" id="edit_address" name="address" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update School</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editSchool(school) {
    document.getElementById('edit_school_id').value = school.id;
    document.getElementById('edit_name').value = school.name;
    document.getElementById('edit_code').value = school.code;
    document.getElementById('edit_email').value = school.email;
    document.getElementById('edit_contact_person').value = school.contact_person || '';
    document.getElementById('edit_phone').value = school.phone || '';
    document.getElementById('edit_address').value = school.address || '';
    document.getElementById('edit_status').value = school.status;
    
    const modal = new bootstrap.Modal(document.getElementById('editSchoolModal'));
    modal.show();
}

function deleteSchool(schoolId, schoolName) {
    if (confirm(`Are you sure you want to delete "${schoolName}"? This action cannot be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_school">
            <input type="hidden" name="school_id" value="${schoolId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
