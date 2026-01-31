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

<div style="max-width: 900px; animation: slideIn 0.4s ease-out;">
    <div style="margin-bottom: 25px;">
        <h3 style="font-size: 1.25rem; font-weight: 850; color: var(--admin-text-main); letter-spacing: -0.6px; margin-bottom: 5px;">System Configuration Core</h3>
        <p style="color: var(--admin-text-muted); font-weight: 500; font-size: 0.9rem;">Manage global environment variables and platform-wide feature permissions.</p>
    </div>

    <form method="POST">
        <input type="hidden" name="save_settings" value="1">

        <div style="display: flex; flex-direction: column; gap: 20px;">
            
            <!-- General Settings -->
            <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: var(--shadow-sm); border: 1px solid #E5E7EB;">
                <h4 style="font-size: 0.95rem; color: #4338CA; border-bottom: 1px solid #F3F4F6; padding-bottom: 12px; margin-bottom: 15px;">Environment Profile</h4>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.85rem;">Platform Title</label>
                        <input type="text" name="s[site_title]" value="<?php echo htmlspecialchars($s['site_title']); ?>" class="form-control" style="height: 40px; border-radius: 10px; font-size: 0.9rem;">
                    </div>
                    <div class="form-group" style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 700; color: #111827; font-size: 0.9rem;">Maintenance Mode</div>
                            <div style="font-size: 0.75rem; color: #6B7280;">Lock public access</div>
                        </div>
                        <label class="switch">
                            <input type="hidden" name="s[maintenance_mode]" value="0">
                            <input type="checkbox" name="s[maintenance_mode]" value="1" <?php if($s['maintenance_mode'] == '1') echo 'checked'; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Features -->
            <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: var(--shadow-sm); border: 1px solid #E5E7EB;">
                <h4 style="font-size: 0.95rem; color: #4338CA; border-bottom: 1px solid #F3F4F6; padding-bottom: 12px; margin-bottom: 15px;">Feature Access Control</h4>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    
                    <div class="form-group" style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 700; color: #111827; font-size: 0.9rem;">New Registration</div>
                            <div style="font-size: 0.75rem; color: #6B7280;">Allow new users to join</div>
                        </div>
                        <label class="switch">
                            <input type="hidden" name="s[registration_enabled]" value="0">
                            <input type="checkbox" name="s[registration_enabled]" value="1" <?php if($s['registration_enabled'] == '1') echo 'checked'; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="form-group" style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 700; color: #111827; font-size: 0.9rem;">Media Uploads</div>
                            <div style="font-size: 0.75rem; color: #6B7280;">Enable gallery uploads</div>
                        </div>
                        <label class="switch">
                            <input type="hidden" name="s[media_uploads_enabled]" value="0">
                            <input type="checkbox" name="s[media_uploads_enabled]" value="1" <?php if($s['media_uploads_enabled'] == '1') echo 'checked'; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="form-group" style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 700; color: #111827; font-size: 0.9rem;">ZIP Downloads</div>
                            <div style="font-size: 0.75rem; color: #6B7280;">Allow bulk media download</div>
                        </div>
                        <label class="switch">
                            <input type="hidden" name="s[zip_downloads_enabled]" value="0">
                            <input type="checkbox" name="s[zip_downloads_enabled]" value="1" <?php if($s['zip_downloads_enabled'] == '1') echo 'checked'; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="form-group" style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 700; color: #111827; font-size: 0.9rem;">Auto-Approval</div>
                            <div style="font-size: 0.75rem; color: #6B7280;">Skip TripMaker review</div>
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
            <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: var(--shadow-sm); border: 1px solid #E5E7EB;">
                <h4 style="font-size: 0.95rem; color: #4338CA; border-bottom: 1px solid #F3F4F6; padding-bottom: 12px; margin-bottom: 15px;">Technical Limits</h4>
                
                <div class="form-group" style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-weight: 700; color: #111827; font-size: 0.9rem;">Max Upload Size (MB)</div>
                        <div style="font-size: 0.75rem; color: #6B7280;">Per file limit for media</div>
                    </div>
                    <input type="number" name="s[max_upload_size_mb]" value="<?php echo htmlspecialchars($s['max_upload_size_mb']); ?>" class="form-control" style="width: 100px; height: 40px; border-radius: 10px; font-size: 0.9rem;">
                </div>
            </div>

            <button type="submit" class="btn-premium" style="margin-top: 5px; width: 100%; justify-content: center; height: 45px; font-size: 0.95rem;">
                <i class="ri-save-3-line"></i> Apply Global Settings
            </button>

        </div>
    </form>

        </div>
    </form>
</div>

<?php require_once 'admin_footer.php'; ?>
