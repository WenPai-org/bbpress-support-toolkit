<?php
/**
 * Advanced Features for bbPress Support Toolkit
 * 
 * This file contains implementations of advanced features including:
 * - Admin Notes
 * - Live Preview
 * - Mark as Read
 * - Canned Replies
 * - Report Content
 * - Topic Lock
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Notes Feature
 */
class BBPS_Admin_Notes {
    
    public function __construct() {
        if (get_option('bbps_enable_admin_notes', 0)) {
            add_action('init', array($this, 'init'));
        }
    }
    
    public function init() {
        add_action('bbp_theme_after_reply_content', array($this, 'display_admin_note'));
        add_action('bbp_theme_after_topic_content', array($this, 'display_admin_note'));
        add_action('add_meta_boxes', array($this, 'add_admin_note_metabox'));
        add_action('save_post', array($this, 'save_admin_note'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }
    
    public function display_admin_note() {
        if (!current_user_can('moderate_forums')) {
            return;
        }
        
        $post_id = get_the_ID();
        $admin_note = get_post_meta($post_id, '_bbps_admin_note', true);
        
        if (!empty($admin_note)) {
            echo '<div class="bbps-admin-note">';
            echo '<strong>' . __('Admin Note:', 'bbpress-support-toolkit') . '</strong> ';
            echo esc_html($admin_note);
            echo '</div>';
        }
    }
    
    public function add_admin_note_metabox() {
        add_meta_box(
            'bbps_admin_note',
            __('Admin Note', 'bbpress-support-toolkit'),
            array($this, 'admin_note_metabox_callback'),
            array('topic', 'reply'),
            'side',
            'high'
        );
    }
    
    public function admin_note_metabox_callback($post) {
        wp_nonce_field('bbps_admin_note_nonce', 'bbps_admin_note_nonce');
        $admin_note = get_post_meta($post->ID, '_bbps_admin_note', true);
        
        echo '<textarea name="bbps_admin_note" rows="4" style="width: 100%;">' . esc_textarea($admin_note) . '</textarea>';
        echo '<p class="description">' . __('This note is only visible to administrators and moderators.', 'bbpress-support-toolkit') . '</p>';
    }
    
    public function save_admin_note($post_id) {
        if (!isset($_POST['bbps_admin_note_nonce']) || !wp_verify_nonce($_POST['bbps_admin_note_nonce'], 'bbps_admin_note_nonce')) {
            return;
        }
        
        if (!current_user_can('moderate_forums')) {
            return;
        }
        
        if (isset($_POST['bbps_admin_note'])) {
            update_post_meta($post_id, '_bbps_admin_note', sanitize_textarea_field($_POST['bbps_admin_note']));
        }
    }
    
    public function enqueue_styles() {
        $is_single_topic_or_reply = false;
        if (function_exists('bbp_is_single_topic') && function_exists('bbp_is_single_reply')) {
            $is_single_topic_or_reply = bbp_is_single_topic() || bbp_is_single_reply();
        }
        if ($is_single_topic_or_reply) {
            wp_enqueue_style('bbps-advanced-features', plugin_dir_url(__FILE__) . '../assets/advanced-features.css', array(), '1.0.0');
        }
    }
}

/**
 * Live Preview Feature
 */
class BBPS_Live_Preview {
    
    public function __construct() {
        if (get_option('bbps_enable_live_preview', 0)) {
            add_action('init', array($this, 'init'));
        }
    }
    
    public function init() {
        add_action('bbp_theme_after_reply_form_content', array($this, 'add_preview_button'));
        add_action('bbp_theme_after_topic_form_content', array($this, 'add_preview_button'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_bbps_preview_content', array($this, 'ajax_preview_content'));
        add_action('wp_ajax_nopriv_bbps_preview_content', array($this, 'ajax_preview_content'));
    }
    
    public function add_preview_button() {
        echo '<div class="bbps-preview-container">';
        echo '<button type="button" id="bbps-preview-btn" class="button">' . __('Preview', 'bbpress-support-toolkit') . '</button>';
        echo '<div id="bbps-preview-content" style="display: none; margin-top: 10px; padding: 10px; border: 1px solid #ddd; background: #f9f9f9;"></div>';
        echo '</div>';
    }
    
    public function enqueue_scripts() {
        // Check if bbPress functions exist and if we're on a bbPress page
        $is_bbpress_page = false;
        if (function_exists('bbp_is_topic_edit') && function_exists('bbp_is_reply_edit')) {
            $is_bbpress_page = bbp_is_topic_edit() || bbp_is_reply_edit();
        }
        if (function_exists('bbp_is_topic_edit') && function_exists('bbp_is_reply_edit')) {
            $is_bbpress_page = $is_bbpress_page || bbp_is_topic_edit() || bbp_is_reply_edit();
        }
        
        // Fallback: check if we're on any bbPress related page
        if (!$is_bbpress_page && function_exists('bbp_is_bbpress')) {
            $is_bbpress_page = bbp_is_bbpress();
        }
        
        if ($is_bbpress_page) {
            wp_enqueue_script('bbps-combined', plugin_dir_url(__FILE__) . '../assets/bbps-combined.js', array('jquery'), '1.0.0', true);
            wp_localize_script('bbps-combined', 'bbps_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bbps_preview_nonce')
            ));
        }
    }
    
    public function ajax_preview_content() {
        if (!wp_verify_nonce($_POST['nonce'], 'bbps_preview_nonce')) {
            wp_die(__('Security check failed', 'bbpress-support-toolkit'));
        }
        
        $content = wp_kses_post($_POST['content']);
        $preview = apply_filters('the_content', $content);
        
        wp_send_json_success($preview);
    }
}

/**
 * Mark as Read Feature
 */
class BBPS_Mark_As_Read {
    
    public function __construct() {
        if (get_option('bbps_enable_mark_as_read', 0)) {
            add_action('init', array($this, 'init'));
        }
    }
    
    public function init() {
        add_action('bbp_template_after_single_topic', array($this, 'add_mark_read_link'));
        add_action('wp_ajax_bbps_mark_read', array($this, 'ajax_mark_read'));
        add_action('wp_ajax_nopriv_bbps_mark_read', array($this, 'ajax_mark_read'));
        add_filter('bbp_get_topic_class', array($this, 'add_read_status_class'), 10, 2);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    public function add_mark_read_link() {
        if (!is_user_logged_in()) {
            return;
        }
        
        $topic_id = function_exists('bbp_get_topic_id') ? bbp_get_topic_id() : 0;
        $user_id = get_current_user_id();
        $is_read = $this->is_topic_read($topic_id, $user_id);
        
        $text = $is_read ? __('Mark as Unread', 'bbpress-support-toolkit') : __('Mark as Read', 'bbpress-support-toolkit');
        $action = $is_read ? 'unread' : 'read';
        
        echo '<div class="bbps-mark-read-container">';
        echo '<a href="#" class="bbps-mark-read" data-topic="' . $topic_id . '" data-action="' . $action . '">' . $text . '</a>';
        echo '</div>';
    }
    
    public function is_topic_read($topic_id, $user_id) {
        $read_topics = get_user_meta($user_id, '_bbps_read_topics', true);
        return is_array($read_topics) && in_array($topic_id, $read_topics);
    }
    
    public function ajax_mark_read() {
        if (!is_user_logged_in()) {
            wp_die(__('Please log in', 'bbpress-support-toolkit'));
        }
        
        $topic_id = intval($_POST['topic_id']);
        $action = sanitize_text_field($_POST['action']);
        $user_id = get_current_user_id();
        
        $read_topics = get_user_meta($user_id, '_bbps_read_topics', true);
        if (!is_array($read_topics)) {
            $read_topics = array();
        }
        
        if ($action === 'read') {
            if (!in_array($topic_id, $read_topics)) {
                $read_topics[] = $topic_id;
            }
        } else {
            $read_topics = array_diff($read_topics, array($topic_id));
        }
        
        update_user_meta($user_id, '_bbps_read_topics', $read_topics);
        
        wp_send_json_success(array(
            'action' => $action,
            'new_text' => $action === 'read' ? __('Mark as Unread', 'bbpress-support-toolkit') : __('Mark as Read', 'bbpress-support-toolkit'),
            'new_action' => $action === 'read' ? 'unread' : 'read'
        ));
    }
    
    public function add_read_status_class($classes, $topic_id) {
        if (is_user_logged_in() && $this->is_topic_read($topic_id, get_current_user_id())) {
            $classes[] = 'bbps-topic-read';
        } else {
            $classes[] = 'bbps-topic-unread';
        }
        return $classes;
    }
    
    public function enqueue_scripts() {
        $is_single_topic = function_exists('bbp_is_single_topic') && bbp_is_single_topic();
        if ($is_single_topic) {
            wp_enqueue_script('bbps-combined', plugin_dir_url(__FILE__) . '../assets/bbps-combined.js', array('jquery'), '1.0.0', true);
            wp_localize_script('bbps-combined', 'bbps_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bbps_nonce'),
                'strings' => array(
                    'mark_read' => __('Mark as Read', 'bbpress-support-toolkit'),
                    'mark_unread' => __('Mark as Unread', 'bbpress-support-toolkit'),
                    'error' => __('An error occurred', 'bbpress-support-toolkit')
                )
            ));
        }
    }
}

/**
 * Report Content Feature
 */
class BBPS_Report_Content {
    
