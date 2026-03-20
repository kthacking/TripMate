<?php
require_once 'db.php';
require_once 'auth.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
checkTripMaker(); 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and Get Inputs
    $title = $conn->real_escape_string($_POST['title']);
    $destination = $conn->real_escape_string($_POST['destination']);
    $image_url = $conn->real_escape_string($_POST['image_url']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $cost = floatval($_POST['cost']);
    $description = $conn->real_escape_string($_POST['description']);
    // New Fields
    $max_participants = intval($_POST['max_participants']);
    $registration_deadline = $_POST['registration_deadline'];
    $trip_type = $conn->real_escape_string($_POST['trip_type']);
    $comfort_level = intval($_POST['comfort_level']);
    $included_items = $conn->real_escape_string($_POST['included_items']);
    $not_included_items = $conn->real_escape_string($_POST['not_included_items']);
    $timeline = $conn->real_escape_string($_POST['timeline']);
    $checklist = $conn->real_escape_string($_POST['checklist']);
    
    $created_by = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO trips (
        title, destination, image_url, start_date, end_date, cost, description, created_by,
        max_participants, registration_deadline, trip_type, comfort_level, included_items, not_included_items,
        timeline, checklist
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("sssssdsiississss", 
        $title, $destination, $image_url, $start_date, $end_date, $cost, $description, $created_by,
        $max_participants, $registration_deadline, $trip_type, $comfort_level, $included_items, $not_included_items,
        $timeline, $checklist
    );
    
    if ($stmt->execute()) {
        header("Location: dashboard.php?msg=trip_created");
        exit();
    } else {
        $error = "Error creating trip: " . $conn->error;
    }
}
?>
<?php include 'header.php'; ?>

<style>
/* ── Theme Definitions ── */
:root {
    --primary-color: #ea580c;
    --primary-hover: #c2410c;
    --secondary-color: #1e293b;
    --text-color: #334155;
    --text-light: #64748b;
    --bg-light: #f8fafc;
    --white: #ffffff;
    --shadow-sm: 0 4px 6px rgba(0,0,0,0.05);
    --shadow-md: 0 10px 25px rgba(0,0,0,0.08);
    --radius-sm: 12px;
    --radius-md: 20px;
    --radius-lg: 30px;
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.section {
    background: linear-gradient(135deg, #fffaf5 0%, #ffffff 100%);
    min-height: calc(100vh - 80px);
}

.auth-card {
    background: var(--white);
    border-radius: 24px;
    box-shadow: var(--shadow-md);
    padding: 40px;
    border: 1px solid rgba(234, 88, 12, 0.1);
}

.form-control, .form-select {
    border-radius: 14px;
    border: 2px solid #edf2f7;
    padding: 14px;
    transition: var(--transition);
    background: #f8fafc;
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary-color);
    background: white;
    box-shadow: 0 0 0 4px rgba(234, 88, 12, 0.1);
}

.form-label {
    font-weight: 700;
    color: var(--secondary-color);
    font-size: 0.95rem;
}

.btn-primary {
    background: var(--primary-color);
    color: var(--white);
    padding: 14px 28px;
    border-radius: 50px;
    font-weight: 800;
    font-size: 1rem;
    border: none;
    cursor: pointer;
    transition: var(--transition);
    box-shadow: 0 4px 15px rgba(234, 88, 12, 0.3);
}

.btn-primary:hover {
    background: var(--primary-hover);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(234, 88, 12, 0.4);
}

.btn-outline {
    background: white;
    border: 2px solid #e2e8f0;
    color: var(--secondary-color);
    padding: 14px 28px;
    border-radius: 50px;
    font-weight: 800;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition);
}

.btn-outline:hover {
    border-color: var(--primary-color);
    color: var(--primary-color);
    background: #fffaf5;
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

.csv-upload-trigger {
    border-color: #cbd5e1;
    border-radius: 20px;
}

.csv-upload-trigger:hover {
    border-color: var(--primary-color);
    background: #fffaf5;
    box-shadow: 0 4px 15px rgba(234, 88, 12, 0.15);
}

.csv-modal { border-radius: 24px; overflow: hidden; }
</style>

<div class="container section">
    <div style="max-width: 900px; margin: 0 auto;">
        <h1 style="margin-bottom: 24px; color: var(--secondary-color); font-weight: 800; font-size: 2.2rem; letter-spacing: -0.5px;">Create New Trip</h1>
        
        <?php if(isset($error)) echo "<div class='alert alert-danger' style='color:#dc2626; margin-bottom:20px; padding: 15px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; font-weight: 600;'>$error</div>"; ?>

        <div class="auth-card" style="max-width: 100%; text-align: left;">
            <form method="POST" action="">
                
                <!-- Basic Info -->
                <h4 style="color: var(--secondary-color); margin-bottom: 16px; border-bottom: 1px solid #edf2f7; padding-bottom: 8px;">Basic Information</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Trip Title</label>
                        <input type="text" name="title" class="form-control" required placeholder="e.g. Bali Summer Retreat">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Destination</label>
                        <input type="text" name="destination" class="form-control" required placeholder="e.g. Bali, Indonesia">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Cover Image URL</label>
                    <input type="url" name="image_url" class="form-control" placeholder="https://..." required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cost ($)</label>
                        <input type="number" name="cost" class="form-control" required min="0" step="0.01">
                    </div>
                </div>

                <!-- Logistics & Type (New Section) -->
                <h4 style="color: var(--secondary-color); margin: 24px 0 16px; border-bottom: 1px solid #edf2f7; padding-bottom: 8px;">Logistics & Details</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Max Participants</label>
                        <input type="number" name="max_participants" class="form-control" placeholder="e.g. 25" min="1">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Registration Deadline</label>
                        <input type="date" name="registration_deadline" class="form-control">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start;">
                    <div class="form-group">
                        <label class="form-label">Trip Type</label>
                        <select name="trip_type" class="form-select">
                            <option value="Leisure">Leisure</option>
                            <option value="Adventure">Adventure</option>
                            <option value="Educational">Educational</option>
                            <option value="Religious">Religious</option>
                            <option value="Budget">Budget</option>
                            <option value="Luxury">Luxury</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Comfort Level</label>
                        <div class="star-rating">
                            <input type="radio" id="star5" name="comfort_level" value="5" /><label for="star5" title="Luxury">★</label>
                            <input type="radio" id="star4" name="comfort_level" value="4" /><label for="star4" title="Very Comfortable">★</label>
                            <input type="radio" id="star3" name="comfort_level" value="3" checked /><label for="star3" title="Comfortable">★</label>
                            <input type="radio" id="star2" name="comfort_level" value="2" /><label for="star2" title="Basic">★</label>
                            <input type="radio" id="star1" name="comfort_level" value="1" /><label for="star1" title="Rough">★</label>
                        </div>
                    </div>
                </div>

                <!-- Description & Inclusions -->
                <h4 style="color: var(--secondary-color); margin: 24px 0 16px; border-bottom: 1px solid #edf2f7; padding-bottom: 8px;">Itinerary & Inclusions</h4>
                
                <div class="form-group">
                    <label class="form-label">Description & Itinerary</label>
                    <textarea name="description" class="form-control" rows="5" required placeholder="Describe the highlights..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Detailed Timeline (One event per line)</label>
                    <textarea name="timeline" class="form-control" rows="5" placeholder="Day 1: Arrival...&#10;Day 2: Exploration..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Essential Checklist (One item per line)</label>
                    <textarea name="checklist" class="form-control" rows="5" placeholder="Passport&#10;Hiking Boots&#10;Sunscreen"></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Included Items <i class="ri-check-line" style="color: #48bb78;"></i></label>
                        <textarea name="included_items" class="form-control" rows="3" placeholder="Stay, Food, Transport..."></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Not Included <i class="ri-close-line" style="color: #F50057;"></i></label>
                        <textarea name="not_included_items" class="form-control" rows="3" placeholder="Personal expenses, Flights..."></textarea>
                    </div>
                </div>

                <div style="margin-top: 30px; display: flex; gap: 16px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Publish Trip</button>
                    <a href="dashboard.php" class="btn btn-outline" style="border:none;">Cancel</a>
                </div>
            </form>

            <!-- ━━━ CSV Upload Divider ━━━ -->
            <div style="margin-top: 40px; border-top: 2px dashed #e2e8f0; padding-top: 28px; text-align: center;">
                <p style="color: var(--text-light); font-size: 0.9rem; margin-bottom: 14px;">
                    <i class="ri-information-line"></i> Have multiple trips? Bulk import from a CSV file.
                </p>
                <button type="button" id="csvUploadBtn" class="csv-upload-trigger" onclick="openCsvModal()">
                    <i class="ri-file-upload-line"></i>
                    Upload CSV File
                    <span class="csv-badge-optional">Optional</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     CSV UPLOAD MODAL
     ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
<div class="csv-modal-overlay" id="csvModalOverlay" onclick="closeCsvModal(event)">
    <div class="csv-modal" onclick="event.stopPropagation()">

        <!-- Header -->
        <div class="csv-modal-header">
            <div>
                <h3 style="margin: 0; font-size: 1.25rem; color: var(--secondary-color);">
                    <i class="ri-file-list-3-line" style="color: var(--primary-color);"></i> CSV Bulk Import
                </h3>
                <p style="margin: 4px 0 0; font-size: 0.82rem; color: var(--text-light);">
                    All trips will be created under your account
                </p>
            </div>
            <button class="csv-modal-close" onclick="closeCsvModal()">&times;</button>
        </div>

        <!-- Step 1 ─ Upload -->
        <div class="csv-step" id="csvStep1">
            <div class="csv-dropzone" id="csvDropzone">
                <div class="csv-dropzone-icon">
                    <i class="ri-upload-cloud-2-line"></i>
                </div>
                <p class="csv-dropzone-title">Drag & drop your CSV file here</p>
                <p class="csv-dropzone-sub">or click to browse</p>
                <input type="file" id="csvFileInput" accept=".csv" style="display:none;" />
            </div>

            <!-- Format Guide -->
            <div class="csv-format-guide">
                <h4><i class="ri-lightbulb-line" style="color: #F59E0B;"></i> CSV Format Guide</h4>
                <p>Your CSV must include these <strong>required</strong> columns:</p>
                <div class="csv-columns-grid">
                    <span class="csv-col required">title</span>
                    <span class="csv-col required">destination</span>
                    <span class="csv-col required">start_date</span>
                    <span class="csv-col required">end_date</span>
                    <span class="csv-col required">cost</span>
                </div>
                <p style="margin-top: 10px; font-size: 0.82rem;">Optional columns:</p>
                <div class="csv-columns-grid">
                    <span class="csv-col">image_url</span>
                    <span class="csv-col">description</span>
                    <span class="csv-col">max_participants</span>
                    <span class="csv-col">registration_deadline</span>
                    <span class="csv-col">trip_type</span>
                    <span class="csv-col">comfort_level</span>
                    <span class="csv-col">included_items</span>
                    <span class="csv-col">not_included_items</span>
                    <span class="csv-col">timeline</span>
                    <span class="csv-col">checklist</span>
                </div>
                <p style="margin-top:10px; font-size:0.8rem; color:var(--text-light);">
                    Dates must be <code style="background:#f1f1f1;padding:2px 6px;border-radius:4px;">YYYY-MM-DD</code>.
                    Trip type: Leisure, Adventure, Educational, Religious, Budget, or Luxury.
                </p>
            </div>
        </div>

        <!-- Step 2 ─ Preview -->
        <div class="csv-step" id="csvStep2" style="display:none;">
            <div class="csv-preview-bar">
                <div>
                    <i class="ri-checkbox-circle-line" style="color:#48bb78;"></i>
                    <strong id="csvPreviewCount">0</strong> trip(s) parsed
                </div>
                <button class="csv-btn-sm" onclick="resetCsvModal()">
                    <i class="ri-restart-line"></i> Choose Another
                </button>
            </div>
            <div class="csv-preview-table-wrap">
                <table class="csv-preview-table" id="csvPreviewTable">
                    <thead><tr></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <!-- Step 3 ─ Result -->
        <div class="csv-step" id="csvStep3" style="display:none;">
            <div id="csvResultContent" class="csv-result-content"></div>
        </div>

        <!-- Errors display -->
        <div id="csvErrors" class="csv-errors" style="display:none;"></div>

        <!-- Footer -->
        <div class="csv-modal-footer" id="csvModalFooter">
            <button class="btn btn-outline" style="border:none;" onclick="closeCsvModal()">Cancel</button>
            <button class="btn btn-primary" id="csvConfirmBtn" style="display:none;" onclick="confirmCsvImport()">
                <i class="ri-check-double-line"></i> Confirm Import
            </button>
        </div>

        <!-- Loading Overlay -->
        <div class="csv-loading" id="csvLoading" style="display:none;">
            <div class="csv-spinner"></div>
            <p id="csvLoadingText">Processing CSV...</p>
        </div>
    </div>
</div>

<!-- ━━━ Inline Styles ━━━ -->
<style>
/* ── Trigger Button ── */
.csv-upload-trigger {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 28px;
    border: 2px dashed #cbd5e0;
    background: #f8fafc;
    border-radius: var(--radius-lg);
    color: var(--text-light);
    font-family: inherit;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}
.csv-upload-trigger:hover {
    border-color: var(--primary-color);
    color: var(--primary-color);
    background: #f0f0ff;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(108,99,255,0.15);
}
.csv-upload-trigger i { font-size: 1.2rem; }
.csv-badge-optional {
    background: #edf2f7;
    color: #a0aec0;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* ── Modal Overlay ── */
.csv-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(0,0,0,0.45);
    backdrop-filter: blur(4px);
    justify-content: center;
    align-items: center;
    padding: 20px;
    animation: csvFadeIn 0.25s ease;
}
.csv-modal-overlay.show { display: flex; }

@keyframes csvFadeIn {
    from { opacity:0; }
    to   { opacity:1; }
}
@keyframes csvSlideUp {
    from { opacity:0; transform:translateY(30px) scale(0.97); }
    to   { opacity:1; transform:translateY(0) scale(1); }
}

/* ── Modal ── */
.csv-modal {
    position: relative;
    background: var(--white);
    border-radius: 20px;
    width: 100%;
    max-width: 720px;
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 25px 60px rgba(0,0,0,0.2);
    animation: csvSlideUp 0.35s ease;
    overflow: hidden;
}
.csv-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 22px 28px 18px;
    border-bottom: 1px solid #edf2f7;
}
.csv-modal-close {
    background: none;
    border: none;
    font-size: 1.8rem;
    color: #a0aec0;
    cursor: pointer;
    line-height: 1;
    transition: color 0.2s;
}
.csv-modal-close:hover { color: #e53e3e; }

.csv-modal-footer {
    padding: 16px 28px;
    border-top: 1px solid #edf2f7;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    background: #fafbfc;
}

/* ── Steps ── */
.csv-step {
    padding: 24px 28px;
    overflow-y: auto;
    flex: 1;
}

/* ── Dropzone ── */
.csv-dropzone {
    border: 2px dashed #d2d6dc;
    border-radius: 16px;
    padding: 50px 24px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: #fafbfc;
}
.csv-dropzone:hover,
.csv-dropzone.dragover {
    border-color: var(--primary-color);
    background: #f0f0ff;
}
.csv-dropzone-icon {
    font-size: 3rem;
    color: var(--primary-color);
    margin-bottom: 12px;
    opacity: 0.7;
}
.csv-dropzone-title {
    font-size: 1.05rem;
    font-weight: 600;
    color: var(--secondary-color);
    margin-bottom: 4px;
}
.csv-dropzone-sub {
    font-size: 0.85rem;
    color: var(--text-light);
}

/* ── Format Guide ── */
.csv-format-guide {
    margin-top: 22px;
    background: #fafbfc;
    border: 1px solid #edf2f7;
    border-radius: 12px;
    padding: 18px 20px;
}
.csv-format-guide h4 {
    font-size: 0.95rem;
    color: var(--secondary-color);
    margin-bottom: 8px;
}
.csv-format-guide p {
    font-size: 0.85rem;
    color: var(--text-light);
}
.csv-columns-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 8px;
}
.csv-col {
    background: #edf2f7;
    color: #4a5568;
    font-size: 0.78rem;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 6px;
    font-family: 'Courier New', monospace;
}
.csv-col.required {
    background: #e9d8fd;
    color: #6b46c1;
}

/* ── Preview ── */
.csv-preview-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding: 12px 16px;
    background: #f0fff4;
    border: 1px solid #c6f6d5;
    border-radius: 10px;
    font-size: 0.92rem;
    color: #276749;
}
.csv-btn-sm {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: white;
    border: 1px solid #e2e8f0;
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 0.82rem;
    font-family: inherit;
    cursor: pointer;
    font-weight: 500;
    color: var(--text-light);
    transition: all 0.2s;
}
.csv-btn-sm:hover {
    background: #edf2f7;
    color: var(--secondary-color);
}
.csv-preview-table-wrap {
    overflow-x: auto;
    overflow-y: auto;
    max-height: 340px;
    border: 1px solid #edf2f7;
    border-radius: 10px;
}
.csv-preview-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.82rem;
    white-space: nowrap;
}
.csv-preview-table thead {
    position: sticky;
    top: 0;
    z-index: 1;
}
.csv-preview-table th {
    background: #f7fafc;
    padding: 10px 14px;
    text-align: left;
    font-weight: 700;
    color: var(--secondary-color);
    border-bottom: 2px solid #e2e8f0;
    text-transform: uppercase;
    font-size: 0.72rem;
    letter-spacing: 0.5px;
}
.csv-preview-table td {
    padding: 9px 14px;
    border-bottom: 1px solid #f0f0f0;
    color: var(--text-color);
    max-width: 180px;
    overflow: hidden;
    text-overflow: ellipsis;
}
.csv-preview-table tbody tr:hover {
    background: #f7fafc;
}
.csv-preview-table tbody tr:last-child td { border-bottom: none; }

