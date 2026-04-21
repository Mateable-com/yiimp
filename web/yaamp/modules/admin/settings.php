<?php

$this->pageTitle = 'Pool Settings - Admin';

echo '<div class="container-fluid py-4">';

// --- Admin Navigation Bar ---
echo getAdminSideBarLinks();

echo '<div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-dark text-white p-4 d-flex align-items-center border-0">
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-4">
                        <i class="fa fa-cog fa-2x text-primary"></i>
                    </div>
                    <div>
                        <h3 class="mb-0 fw-bold">Pool Configuration</h3>
                        <p class="text-muted small mb-0 mt-1">Manage global pool branding, fees, and social integration.</p>
                    </div>
                </div>
                <div class="card-body p-0">';

if(user()->hasFlash('message')) {
    echo '<div class="alert alert-success border-0 shadow-sm mx-4 mt-4 mb-0"><i class="fa fa-check-circle me-2"></i>'.user()->getFlash('message').'</div>';
}

echo CHtml::beginForm('', 'post', array('id'=>'settings-form'));

?>
<div class="p-4">
    <div class="row g-4">
        <!-- Section 1: Branding -->
        <div class="col-lg-6">
            <div class="card bg-light border-0 rounded-3 h-100">
                <div class="card-header bg-transparent border-0 fw-bold small text-uppercase py-3">
                    <i class="fa fa-id-card me-2 text-primary"></i>Pool Branding
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Pool Name</label>
                        <input type="text" name="Settings[site_name]" class="form-control border-2" value="<?php echo settings_get('site_name', YAAMP_SITE_NAME); ?>">
                        <div class="form-text small text-muted">The primary name shown across the site and in payout logs.</div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold">Stratum URL</label>
                        <input type="text" name="Settings[stratum_url]" class="form-control border-2" value="<?php echo settings_get('stratum_url', YAAMP_STRATUM_URL); ?>">
                        <div class="form-text small text-muted">Public URL used for stratum connections.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Economics -->
        <div class="col-lg-6">
            <div class="card bg-light border-0 rounded-3 h-100">
                <div class="card-header bg-transparent border-0 fw-bold small text-uppercase py-3">
                    <i class="fa fa-percentage me-2 text-success"></i>Fee Structure
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Mining Fee (%)</label>
                            <div class="input-group">
                                <input type="text" name="Settings[fees_mining]" class="form-control border-2" value="<?php echo settings_get('fees_mining', YAAMP_FEES_MINING); ?>">
                                <span class="input-group-text border-2">%</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Solo Fee (%)</label>
                            <div class="input-group">
                                <input type="text" name="Settings[fees_solo]" class="form-control border-2" value="<?php echo settings_get('fees_solo', YAAMP_FEES_SOLO); ?>">
                                <span class="input-group-text border-2">%</span>
                            </div>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold">Rental Fee (%)</label>
                        <div class="input-group">
                            <input type="text" name="Settings[fees_rental]" class="form-control border-2" value="<?php echo settings_get('fees_rental', YAAMP_FEES_RENTAL); ?>">
                            <span class="input-group-text border-2">%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: Pool Announcement -->
        <div class="col-12">
            <div class="card bg-light border-0 rounded-3">
                <div class="card-header bg-transparent border-0 fw-bold small text-uppercase py-3">
                    <i class="fa fa-bullhorn me-2 text-warning"></i>Pool Announcement Banner
                </div>
                <div class="card-body pt-0">
                    <div class="mb-2">
                        <label class="form-label small fw-bold">Announcement Message <span class="text-muted fw-normal">(leave blank to hide banner)</span></label>
                        <input type="text" name="Settings[pool_announcement]" class="form-control border-2" value="<?php echo htmlspecialchars(settings_get('pool_announcement', '')); ?>" placeholder="e.g. Scheduled maintenance on Sunday at 02:00 UTC">
                        <div class="form-text small text-muted">Shown as a highlighted banner at the top of the home page and pool page. HTML is not allowed.</div>
                    </div>
                    <div>
                        <label class="form-label small fw-bold">Banner Style</label>
                        <select name="Settings[pool_announcement_style]" class="form-select border-2 w-auto">
                            <?php
                            $styles = ['info' => 'Info (Blue)', 'success' => 'Success (Green)', 'warning' => 'Warning (Yellow)', 'danger' => 'Alert (Red)'];
                            $cur_style = settings_get('pool_announcement_style', 'info');
                            foreach ($styles as $val => $label) {
                                $sel = ($cur_style == $val) ? ' selected' : '';
                                echo "<option value=\"$val\"$sel>$label</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 4: Social & Support -->
        <div class="col-12">
            <div class="card bg-light border-0 rounded-3">
                <div class="card-header bg-transparent border-0 fw-bold small text-uppercase py-3">
                    <i class="fa fa-share-alt me-2 text-info"></i>Support & Social Links
                </div>
                <div class="card-body pt-0">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold"><i class="fab fa-discord me-1"></i>Discord URL</label>
                            <input type="text" name="Settings[link_discord]" class="form-control border-2" value="<?php echo settings_get('link_discord', ''); ?>" placeholder="https://discord.gg/...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold"><i class="fab fa-twitter me-1"></i>Twitter / X URL</label>
                            <input type="text" name="Settings[link_twitter]" class="form-control border-2" value="<?php echo settings_get('link_twitter', ''); ?>" placeholder="https://twitter.com/...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold"><i class="fab fa-github me-1"></i>GitHub URL</label>
                            <input type="text" name="Settings[link_github]" class="form-control border-2" value="<?php echo settings_get('link_github', ''); ?>" placeholder="https://github.com/...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold"><i class="fa fa-envelope me-1"></i>Admin Email</label>
                            <input type="text" name="Settings[admin_email]" class="form-control border-2" value="<?php echo settings_get('admin_email', YAAMP_ADMIN_EMAIL); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold"><i class="fa fa-comment-alt me-1"></i>BitcoinTalk Link</label>
                            <input type="text" name="Settings[link_bitcointalk]" class="form-control border-2" value="<?php echo settings_get('link_bitcointalk', ''); ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card-footer bg-light p-4 text-end border-0">
    <button type="submit" class="btn btn-primary btn-lg fw-bold px-5 shadow-sm rounded-pill"><i class="fa fa-save me-2"></i> Apply Global Settings</button>
</div>
<?php

echo CHtml::endForm();

echo '</div></div></div></div>';

echo '<style>
    .form-control:focus { border-color: #0d6efd; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1); }
    .card-header i { vertical-align: middle; }
</style>';
?>
