<?php
// Shim to load the actual auth_guard.php from the parent directory
// because api/.htaccess forces auto_prepend_file 'auth_guard.php' 
// which resolves to the current directory for scripts inside api/riwayat/
require_once dirname(__DIR__) . '/auth_guard.php';