/* ── Errors ── */
.csv-errors {
    margin: 0 28px 6px;
    padding: 14px 16px;
    background: #fff5f5;
    border: 1px solid #feb2b2;
    border-radius: 10px;
    font-size: 0.85rem;
    color: #c53030;
    max-height: 150px;
    overflow-y: auto;
}
.csv-errors ul {
    margin: 6px 0 0 18px;
    list-style: disc;
}
.csv-errors li { margin-bottom: 3px; }

/* ── Result ── */
.csv-result-content {
    text-align: center;
    padding: 40px 20px;
}
.csv-result-icon {
    font-size: 4rem;
    margin-bottom: 16px;
    display: block;
}
.csv-result-icon.success { color: #48bb78; }
.csv-result-icon.error   { color: #e53e3e; }
.csv-result-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--secondary-color);
    margin-bottom: 8px;
}
.csv-result-msg {
    font-size: 0.95rem;
    color: var(--text-light);
}

/* ── Loading ── */
.csv-loading {
    position: absolute;
    inset: 0;
    background: rgba(255,255,255,0.88);
    backdrop-filter: blur(2px);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 10;
    border-radius: 20px;
}
.csv-spinner {
    width: 44px;
    height: 44px;
    border: 4px solid #e2e8f0;
    border-top-color: var(--primary-color);
    border-radius: 50%;
    animation: csvSpin 0.8s linear infinite;
    margin-bottom: 14px;
}
@keyframes csvSpin { to { transform: rotate(360deg); } }
.csv-loading p {
    font-size: 0.95rem;
    font-weight: 500;
    color: var(--text-light);
}

