<?php
/**
 * Load a PHP template from templates/email and return rendered HTML.
 * Usage: load_email_template('report_submitted.php', $varsArray)
 */
function load_email_template($templateName, $vars = []) {
    $templatePath = __DIR__ . '/' . $templateName;
    if (!file_exists($templatePath)) {
        throw new Exception('Email template not found: ' . $templateName);
    }
    extract($vars, EXTR_SKIP);
    ob_start();
    include $templatePath;
    return ob_get_clean();
}