    public function __construct() {
        if (get_option('bbps_enable_report_content', 0)) {
            add_action('init', array($this, 'init'));
        }
    }
    
    public function init() {
        add_action('bbp_template_after_single_topic', array($this, 'add_report_link'));
        add_action('bbp_template_after_single_reply', array($this, 'add_report_link'));
        add_action('wp_ajax_bbps_report_content', array($this, 'ajax_report_content'));
        add_action('wp_ajax_nopriv_bbps_report_content', array($this, 'ajax_report_content'));
        add_action('init', array($this, 'register_post_status'));
    }
    
    public function register_post_status() {
        register_post_status('reported', array(
            'label' => __('Reported', 'bbpress-support-toolkit'),
            'public' => false,
            'exclude_from_search' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Reported <span class="count">(%s)</span>', 'Reported <span class="count">(%s)</span>', 'bbpress-support-toolkit')
        ));
    }
    
    public function add_report_link() {
        if (!is_user_logged_in()) {
            return;
        }
        
        $post_id = get_the_ID();
        echo '<div class="bbps-report-container">';
        echo '<a href="#" class="bbps-report-content" data-post="' . $post_id . '">' . __('Report', 'bbpress-support-toolkit') . '</a>';
        echo '</div>';
    }
    
    public function ajax_report_content() {
        if (!is_user_logged_in()) {
            wp_die(__('Please log in', 'bbpress-support-toolkit'));
        }
        
        $post_id = intval($_POST['post_id']);
        $reason = sanitize_textarea_field($_POST['reason']);
        
        // Mark post as reported
        wp_update_post(array(
            'ID' => $post_id,
            'post_status' => 'reported'
        ));
        
        // Save report reason
        update_post_meta($post_id, '_bbps_report_reason', $reason);
        update_post_meta($post_id, '_bbps_reported_by', get_current_user_id());
        update_post_meta($post_id, '_bbps_reported_date', current_time('mysql'));
        
        wp_send_json_success(__('Content reported successfully.', 'bbpress-support-toolkit'));
    }
}

/**
 * Canned Replies Feature
 * Note: Functionality moved to enhanced-features.php to avoid duplication
 */
class BBPS_Canned_Replies {
    