/* ── Responsive ── */
@media (max-width: 640px) {
    .csv-modal { max-width: 100%; border-radius: 16px; }
    .csv-step   { padding: 18px 16px; }
    .csv-modal-header, .csv-modal-footer { padding-left: 16px; padding-right: 16px; }
}
</style>

<!-- ━━━ JavaScript ━━━ -->
<script>
(function() {
    // state
    let csvFile = null;
    let previewData = null;

    // ── elements
    const overlay   = document.getElementById('csvModalOverlay');
    const dropzone  = document.getElementById('csvDropzone');
    const fileInput = document.getElementById('csvFileInput');
    const step1     = document.getElementById('csvStep1');
    const step2     = document.getElementById('csvStep2');
    const step3     = document.getElementById('csvStep3');
    const errBox    = document.getElementById('csvErrors');
    const confirmBtn= document.getElementById('csvConfirmBtn');
    const loading   = document.getElementById('csvLoading');
    const loadText  = document.getElementById('csvLoadingText');

    // ── Open / Close ─────────────────────────
    window.openCsvModal = function() {
        resetCsvModal();
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    };
    window.closeCsvModal = function(e) {
        if (e && e.target !== overlay) return;
        overlay.classList.remove('show');
        document.body.style.overflow = '';
    };

    // ── Reset ────────────────────────────────
    window.resetCsvModal = function() {
        csvFile = null;
        previewData = null;
        fileInput.value = '';
        step1.style.display = '';
        step2.style.display = 'none';
        step3.style.display = 'none';
        errBox.style.display = 'none';
        errBox.innerHTML = '';
        confirmBtn.style.display = 'none';
        loading.style.display = 'none';
    };

    // ── Dropzone Events ──────────────────────
    dropzone.addEventListener('click', () => fileInput.click());

    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.classList.add('dragover');
    });
    dropzone.addEventListener('dragleave', () => {
        dropzone.classList.remove('dragover');
    });
    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('dragover');
        if (e.dataTransfer.files.length) {
            handleFile(e.dataTransfer.files[0]);
        }
    });

    fileInput.addEventListener('change', () => {
        if (fileInput.files.length) handleFile(fileInput.files[0]);
    });

    // ── Handle File ──────────────────────────
    function handleFile(file) {
        errBox.style.display = 'none';
        errBox.innerHTML = '';

        // Client-side check
        const ext = file.name.split('.').pop().toLowerCase();
        if (ext !== 'csv') {
            showError('Please select a valid <strong>.csv</strong> file.');
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            showError('File is too large. Maximum size is <strong>5 MB</strong>.');
            return;
        }

        csvFile = file;
        uploadForPreview();
    }

    // ── Upload for Preview ───────────────────
    function uploadForPreview() {
        showLoading('Parsing CSV...');

        const fd = new FormData();
        fd.append('csv_file', csvFile);
        fd.append('action', 'preview');

        fetch('csv_upload.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    previewData = data.preview;
                    renderPreview(data);
                } else {
                    if (data.errors && data.errors.length) {
                        showErrors(data.message, data.errors);
                    } else {
                        showError(data.message);
                    }
                }
            })
            .catch(err => {
                hideLoading();
                showError('Network error. Please try again.');
                console.error(err);
            });
    }

    // ── Render Preview Table ─────────────────
    function renderPreview(data) {
        step1.style.display = 'none';
        step2.style.display = '';
        step3.style.display = 'none';

        document.getElementById('csvPreviewCount').textContent = data.count;

        const displayCols = ['title','destination','start_date','end_date','cost','trip_type','max_participants'];
        const table = document.getElementById('csvPreviewTable');
        const thead = table.querySelector('thead tr');
        const tbody = table.querySelector('tbody');

        thead.innerHTML = '<th>#</th>' + displayCols.map(c =>
            '<th>' + c.replace(/_/g, ' ') + '</th>'
        ).join('');

        tbody.innerHTML = data.preview.map((row, i) => {
            return '<tr><td>' + (i+1) + '</td>' + displayCols.map(c => {
                let val = row[c] || '—';
                if (c === 'cost' && val !== '—') val = '$' + parseFloat(val).toFixed(2);
                return '<td title="' + escapeHtml(val) + '">' + escapeHtml(val) + '</td>';
            }).join('') + '</tr>';
        }).join('');

        confirmBtn.style.display = '';
    }

    // ── Confirm Import ───────────────────────
    window.confirmCsvImport = function() {
        if (!csvFile) return;

        showLoading('Importing trips...');
        confirmBtn.style.display = 'none';

        const fd = new FormData();
        fd.append('csv_file', csvFile);
        fd.append('action', 'confirm');

        fetch('csv_upload.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                hideLoading();
                showResult(data);
            })
            .catch(err => {
                hideLoading();
                showError('Network error during import. Please try again.');
                console.error(err);
            });
    };

    // ── Show Result ──────────────────────────
    function showResult(data) {
        step1.style.display = 'none';
        step2.style.display = 'none';
        step3.style.display = '';
        confirmBtn.style.display = 'none';

        const rc = document.getElementById('csvResultContent');

        if (data.success) {
            rc.innerHTML = `
                <span class="csv-result-icon success"><i class="ri-checkbox-circle-fill"></i></span>
                <div class="csv-result-title">Import Successful!</div>
                <p class="csv-result-msg">${escapeHtml(data.message)}</p>
                <a href="dashboard.php" class="btn btn-primary" style="margin-top: 24px;">
                    <i class="ri-arrow-right-line"></i> Go to Dashboard
                </a>`;
        } else {
            let errHtml = '';
            if (data.errors && data.errors.length) {
                errHtml = '<ul style="text-align:left;max-height:120px;overflow-y:auto;margin:14px auto;max-width:500px;list-style:disc;padding-left:20px;font-size:0.85rem;color:#c53030;">'
                    + data.errors.map(e => '<li>' + escapeHtml(e) + '</li>').join('')
                    + '</ul>';
            }
            rc.innerHTML = `
                <span class="csv-result-icon error"><i class="ri-close-circle-fill"></i></span>
                <div class="csv-result-title">Import Had Issues</div>
                <p class="csv-result-msg">${escapeHtml(data.message)}</p>
                ${errHtml}
                <button class="btn btn-primary" style="margin-top:18px;" onclick="resetCsvModal()">
                    <i class="ri-restart-line"></i> Try Again
                </button>`;
        }
    }

    // ── Helpers ──────────────────────────────
    function showError(msg) {
        errBox.innerHTML = '<strong><i class="ri-error-warning-line"></i> Error:</strong> ' + msg;
        errBox.style.display = '';
    }
    function showErrors(title, errs) {
        errBox.innerHTML = '<strong><i class="ri-error-warning-line"></i> ' + escapeHtml(title) + '</strong>'
            + '<ul>' + errs.map(e => '<li>' + escapeHtml(e) + '</li>').join('') + '</ul>';
        errBox.style.display = '';
    }
    function showLoading(text) {
        loadText.textContent = text;
        loading.style.display = '';
    }
    function hideLoading() { loading.style.display = 'none'; }

    function escapeHtml(str) {
        const d = document.createElement('div');
        d.textContent = String(str);
        return d.innerHTML;
    }

    // ── Keyboard: Escape to close ────────────
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && overlay.classList.contains('show')) {
            overlay.classList.remove('show');
            document.body.style.overflow = '';
        }
    });
})();
</script>

<?php include 'footer.php'; ?>
