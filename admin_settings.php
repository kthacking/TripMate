<?php
require_once 'admin_header.php';

// Handle Updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_settings'])) {
    foreach ($_POST['s'] as $key => $value) {
        $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->bind_param("ss", $value, $key);
        $stmt->execute();
    }
    
    // Log special events
    logActivity($conn, $_SESSION['user_id'], "Updated system settings", "Modified global configurations");
    
    header("Location: admin_settings.php?msg=updated");
    exit();
}

$s = $settings;
?>

<div style="max-width: 800px;">
    <h3 style="margin-bottom: 30px; color: #111827;">Global Platform Configurations</h3>

    <form method="POST">
        <input type="hidden" name="save_settings" value="1">

        <div style="display: flex; flex-direction: column; gap: 20px;">
            
            <!-- General Settings -->
            <div style="background: white; border-radius: 16px; padding: 25px; box-shadow: var(--shadow-sm); border: 1px solid #E5E7EB;">
                <h4 style="font-size: 1rem; color: #4338CA; border-bottom: 1px solid #F3F4F6; padding-bottom: 15px; margin-bottom: 20px;">General System</h4>
                
                <div class="form-group" style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-weight: 700; color: #111827;">Platform Title</div>
                        <div style="font-size: 0.8rem; color: #6B7280;">Displayed in browser and header</div>
                    </div>
                    <input type="text" name="s[site_title]" value="<?php echo htmlspecialchars($s['site_title']); ?>" class="form-control" style="width: 250px; border-radius: 10px;">
                </div>

                <div class="form-group" style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-weight: 700; color: #111827;">Maintenance Mode</div>
                        <div style="font-size: 0.8rem; color: #6B7280;">Disable public access (Coming soon)</div>
                    </div>
                    <label class="switch">
                        <input type="hidden" name="s[maintenance_mode]" value="0">
                        <input type="checkbox" name="s[maintenance_mode]" value="1" <?php if($s['maintenance_mode'] == '1') echo 'checked'; ?>>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>

            <!-- Features -->
            <div style="background: white; border-radius: 16px; padding: 25px; box-shadow: var(--shadow-sm); border: 1px solid #E5E7EB;">
                <h4 style="font-size: 1rem; color: #4338CA; border-bottom: 1px solid #F3F4F6; padding-bottom: 15px; margin-bottom: 20px;">Feature Access Control</h4>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    
                    <div class="form-group" style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 700; color: #111827;">New Registration</div>
                            <div style="font-size: 0.8rem; color: #6B7280;">Allow new users to join</div>
                        </div>
                        <label class="switch">
                            <input type="hidden" name="s[registration_enabled]" value="0">
                            <input type="checkbox" name="s[registration_enabled]" value="1" <?php if($s['registration_enabled'] == '1') echo 'checked'; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="form-group" style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 700; color: #111827;">Media Uploads</div>
                            <div style="font-size: 0.8rem; color: #6B7280;">Enable gallery uploads</div>
                        </div>
                        <label class="switch">
                            <input type="hidden" name="s[media_uploads_enabled]" value="0">
                            <input type="checkbox" name="s[media_uploads_enabled]" value="1" <?php if($s['media_uploads_enabled'] == '1') echo 'checked'; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="form-group" style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 700; color: #111827;">ZIP Downloads</div>
                            <div style="font-size: 0.8rem; color: #6B7280;">Allow bulk media download</div>
                        </div>
                        <label class="switch">
                            <input type="hidden" name="s[zip_downloads_enabled]" value="0">
                            <input type="checkbox" name="s[zip_downloads_enabled]" value="1" <?php if($s['zip_downloads_enabled'] == '1') echo 'checked'; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="form-group" style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 700; color: #111827;">Auto-Approval</div>
                            <div style="font-size: 0.8rem; color: #6B7280;">Skip TripMaker review</div>
                        </div>
                        <label class="switch">
                            <input type="hidden" name="s[auto_approval]" value="0">
                            <input type="checkbox" name="s[auto_approval]" value="1" <?php if($s['auto_approval'] == '1') echo 'checked'; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                </div>
            </div>

            <!-- Limits -->
            <div style="background: white; border-radius: 16px; padding: 25px; box-shadow: var(--shadow-sm); border: 1px solid #E5E7EB;">
                <h4 style="font-size: 1rem; color: #4338CA; border-bottom: 1px solid #F3F4F6; padding-bottom: 15px; margin-bottom: 20px;">Technical Limits</h4>
                
                <div class="form-group" style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-weight: 700; color: #111827;">Max Upload Size (MB)</div>
                        <div style="font-size: 0.8rem; color: #6B7280;">Per file limit for media</div>
                    </div>
                    <input type="number" name="s[max_upload_size_mb]" value="<?php echo htmlspecialchars($s['max_upload_size_mb']); ?>" class="form-control" style="width: 120px; border-radius: 10px;">
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="margin-top: 10px; padding: 15px; font-weight: 700; font-size: 1rem; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);">Apply Global Settings</button>

        </div>
    </form>
</div>

<?php require_once 'admin_footer.php'; ?>