    public function __construct() {
        // Functionality moved to enhanced-features.php
        // This prevents duplicate registration
    }
}

/**
 * Topic Lock Feature
 */
class BBPS_Topic_Lock {
    
    public function __construct() {
        if (get_option('bbps_enable_topic_lock', 0)) {
            add_action('init', array($this, 'init'));
        }
    }
    
    public function init() {
        add_action('bbp_template_before_single_topic', array($this, 'check_topic_lock'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_bbps_topic_lock', array($this, 'ajax_topic_lock'));
        add_action('wp_ajax_nopriv_bbps_topic_lock', array($this, 'ajax_topic_lock'));
    }
    
    public function check_topic_lock() {
        if (!current_user_can('moderate_forums')) {
            return;
        }
        
        $topic_id = function_exists('bbp_get_topic_id') ? bbp_get_topic_id() : 0;
        $current_user_id = get_current_user_id();
        
        // Check if topic is locked by another user
        $lock_info = get_transient('bbps_topic_lock_' . $topic_id);
        
        if ($lock_info && $lock_info['user_id'] != $current_user_id) {
            $user_info = get_userdata($lock_info['user_id']);
            echo '<div class="bbps-topic-lock-warning">';
            echo '<span class="dashicons dashicons-warning"></span>';
            printf(
                __('Warning: %s is currently viewing this topic (since %s)', 'bbpress-support-toolkit'),
                esc_html($user_info->display_name),
                esc_html(human_time_diff($lock_info['timestamp']))
            );
            echo '</div>';
        }
        
        // Set lock for current user
        set_transient('bbps_topic_lock_' . $topic_id, array(
            'user_id' => $current_user_id,
            'timestamp' => time()
        ), 300); // 5 minutes
    }
    
    public function enqueue_scripts() {
        $is_single_topic = function_exists('bbp_is_single_topic') && bbp_is_single_topic();
        if ($is_single_topic && current_user_can('moderate_forums')) {
            wp_enqueue_script('bbps-combined', plugin_dir_url(__FILE__) . '../assets/bbps-combined.js', array('jquery'), '1.0.0', true);
            $topic_id = function_exists('bbp_get_topic_id') ? bbp_get_topic_id() : 0;
            wp_localize_script('bbps-combined', 'bbps_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'topic_id' => $topic_id,
                'user_id' => get_current_user_id()
            ));
        }
    }
    
    public function ajax_topic_lock() {
        $topic_id = intval($_POST['topic_id']);
        $user_id = get_current_user_id();
        
        if (!current_user_can('moderate_forums')) {
            wp_send_json_error(__('Permission denied', 'bbpress-support-toolkit'));
        }
        
        // Update lock
        set_transient('bbps_topic_lock_' . $topic_id, array(
            'user_id' => $user_id,
            'timestamp' => time()
        ), 300);
        
        wp_send_json_success();
    }
}

// Initialize all features
new BBPS_Admin_Notes();
// BBPS_Live_Preview functionality moved to enhanced-features.php to avoid duplication
// new BBPS_Live_Preview();
new BBPS_Mark_As_Read();
new BBPS_Report_Content();
// BBPS_Canned_Replies functionality moved to enhanced-features.php
new BBPS_Topic_Lock();