<?php
/**
 * Enhanced Features for bbPress Support Toolkit
 * Integrates functionality from admin notes, live preview, mark as read, canned replies, etc.
 */

if (!defined('ABSPATH')) {
    exit();
}

class BBPS_Enhanced_Features {
    
    private static $instance = null;
    
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Admin Notes - only if enabled
        if (get_option('bbps_enable_admin_notes', 0)) {
            add_action('init', [$this, 'init_admin_notes']);
            add_action('bbp_theme_after_reply_content', [$this, 'display_admin_notes']);
            add_action('bbp_theme_after_topic_content', [$this, 'add_note_form']);
            add_action('bbp_theme_after_reply_content', [$this, 'add_note_form']);
            add_action('wp_ajax_bbps_save_note', [$this, 'save_admin_note']);
            add_action('wp_ajax_nopriv_bbps_save_note', [$this, 'save_admin_note']);
        }
        
        // Live Preview - only if enabled
        if (get_option('bbps_enable_live_preview', 0)) {
            add_action('wp_ajax_bbps_live_preview', [$this, 'ajax_live_preview']);
            add_action('wp_ajax_nopriv_bbps_live_preview', [$this, 'ajax_live_preview']);
            add_action('bbp_theme_before_topic_form_content', [$this, 'add_preview_button']);
            add_action('bbp_theme_before_reply_form_content', [$this, 'add_preview_button']);
        }
        
        // Mark as Read - only if enabled
        if (get_option('bbps_enable_mark_as_read', 0)) {
            add_action('wp_ajax_bbps_mark_read', [$this, 'ajax_mark_read']);
            add_action('wp_ajax_nopriv_bbps_mark_read', [$this, 'ajax_mark_read']);
            add_action('wp_ajax_bbps_mark_all_read', [$this, 'ajax_mark_all_read']);
            add_filter('bbp_topic_admin_links', [$this, 'add_mark_read_link'], 10, 2);
            add_filter('post_class', [$this, 'add_read_status_class']);
            add_action('wp_enqueue_scripts', [$this, 'enqueue_mark_read_scripts']);
        }
        
        // Canned Replies - only if enabled
        if (get_option('bbps_enable_canned_replies', 0)) {
            add_action('init', [$this, 'register_canned_replies_post_type']);
            add_action('bbp_theme_before_reply_form_content', [$this, 'display_canned_replies']);
            add_action('admin_menu', [$this, 'add_canned_replies_menu']);
            add_action('wp_enqueue_scripts', [$this, 'enqueue_canned_replies_scripts']);
        }
        
        // Topic Lock - only if enabled
        if (get_option('bbps_enable_topic_lock', 0)) {
            add_action('wp_ajax_bbps_topic_lock', [$this, 'ajax_topic_lock']);
            add_filter('bbp_topic_admin_links', [$this, 'add_topic_lock_link'], 10, 2);
            add_action('wp_enqueue_scripts', [$this, 'enqueue_topic_lock_scripts']);
        }
        
