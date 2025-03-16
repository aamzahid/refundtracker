<?php
// Exit if accessed directly
if (!defined("ABSPATH")) {
    exit;
}
?>
<div class="wrap refund-tracker-admin">
    <h1><?php _e("Refund Tracker", "refund-tracker"); ?></h1>
    
    <div class="refund-tracker-admin-content">
        <div class="admin-card">
            <h2><?php _e("Plugin Information", "refund-tracker"); ?></h2>
            <p><?php _e("Use the shortcode <code>[refund_tracker]</code> to display the refund tracker on any page or post.", "refund-tracker"); ?></p>
            <p><?php _e("Only users with the <code>manage_refunds</code> capability can access the tracker. By default, Administrators and Refund Managers have this capability.", "refund-tracker"); ?></p>
        </div>
        
        <div class="admin-card">
            <h2><?php _e("Plugin Settings", "refund-tracker"); ?></h2>
            <p><?php _e("Future versions will include additional settings here.", "refund-tracker"); ?></p>
        </div>
    </div>
</div>;
    