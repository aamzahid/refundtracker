<?php
/**
 * Plugin Name: Refund Tracker
 * Plugin URI: https://yourwebsite.com/refund-tracker
 * Description: A WordPress plugin to track and manage refunds with frontend admin panel.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * Text Domain: refund-tracker
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('REFUND_TRACKER_VERSION', '1.0.0');
define('REFUND_TRACKER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('REFUND_TRACKER_PLUGIN_URL', plugin_dir_url(__FILE__));

class RefundTracker {
    /**
     * Constructor - Initialize the plugin
     */
    public function __construct() {
        // Register activation hook
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
        
        // Register deactivation hook
        register_deactivation_hook(__FILE__, array($this, 'deactivate_plugin'));
        
        // Initialize plugin
        add_action('plugins_loaded', array($this, 'init_plugin'));
        
        // Register scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'register_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'register_admin_scripts'));
        
        // Add shortcode for frontend display
        add_shortcode('refund_tracker', array($this, 'refund_tracker_shortcode'));
        
        // AJAX handlers
        add_action('wp_ajax_add_refund', array($this, 'handle_add_refund'));
        add_action('wp_ajax_get_refunds', array($this, 'handle_get_refunds'));
        add_action('wp_ajax_update_refund_status', array($this, 'handle_update_refund_status'));
    }
    
    /**
     * Plugin activation
     */
    public function activate_plugin() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create refunds table
        $table_name = $wpdb->prefix . 'refund_tracker';
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            email varchar(100) NOT NULL,
            refund_type varchar(20) NOT NULL,
            amount decimal(10,2) NOT NULL,
            status varchar(20) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Add capabilities to admin
        $admin_role = get_role('administrator');
        $admin_role->add_cap('manage_refunds');
        
        // Add custom role for refund managers
        add_role(
            'refund_manager',
            __('Refund Manager', 'refund-tracker'),
            array(
                'read' => true,
                'manage_refunds' => true
            )
        );
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate_plugin() {
        // Remove custom capabilities
        $admin_role = get_role('administrator');
        $admin_role->remove_cap('manage_refunds');
        
        // Remove custom role
        remove_role('refund_manager');
    }
    
    /**
     * Initialize the plugin
     */
    public function init_plugin() {
        // Load text domain for internationalization
        load_plugin_textdomain('refund-tracker', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Register settings page
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    /**
     * Register plugin scripts and styles
     */
    public function register_scripts() {
        // Register main CSS
        wp_register_style(
            'refund-tracker-css',
            REFUND_TRACKER_PLUGIN_URL . 'assets/css/refund-tracker.css',
            array(),
            REFUND_TRACKER_VERSION
        );
        
        // Register main JavaScript
        wp_register_script(
            'refund-tracker-js',
            REFUND_TRACKER_PLUGIN_URL . 'assets/js/refund-tracker.js',
            array('jquery', 'jquery-ui-datepicker'),
            REFUND_TRACKER_VERSION,
            true
        );
        
        // Localize script with AJAX URL and nonce
        wp_localize_script('refund-tracker-js', 'refund_tracker_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('refund_tracker_nonce'),
            'date_format' => get_option('date_format')
        ));
    }
    
    /**
     * Register admin scripts and styles
     */
    public function register_admin_scripts() {
        // Register admin CSS
        wp_register_style(
            'refund-tracker-admin-css',
            REFUND_TRACKER_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            REFUND_TRACKER_VERSION
        );
        
        // Register admin JavaScript
        wp_register_script(
            'refund-tracker-admin-js',
            REFUND_TRACKER_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-datepicker'),
            REFUND_TRACKER_VERSION,
            true
        );
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Refund Tracker', 'refund-tracker'),
            __('Refund Tracker', 'refund-tracker'),
            'manage_refunds',
            'refund-tracker',
            array($this, 'render_admin_page'),
            'dashicons-money-alt',
            30
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        // Enqueue admin styles and scripts
        wp_enqueue_style('refund-tracker-admin-css');
        wp_enqueue_script('refund-tracker-admin-js');
        
        // Include admin template
        include_once REFUND_TRACKER_PLUGIN_DIR . 'templates/admin.php';
    }
    
    /**
     * Frontend shortcode handler
     */
    public function refund_tracker_shortcode($atts) {
        // Check if user has permission
        if (!current_user_can('manage_refunds')) {
            return '<p>' . __('You do not have permission to access this feature.', 'refund-tracker') . '</p>';
        }
        
        // Enqueue frontend styles and scripts
        wp_enqueue_style('refund-tracker-css');
        wp_enqueue_script('refund-tracker-js');
        
        // For jQuery UI datepicker
        wp_enqueue_style('jquery-ui-css', 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css');
        
        // Start output buffering to capture template content
        ob_start();
        include REFUND_TRACKER_PLUGIN_DIR . 'templates/frontend.php';
        return ob_get_clean();
    }
    
    /**
     * Handle AJAX request to add a refund
     */
    public function handle_add_refund() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'refund_tracker_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'refund-tracker')));
        }
        
        // Check permissions
        if (!current_user_can('manage_refunds')) {
            wp_send_json_error(array('message' => __('You do not have permission to add refunds', 'refund-tracker')));
        }
        
        // Validate and sanitize data
        $email = sanitize_email($_POST['email']);
        if (!is_email($email)) {
            wp_send_json_error(array('message' => __('Invalid email address', 'refund-tracker')));
        }
        
        $refund_type = sanitize_text_field($_POST['refund_type']);
        if (!in_array($refund_type, array('main', 'recurring'))) {
            wp_send_json_error(array('message' => __('Invalid refund type', 'refund-tracker')));
        }
        
        $amount = floatval($_POST['amount']);
        if ($amount <= 0) {
            wp_send_json_error(array('message' => __('Amount must be greater than zero', 'refund-tracker')));
        }
        
        $status = sanitize_text_field($_POST['status']);
        if (!in_array($status, array('refunded', 'not_refunded'))) {
            wp_send_json_error(array('message' => __('Invalid status', 'refund-tracker')));
        }
        
        // Prepare date (if custom date provided)
        $date = isset($_POST['date']) && !empty($_POST['date']) 
            ? date('Y-m-d H:i:s', strtotime($_POST['date'])) 
            : current_time('mysql');
        
        // Insert into database
        global $wpdb;
        $table_name = $wpdb->prefix . 'refund_tracker';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'date' => $date,
                'email' => $email,
                'refund_type' => $refund_type,
                'amount' => $amount,
                'status' => $status
            ),
            array('%s', '%s', '%s', '%f', '%s')
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => __('Failed to add refund', 'refund-tracker')));
        }
        
        wp_send_json_success(array(
            'message' => __('Refund added successfully', 'refund-tracker'),
            'refund_id' => $wpdb->insert_id
        ));
    }
    
    /**
     * Handle AJAX request to get refunds
     */
    public function handle_get_refunds() {
        // Check nonce
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'refund_tracker_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'refund-tracker')));
        }
        
        // Check permissions
        if (!current_user_can('manage_refunds')) {
            wp_send_json_error(array('message' => __('You do not have permission to view refunds', 'refund-tracker')));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'refund_tracker';
        
        // Build query based on filters
        $where_clauses = array();
        $where_values = array();
        
        // Date filter
        if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
            $where_clauses[] = 'date >= %s';
            $where_values[] = date('Y-m-d 00:00:00', strtotime($_GET['start_date']));
        }
        
        if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
            $where_clauses[] = 'date <= %s';
            $where_values[] = date('Y-m-d 23:59:59', strtotime($_GET['end_date']));
        }
        
        // Email filter
        if (isset($_GET['email']) && !empty($_GET['email'])) {
            $where_clauses[] = 'email LIKE %s';
            $where_values[] = '%' . $wpdb->esc_like($_GET['email']) . '%';
        }
        
        // Refund type filter
        if (isset($_GET['refund_type']) && !empty($_GET['refund_type'])) {
            if ($_GET['refund_type'] !== 'all') {
                $where_clauses[] = 'refund_type = %s';
                $where_values[] = $_GET['refund_type'];
            }
        }
        
        // Status filter
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            if ($_GET['status'] !== 'all') {
                $where_clauses[] = 'status = %s';
                $where_values[] = $_GET['status'];
            }
        }
        
        // Build the complete query
        $query = "SELECT * FROM $table_name";
        
        if (!empty($where_clauses)) {
            $query .= " WHERE " . implode(' AND ', $where_clauses);
        }
        
        // Ordering
        $orderby = isset($_GET['orderby']) ? sanitize_sql_orderby($_GET['orderby']) : 'date';
        $order = isset($_GET['order']) && strtoupper($_GET['order']) === 'ASC' ? 'ASC' : 'DESC';
        
        $query .= " ORDER BY $orderby $order";
        
        // Prepare and execute query
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        $refunds = $wpdb->get_results($query);
        
        // Calculate totals
        $main_refund_total = 0;
        $recurring_refund_total = 0;
        $not_refunded_total = 0;
        
        foreach ($refunds as $refund) {
            if ($refund->status === 'refunded') {
                if ($refund->refund_type === 'main') {
                    $main_refund_total += floatval($refund->amount);
                } else if ($refund->refund_type === 'recurring') {
                    $recurring_refund_total += floatval($refund->amount);
                }
            } else if ($refund->status === 'not_refunded') {
                $not_refunded_total += floatval($refund->amount);
            }
        }
        
        $total_refund = $main_refund_total + $recurring_refund_total;
        
        wp_send_json_success(array(
            'refunds' => $refunds,
            'totals' => array(
                'main_refund_total' => $main_refund_total,
                'recurring_refund_total' => $recurring_refund_total,
                'total_refund' => $total_refund,
                'not_refunded_total' => $not_refunded_total
            )
        ));
    }
    
    /**
     * Handle AJAX request to update refund status
     */
    public function handle_update_refund_status() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'refund_tracker_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'refund-tracker')));
        }
        
       // Check permissions
       if (!current_user_can('manage_refunds')) {
        wp_send_json_error(array('message' => __('You do not have permission to update refunds', 'refund-tracker')));
    }
    
    // Validate data
    $refund_id = intval($_POST['refund_id']);
    if ($refund_id <= 0) {
        wp_send_json_error(array('message' => __('Invalid refund ID', 'refund-tracker')));
    }
    
    $status = sanitize_text_field($_POST['status']);
    if (!in_array($status, array('refunded', 'not_refunded'))) {
        wp_send_json_error(array('message' => __('Invalid status', 'refund-tracker')));
    }
    
    // Update database
    global $wpdb;
    $table_name = $wpdb->prefix . 'refund_tracker';
    
    $result = $wpdb->update(
        $table_name,
        array('status' => $status),
        array('id' => $refund_id),
        array('%s'),
        array('%d')
    );
    
    if ($result === false) {
        wp_send_json_error(array('message' => __('Failed to update refund status', 'refund-tracker')));
    }
    
    wp_send_json_success(array(
        'message' => __('Refund status updated successfully', 'refund-tracker')
    ));
}
}

// Initialize the plugin
$refund_tracker = new RefundTracker();

/**
* Create template files
*/

// Create directories if they don't exist
function refund_tracker_create_directories() {
// Plugin directories
if (!file_exists(REFUND_TRACKER_PLUGIN_DIR . 'templates')) {
    mkdir(REFUND_TRACKER_PLUGIN_DIR . 'templates', 0755, true);
}

if (!file_exists(REFUND_TRACKER_PLUGIN_DIR . 'assets/css')) {
    mkdir(REFUND_TRACKER_PLUGIN_DIR . 'assets/css', 0755, true);
}

if (!file_exists(REFUND_TRACKER_PLUGIN_DIR . 'assets/js')) {
    mkdir(REFUND_TRACKER_PLUGIN_DIR . 'assets/js', 0755, true);
}
}

register_activation_hook(__FILE__, 'refund_tracker_create_directories');

/**
 * Create necessary template files on plugin activation
 */