        // Report Content - only if enabled
        if (get_option('bbps_enable_report_content', 0)) {
            add_action('wp_ajax_bbps_report_content', [$this, 'ajax_report_content']);
            add_action('wp_ajax_nopriv_bbps_report_content', [$this, 'ajax_report_content']);
            add_filter('bbp_reply_admin_links', [$this, 'add_report_link'], 10, 2);
            add_action('bbp_theme_after_reply_content', [$this, 'add_report_link_to_content']);
            add_action('bbp_theme_after_topic_content', [$this, 'add_report_link_to_content']);
            add_action('wp_enqueue_scripts', [$this, 'enqueue_report_content_scripts']);
        }
    }
    
    // Admin Notes Functionality
    public function init_admin_notes() {
        add_post_type_support(bbp_get_topic_post_type(), 'comments');
        add_post_type_support(bbp_get_reply_post_type(), 'comments');
    }
    
    public function display_admin_notes($post_id = 0) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        
        if (!current_user_can('moderate')) {
            return;
        }
        
        $notes = get_comments([
            'post_id' => $post_id,
            'meta_key' => '_bbps_admin_note',
            'meta_value' => '1',
            'status' => 'approve'
        ]);
        
        if (!empty($notes)) {
            echo '<div class="bbps-admin-notes">';
            echo '<h4>' . __('Admin Notes', 'bbpress-support-toolkit') . '</h4>';
            
            foreach ($notes as $note) {
                echo '<div class="bbps-admin-note">';
                echo '<div class="bbps-note-meta">';
                echo '<strong>' . get_user_by('id', $note->user_id)->display_name . '</strong> - ';
                echo '<span class="bbps-note-date">' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($note->comment_date)) . '</span>';
                echo '</div>';
                echo '<div class="bbps-note-content">' . wpautop($note->comment_content) . '</div>';
                echo '</div>';
            }
            
            echo '</div>';
        }
    }
    
    public function add_note_form($post_id = 0) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        
        if (!current_user_can('moderate')) {
            return;
        }
        
        echo '<div class="bbps-add-note-wrapper">';
        echo '<a href="#" class="bbps-add-note-link bbps-admin-note-toggle" data-post-id="' . $post_id . '">' . __('Add Admin Note', 'bbpress-support-toolkit') . '</a>';
        echo '<form class="bbps-note-form bbps-admin-note-form" id="bbps-note-form-' . $post_id . '" style="display:none;" data-topic-id="' . $post_id . '">';
        echo '<textarea name="admin_note" placeholder="' . __('Enter admin note...', 'bbpress-support-toolkit') . '" rows="3" required></textarea>';
        echo '<input type="hidden" name="topic_id" value="' . $post_id . '">';
        echo '<button type="submit">' . __('Save Note', 'bbpress-support-toolkit') . '</button>';
        echo '</form>';
        echo '</div>';
    }
    
    public function save_admin_note() {
        check_ajax_referer('bbps_nonce', 'nonce');
        
        if (!current_user_can('moderate')) {
            wp_die(__('Permission denied', 'bbpress-support-toolkit'));
        }
        
        $post_id = intval($_POST['topic_id']) ?: intval($_POST['post_id']);
        $content = sanitize_textarea_field($_POST['admin_note']) ?: sanitize_textarea_field($_POST['content']);
        
        if (empty($content)) {
            wp_send_json_error(__('Note content is required', 'bbpress-support-toolkit'));
        }
        
        $comment_data = [
            'comment_post_ID' => $post_id,
            'comment_content' => $content,
            'comment_type' => 'bbps_admin_note',
            'comment_approved' => 1,
            'user_id' => get_current_user_id()
        ];
        
        $comment_id = wp_insert_comment($comment_data);
        
        if ($comment_id) {
            add_comment_meta($comment_id, '_bbps_admin_note', '1');
            wp_send_json_success(__('Note saved successfully', 'bbpress-support-toolkit'));
        } else {
            wp_send_json_error(__('Failed to save note', 'bbpress-support-toolkit'));
        }
    }
    
    // Live Preview Functionality
    public function add_preview_button() {
        echo '<div class="bbps-preview-wrapper">';
        echo '<button type="button" id="bbps-preview-btn" class="button">' . __('Preview', 'bbpress-support-toolkit') . '</button>';
        echo '<div id="bbps-preview-content" style="display:none; margin-top:10px; padding:10px; border:1px solid #ddd; background:#f9f9f9;"></div>';
        echo '</div>';
    }
    
    public function ajax_live_preview() {
        check_ajax_referer('bbps_nonce', 'nonce');
        
        $content = wp_kses_post($_POST['text']);
        $type = sanitize_text_field($_POST['type']);
        
        // Process content through WordPress filters
        $processed_content = apply_filters('the_content', $content);
        
        wp_send_json_success($processed_content);
    }
    
    // Mark as Read Functionality
    public function add_mark_read_link($links, $topic_id) {
        if (!is_user_logged_in()) {
            return $links;
        }
        
        // Ensure $links is an array
        if (!is_array($links)) {
            $links = array();
        }
        
        // Get the correct topic ID
        if (empty($topic_id)) {
            $topic_id = bbp_get_topic_id();
        }
        $topic_id = (int) $topic_id;
        
        $user_id = get_current_user_id();
        $is_read = $this->is_topic_read($user_id, $topic_id);
        
        $text = $is_read ? __('Mark as Unread', 'bbpress-support-toolkit') : __('Mark as Read', 'bbpress-support-toolkit');
        $action = $is_read ? 'unread' : 'read';
        $class = $is_read ? 'read' : 'unread';
        
        $links['mark_read'] = '<a href="#" class="bbps-mark-read ' . $class . '" data-topic="' . $topic_id . '" data-action="' . $action . '">' . $text . '</a>';
        
        return $links;
    }
    
    public function add_read_status_class($classes) {
        if (bbp_is_topic() && is_user_logged_in()) {
            $topic_id = bbp_get_topic_id();
            $user_id = get_current_user_id();
            
            if ($this->is_topic_read($user_id, $topic_id)) {
                $classes[] = 'bbps-topic-read';
            } else {
                $classes[] = 'bbps-topic-unread';
            }
        }
        
        return $classes;
    }
    
    public function ajax_mark_read() {
        check_ajax_referer('bbps_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Please log in', 'bbpress-support-toolkit'));
        }
        
        $topic_id = intval($_POST['topic_id']);
        $action = sanitize_text_field($_POST['mark_action']);
        $user_id = get_current_user_id();
        
        if ($action === 'read') {
            $this->mark_topic_read($user_id, $topic_id);
            $new_text = __('Mark as Unread', 'bbpress-support-toolkit');
            $new_action = 'unread';
        } else {
            $this->mark_topic_unread($user_id, $topic_id);
            $new_text = __('Mark as Read', 'bbpress-support-toolkit');
            $new_action = 'read';
        }
        
        wp_send_json_success([
            'new_text' => $new_text,
            'new_action' => $new_action
        ]);
    }
    
    public function ajax_mark_all_read() {
        check_ajax_referer('bbps_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Please log in', 'bbpress-support-toolkit'));
        }
        
        $user_id = get_current_user_id();
        
        // Get all topics
        $topics = get_posts([
            'post_type' => bbp_get_topic_post_type(),
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);
        
        foreach ($topics as $topic) {
            $this->mark_topic_read($user_id, $topic->ID);
        }
        
        wp_send_json_success(__('All topics marked as read', 'bbpress-support-toolkit'));
    }
    
    private function is_topic_read($user_id, $topic_id) {
        $read_topics = get_user_meta($user_id, '_bbps_read_topics', true);
        return is_array($read_topics) && in_array($topic_id, $read_topics);
    }
    
    private function mark_topic_read($user_id, $topic_id) {
        $read_topics = get_user_meta($user_id, '_bbps_read_topics', true);
        if (!is_array($read_topics)) {
            $read_topics = [];
        }
        
        if (!in_array($topic_id, $read_topics)) {
            $read_topics[] = $topic_id;
            update_user_meta($user_id, '_bbps_read_topics', $read_topics);
        }
    }
    
    private function mark_topic_unread($user_id, $topic_id) {
        $read_topics = get_user_meta($user_id, '_bbps_read_topics', true);
        if (is_array($read_topics)) {
            $read_topics = array_diff($read_topics, [$topic_id]);
            update_user_meta($user_id, '_bbps_read_topics', $read_topics);
        }
    }
    
    // Canned Replies Functionality
    public function register_canned_replies_post_type() {
        register_post_type('bbps_canned_reply', [
            'labels' => [
                'name' => __('Canned Replies', 'bbpress-support-toolkit'),
                'singular_name' => __('Canned Reply', 'bbpress-support-toolkit'),
                'add_new' => __('Add New Reply', 'bbpress-support-toolkit'),
                'add_new_item' => __('Add New Canned Reply', 'bbpress-support-toolkit'),
                'edit_item' => __('Edit Canned Reply', 'bbpress-support-toolkit'),
                'new_item' => __('New Canned Reply', 'bbpress-support-toolkit'),
                'view_item' => __('View Canned Reply', 'bbpress-support-toolkit'),
                'search_items' => __('Search Canned Replies', 'bbpress-support-toolkit'),
                'not_found' => __('No canned replies found', 'bbpress-support-toolkit'),
                'not_found_in_trash' => __('No canned replies found in trash', 'bbpress-support-toolkit')
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=' . bbp_get_forum_post_type(),
            'capability_type' => 'post',
            'capabilities' => [
                'edit_post' => 'moderate',
                'edit_posts' => 'moderate',
                'edit_others_posts' => 'moderate',
                'publish_posts' => 'moderate',
                'read_post' => 'moderate',
                'read_private_posts' => 'moderate',
                'delete_post' => 'moderate'
            ],
            'supports' => ['title', 'editor'],
            'menu_icon' => 'dashicons-format-chat'
        ]);
    }
    
    public function add_canned_replies_menu() {
        // Check if the menu already exists to avoid duplication
        global $submenu;
        $parent_slug = 'edit.php?post_type=' . bbp_get_forum_post_type();
        $menu_slug = 'edit.php?post_type=bbps_canned_reply';
        
        // Check if submenu already exists
        if (isset($submenu[$parent_slug])) {
            foreach ($submenu[$parent_slug] as $item) {
                if (isset($item[2]) && $item[2] === $menu_slug) {
                    return; // Menu already exists
                }
            }
        }
        
        add_submenu_page(
            $parent_slug,
            __('Canned Replies', 'bbpress-support-toolkit'),
            __('Canned Replies', 'bbpress-support-toolkit'),
            'moderate',
            $menu_slug
        );
    }
    
    public function enqueue_canned_replies_scripts() {
        if (bbp_is_single_topic() || bbp_is_topic_edit() || bbp_is_reply_edit()) {
            wp_enqueue_script('bbps-combined', plugin_dir_url(__FILE__) . '../assets/bbps-combined.js', array('jquery'), '1.0.0', true);
            wp_enqueue_style('bbps-style', plugin_dir_url(__FILE__) . '../assets/style.css', array(), '1.0.0');
            wp_localize_script('bbps-combined', 'bbps_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bbps_nonce'),
                'strings' => array(
                    'loading' => __('Loading...', 'bbpress-support-toolkit'),
                    'error' => __('Error occurred', 'bbpress-support-toolkit'),
                    'success' => __('Success', 'bbpress-support-toolkit')
                )
            ));
        }
    }
    
    public function enqueue_topic_lock_scripts() {
        if (bbp_is_single_topic() || bbp_is_topic_edit()) {
            wp_enqueue_script('bbps-combined', plugin_dir_url(__FILE__) . '../assets/bbps-combined.js', array('jquery'), '1.0.0', true);
            wp_localize_script('bbps-combined', 'bbps_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bbps_nonce'),
                'strings' => array(
                    'lock_topic' => __('Lock Topic', 'bbpress-support-toolkit'),
                    'unlock_topic' => __('Unlock Topic', 'bbpress-support-toolkit'),
                    'topic_locked_success' => __('Topic locked successfully', 'bbpress-support-toolkit'),
                    'topic_unlocked_success' => __('Topic unlocked successfully', 'bbpress-support-toolkit'),
                    'error_locking_topic' => __('Error locking topic', 'bbpress-support-toolkit'),
                    'error_unlocking_topic' => __('Error unlocking topic', 'bbpress-support-toolkit'),
                    'topic_id_not_found' => __('Topic ID not found', 'bbpress-support-toolkit'),
                    'ajax_error' => __('AJAX error occurred', 'bbpress-support-toolkit')
                )
            ));
        }
    }
    
    public function enqueue_report_content_scripts() {
        if (bbp_is_single_topic() || bbp_is_single_reply()) {
            wp_enqueue_script('bbps-combined', plugin_dir_url(__FILE__) . '../assets/bbps-combined.js', array('jquery'), '1.0.0', true);
            wp_localize_script('bbps-combined', 'bbps_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bbps_nonce'),
                'strings' => array(
                    'enter_report_reason' => __('Please enter the reason for reporting this content:', 'bbpress-support-toolkit'),
                    'reported' => __('Reported', 'bbpress-support-toolkit'),
                    'content_reported' => __('Content reported successfully', 'bbpress-support-toolkit'),
                    'error_reporting' => __('Error reporting content', 'bbpress-support-toolkit'),
                    'post_id_not_found' => __('Post ID not found', 'bbpress-support-toolkit'),
                    'ajax_error' => __('AJAX error occurred', 'bbpress-support-toolkit')
                )
            ));
        }
    }
    
    public function enqueue_mark_read_scripts() {
        if (bbp_is_single_topic() || bbp_is_topic_archive()) {
            wp_enqueue_script('bbps-combined', plugin_dir_url(__FILE__) . '../assets/bbps-combined.js', array('jquery'), '1.0.0', true);
            wp_localize_script('bbps-combined', 'bbps_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bbps_nonce'),
                'strings' => array(
                    'mark_read' => __('Mark as Read', 'bbpress-support-toolkit'),
                    'mark_unread' => __('Mark as Unread', 'bbpress-support-toolkit'),
                    'mark_all_read_confirm' => __('Mark all topics as read?', 'bbpress-support-toolkit'),
                    'error' => __('Error occurred', 'bbpress-support-toolkit')
                )
            ));
        }
    }
    
    public function display_canned_replies() {
        if (!current_user_can('moderate')) {
            return;
        }
        
        $replies = get_posts([
            'post_type' => 'bbps_canned_reply',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);
        
        if (empty($replies)) {
            return;
        }
        
        echo '<div class="bbps-canned-replies-wrapper">';
        echo '<a href="#" class="bbps-toggle-canned-replies">' . __('Canned Replies', 'bbpress-support-toolkit') . '</a>';
        echo '<div class="bbps-canned-replies-list" style="display:none;">';
        
        foreach ($replies as $reply) {
            echo '<div class="bbps-canned-reply-item" data-content="' . esc_attr($reply->post_content) . '">';
            echo '<strong>' . esc_html($reply->post_title) . '</strong>';
            echo '<p>' . wp_trim_words($reply->post_content, 20) . '</p>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
    }
    
    // Topic Lock Functionality
    public function add_topic_lock_link($links, $topic_id) {
        if (!current_user_can('moderate')) {
            return $links;
        }
        
        // Ensure $links is an array
        if (!is_array($links)) {
            $links = array();
        }
        
        // Get the correct topic ID
        if (empty($topic_id)) {
            $topic_id = bbp_get_topic_id();
        }
        $topic_id = (int) $topic_id;
        
        $is_locked = get_post_meta($topic_id, '_bbps_topic_locked', true);
        
        if ($is_locked) {
            $links['topic_lock'] = '<a href="#" class="bbps-topic-lock bbps-topic-lock-link" data-topic="' . $topic_id . '" data-topic-id="' . $topic_id . '" data-action="unlock">' . __('Unlock Topic', 'bbpress-support-toolkit') . '</a>';
        } else {
            $links['topic_lock'] = '<a href="#" class="bbps-topic-lock bbps-topic-lock-link" data-topic="' . $topic_id . '" data-topic-id="' . $topic_id . '" data-action="lock">' . __('Lock Topic', 'bbpress-support-toolkit') . '</a>';
        }
        
        return $links;
    }
    
    public function ajax_topic_lock() {
        check_ajax_referer('bbps_nonce', 'nonce');
        
        if (!current_user_can('moderate')) {
            wp_send_json_error(__('Permission denied', 'bbpress-support-toolkit'));
        }
        
        $topic_id = intval($_POST['topic_id']);
        $action = sanitize_text_field($_POST['action']) ?: sanitize_text_field($_POST['lock_action']);
        
        if ($action === 'lock') {
            update_post_meta($topic_id, '_bbps_topic_locked', '1');
            // Close the topic
            bbp_close_topic($topic_id);
            $new_text = __('Unlock Topic', 'bbpress-support-toolkit');
            $is_locked = true;
        } else {
            delete_post_meta($topic_id, '_bbps_topic_locked');
            // Open the topic
            bbp_open_topic($topic_id);
            $new_text = __('Lock Topic', 'bbpress-support-toolkit');
            $is_locked = false;
        }
        
        wp_send_json_success([
            'message' => __('Topic status updated', 'bbpress-support-toolkit'),
            'link_text' => $new_text,
            'is_locked' => $is_locked
        ]);
    }
    
    // Report Content Functionality
    public function add_report_link($links, $post_id) {
        if (!is_user_logged_in()) {
            return $links;
        }
        
        // Ensure $links is an array
        if (!is_array($links)) {
            $links = array();
        }
        
        // Get the correct post ID
        if (empty($post_id)) {
            $post_id = get_the_ID();
        }
        $post_id = (int) $post_id;
        
        $links['report'] = '<a href="#" class="bbps-report-content bbps-report-link" data-post="' . $post_id . '" data-post-id="' . $post_id . '">' . __('Report', 'bbpress-support-toolkit') . '</a>';
        
        return $links;
    }
    
    public function add_report_link_to_content() {
        $post_id = get_the_ID();
        
        if (!$post_id || !is_user_logged_in()) {
            return;
        }
        
        echo '<div class="bbps-report-wrapper">';
        echo '<a href="#" class="bbps-report-content bbps-report-link" data-post="' . $post_id . '" data-post-id="' . $post_id . '">' . __('Report', 'bbpress-support-toolkit') . '</a>';
        echo '</div>';
    }
    
    public function ajax_report_content() {
        check_ajax_referer('bbps_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('Please log in', 'bbpress-support-toolkit'));
        }
        
        $post_id = intval($_POST['post_id']);
        $reason = sanitize_textarea_field($_POST['reason']);
        $user_id = get_current_user_id();
        
        // Save report
        $report_data = [
            'post_id' => $post_id,
            'user_id' => $user_id,
            'reason' => $reason,
            'date' => current_time('mysql')
        ];
        
        $reports = get_option('bbps_content_reports', []);
        $reports[] = $report_data;
        update_option('bbps_content_reports', $reports);
        
        // Notify administrators
        $admin_email = get_option('admin_email');
        $subject = sprintf(__('[%s] Content Reported', 'bbpress-support-toolkit'), get_bloginfo('name'));
        $message = sprintf(
            __('A user has reported content on your forum.\n\nPost ID: %d\nReported by: %s\nReason: %s\n\nPlease review this content.', 'bbpress-support-toolkit'),
            $post_id,
            get_user_by('id', $user_id)->display_name,
            $reason
        );
        
        wp_mail($admin_email, $subject, $message);
        
        wp_send_json_success(__('Content reported successfully', 'bbpress-support-toolkit'));
    }
}

// Initialize the enhanced features
BBPS_Enhanced_Features::instance();