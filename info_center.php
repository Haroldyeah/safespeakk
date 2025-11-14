<?php
$pageTitle = 'Information Center';
require_once 'config/config.php';
requireLogin();
require_once 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3 mb-3"><i class="fas fa-book-reader text-primary me-2"></i>Information Center</h1>
        <p class="text-muted">Resources on anti-bullying policy, reporting procedures, MSWD contacts and support hotlines.</p>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0">Anti-Bullying Policies</h6></div>
    <div class="card-body">
        <h5>RA 10627 - Anti-Bullying Act of 2013 (Philippines)</h5>
        <p>The Anti-Bullying Act of 2013 requires all elementary and secondary schools to adopt policies to address bullying, provide prevention programs, and set procedures for reporting and investigation.</p>
        <p><a href="https://www.officialgazette.gov.ph/2013/09/18/republic-act-no-10627/" target="_blank">Read RA 10627 (Official Gazette)</a></p>

        <h5>Child Protection Policy</h5>
        <p>Schools should adopt a child protection policy that ensures the safety, privacy, and rights of children during reporting, investigation and referral processes.</p>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0">Reporting Procedures</h6></div>
    <div class="card-body">
        <ol>
            <li>Report the incident through the school's reporting channels or via this system.</li>
            <li>Ensure the safety of the victim and separate involved students when necessary.</li>
            <li>Preserve evidence (screenshots, photos, files) and record witness details.</li>
            <li>Follow up with counseling and monitor the situation.</li>
            <li>If there is serious harm, threats, or criminal activity, refer to authorities and MSWD immediately.</li>
        </ol>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0">MSWD & Support Contacts</h6></div>
    <div class="card-body">
        <p>Please keep local MSWD and support hotlines accessible. Below are example entries — replace with local contacts in your school settings.</p>
        <ul>
            <li><strong>MSWD Regional Office:</strong> (02) 1234-5678</li>
            <li><strong>Child Protection Hotline:</strong> 171 (example)</li>
            <li><strong>National Emergency Hotline:</strong> 911</li>
            <li><strong>Email (MSWD):</strong> mswd@example.gov.ph</li>
        </ul>
        <p>To customize contact details for each school, edit the school record in the admin panel.</p>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0">Prevention & Awareness</h6></div>
    <div class="card-body">
        <p>Provide educational materials and run awareness programs for students, parents and staff. Key elements include:</p>
        <ul>
            <li>Clear definitions and examples of bullying</li>
            <li>Reporting channels and a promise of safety for reporters</li>
            <li>Regular counseling and restorative practices</li>
            <li>Contact lists for local MSWD and support providers</li>
        </ul>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
