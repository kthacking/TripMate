<?php
require_once 'db.php';
require_once 'auth.php';
session_start();
checkLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['files'])) {
    
    $files = $_POST['files'];
    if (empty($files)) die("No files selected");

    // Sanitize and Validate
    // In a real app, verify that files belong to a trip the user has access to.
    // Here we rely on the implementation simplicity but basic check:
    $safe_files = [];
    foreach ($files as $f) {
        // Ensure path is within uploads/ and no directory traversal
        $f = realpath($f);
        if ($f && strpos($f, realpath('uploads')) === 0 && file_exists($f)) {
            $safe_files[] = $f;
        }
    }

    if (count($safe_files) == 0) die("Invalid files");

    // Check for Zip Extension
    if (!class_exists('ZipArchive')) {
        die("Error: The PHP ZipArchive extension is not enabled on this server. Please enable 'extension=zip' in your php.ini file.");
    }

    // Create Zip
    $zip = new ZipArchive();
    $zip_name = "TripMate_Gallery_" . time() . ".zip";
    $zip_path = sys_get_temp_dir() . "/" . $zip_name;

    if ($zip->open($zip_path, ZipArchive::CREATE) !== TRUE) {
        die("Could not create zip archive");
    }

    foreach ($safe_files as $file) {
        $zip->addFile($file, basename($file));
    }
    
    $zip->close();

    // Clean Output Buffer
    if (ob_get_length()) ob_clean();

    // Serve File
    header('Content-Type: application/zip');
    header('Content-disposition: attachment; filename='.$zip_name);
    header('Content-Length: ' . filesize($zip_path));
    readfile($zip_path);

    // Delete Temp
    unlink($zip_path);
    exit();
}
?>
