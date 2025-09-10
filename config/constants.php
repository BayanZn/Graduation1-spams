<?php
define('APP_NAME', 'Project Allocation System');
define('APP_VERSION', '1.0.0');
define('APP_DEBUG', true);

// File upload settings
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', [
    'application/pdf',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
]);

// Pagination settings
define('ITEMS_PER_PAGE', 10);

// Email settings
define('EMAIL_FROM', 'no-reply@projectallocation.com');
define('EMAIL_FROM_NAME', 'Project Allocation System');

// Path settings
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('REPORT_PATH', __DIR__ . '/../reports/');

// Ensure upload directories exist
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0777, true);
}

if (!file_exists(REPORT_PATH)) {
    mkdir(REPORT_PATH, 0777, true);



}