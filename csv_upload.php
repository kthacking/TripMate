<?php
require_once 'db.php';
require_once 'auth.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
checkTripMaker();

header('Content-Type: application/json');

$created_by = $_SESSION['user_id'];

// ── Validate Upload ──────────────────────────────────────
if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit.',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds form upload limit.',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file was selected.',
        UPLOAD_ERR_NO_TMP_DIR => 'Server missing temp folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION  => 'Upload blocked by a PHP extension.',
    ];
    $code = isset($_FILES['csv_file']) ? $_FILES['csv_file']['error'] : UPLOAD_ERR_NO_FILE;
    $msg  = isset($errorMessages[$code]) ? $errorMessages[$code] : 'Unknown upload error.';
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

$file = $_FILES['csv_file'];

// Check MIME / extension
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if ($ext !== 'csv') {
    echo json_encode(['success' => false, 'message' => 'Only .csv files are allowed.']);
    exit;
}

// ── Parse CSV ────────────────────────────────────────────
$handle = fopen($file['tmp_name'], 'r');
if (!$handle) {
    echo json_encode(['success' => false, 'message' => 'Unable to read the uploaded file.']);
    exit;
}

// Required headers (must be present)
$requiredHeaders = ['title', 'destination', 'start_date', 'end_date', 'cost'];

// All recognised headers
$allHeaders = [
    'title', 'destination', 'image_url', 'start_date', 'end_date', 'cost',
    'description', 'max_participants', 'registration_deadline', 'trip_type',
    'comfort_level', 'included_items', 'not_included_items', 'timeline', 'checklist'
];

// Read header row
$headerRow = fgetcsv($handle);
if (!$headerRow) {
    fclose($handle);
    echo json_encode(['success' => false, 'message' => 'CSV file is empty or has no header row.']);
    exit;
}

// Normalise headers
$headerRow = array_map(function($h) {
    return strtolower(trim(str_replace([' ', '-'], '_', $h)));
}, $headerRow);

// Check required
$missing = array_diff($requiredHeaders, $headerRow);
if (!empty($missing)) {
    fclose($handle);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required columns: ' . implode(', ', $missing) . '. Required: title, destination, start_date, end_date, cost.'
    ]);
    exit;
}

// Map header indices
$colIndex = [];
foreach ($headerRow as $i => $h) {
    if (in_array($h, $allHeaders)) {
        $colIndex[$h] = $i;
    }
}

// ── Read & Validate Rows ─────────────────────────────────
$rows    = [];
$errors  = [];
$lineNum = 1; // header was line 1

while (($data = fgetcsv($handle)) !== false) {
    $lineNum++;

    // Skip completely empty rows
    if (count($data) === 1 && trim($data[0]) === '') continue;

    $row = [];
    foreach ($allHeaders as $h) {
        $row[$h] = isset($colIndex[$h]) && isset($data[$colIndex[$h]]) ? trim($data[$colIndex[$h]]) : '';
    }

    // ── Per-row validation ──
    $rowErrors = [];

    if (empty($row['title']))       $rowErrors[] = 'title is empty';
    if (empty($row['destination'])) $rowErrors[] = 'destination is empty';

    // Dates
    if (empty($row['start_date']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $row['start_date'])) {
        $rowErrors[] = 'start_date must be YYYY-MM-DD';
    }
    if (empty($row['end_date']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $row['end_date'])) {
        $rowErrors[] = 'end_date must be YYYY-MM-DD';
    }
    if (empty($rowErrors) && strtotime($row['end_date']) < strtotime($row['start_date'])) {
        $rowErrors[] = 'end_date is before start_date';
    }

    // Cost
    if ($row['cost'] === '' || !is_numeric($row['cost']) || floatval($row['cost']) < 0) {
        $rowErrors[] = 'cost must be a positive number';
    }

    // Optional numeric fields
    if ($row['max_participants'] !== '' && (!is_numeric($row['max_participants']) || intval($row['max_participants']) < 0)) {
        $rowErrors[] = 'max_participants must be a positive integer';
    }
    if ($row['comfort_level'] !== '' && (!is_numeric($row['comfort_level']) || intval($row['comfort_level']) < 1 || intval($row['comfort_level']) > 5)) {
        $rowErrors[] = 'comfort_level must be 1-5';
    }

    // Registration deadline
    if (!empty($row['registration_deadline']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $row['registration_deadline'])) {
        $rowErrors[] = 'registration_deadline must be YYYY-MM-DD';
    }

    // Trip type validation
    $validTypes = ['Leisure', 'Adventure', 'Educational', 'Religious', 'Budget', 'Luxury'];
    if (!empty($row['trip_type']) && !in_array($row['trip_type'], $validTypes)) {
        $rowErrors[] = 'trip_type must be one of: ' . implode(', ', $validTypes);
    }

    if (!empty($rowErrors)) {
        $errors[] = "Row $lineNum: " . implode('; ', $rowErrors);
    }

    $row['_line'] = $lineNum;
    $rows[] = $row;
}
fclose($handle);

if (empty($rows)) {
    echo json_encode(['success' => false, 'message' => 'CSV has no data rows (only the header).']);
    exit;
}

// ── If there are validation errors, return them ──────────
if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => 'Validation failed for ' . count($errors) . ' row(s).',
        'errors'  => $errors,
        'preview' => array_slice($rows, 0, 5) // still show first 5 for debugging
    ]);
    exit;
}

// ── ACTION: preview (default) or confirm ─────────────────
$action = isset($_POST['action']) ? $_POST['action'] : 'preview';

if ($action === 'preview') {
    // Return parsed rows for preview, do NOT insert yet
    echo json_encode([
        'success' => true,
        'message' => count($rows) . ' trip(s) ready to import.',
        'preview' => $rows,
        'count'   => count($rows)
    ]);
    exit;
}

// ── INSERT into database ─────────────────────────────────
$inserted = 0;
$insertErrors = [];

$stmt = $conn->prepare("INSERT INTO trips (
    title, destination, image_url, start_date, end_date, cost, description, created_by,
    max_participants, registration_deadline, trip_type, comfort_level,
    included_items, not_included_items, timeline, checklist
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

foreach ($rows as $row) {
    $title              = $row['title'];
    $destination        = $row['destination'];
    $image_url          = $row['image_url'] ?: '';
    $start_date         = $row['start_date'];
    $end_date           = $row['end_date'];
    $cost               = floatval($row['cost']);
    $description        = $row['description'] ?: '';
    $max_participants   = $row['max_participants'] !== '' ? intval($row['max_participants']) : 0;
    $reg_deadline       = $row['registration_deadline'] ?: null;
    $trip_type          = $row['trip_type'] ?: 'Leisure';
    $comfort_level      = $row['comfort_level'] !== '' ? intval($row['comfort_level']) : 3;
    $included_items     = $row['included_items'] ?: '';
    $not_included_items = $row['not_included_items'] ?: '';
    $timeline           = $row['timeline'] ?: '';
    $checklist          = $row['checklist'] ?: '';

    $stmt->bind_param("sssssdsississsss",
        $title, $destination, $image_url, $start_date, $end_date, $cost, $description, $created_by,
        $max_participants, $reg_deadline, $trip_type, $comfort_level,
        $included_items, $not_included_items, $timeline, $checklist
    );

    if ($stmt->execute()) {
        $inserted++;
    } else {
        $insertErrors[] = "Row {$row['_line']}: " . $stmt->error;
    }
}

$stmt->close();

if (!empty($insertErrors)) {
    echo json_encode([
        'success' => false,
        'message' => "$inserted of " . count($rows) . " trip(s) imported. Some rows had errors.",
        'errors'  => $insertErrors,
        'inserted' => $inserted
    ]);
} else {
    echo json_encode([
        'success'  => true,
        'message'  => "All $inserted trip(s) imported successfully!",
        'inserted' => $inserted
    ]);
}
?>
