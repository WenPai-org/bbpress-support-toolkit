<?php
/*
 * Plugin Name: bbPress Support Toolkit
 * Plugin URI: https://cyberforums.com/bbpress-support-toolkit
 * Author: Cyberforums.com
 * Author URI: https://cyberforums.com
 * Description: Transform your bbPress forums into comprehensive support forums with status management, user ranking, and premium features
 * Version: 1.2.0
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * Requires Plugins: bbpress
 * Text Domain: bbpress-support-toolkit
 * Domain Path: /languages
*/

if (!defined("ABSPATH")) {
    exit();
}

final class BBPress_Support_Toolkit
{
    private static $instance = null;

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->define_constants();
        $this->init_hooks();
        $this->includes();
    }

    private function define_constants()
    {
        define("BBPS_VERSION", "1.2.0");
        define("BBPS_PLUGIN_FILE", __FILE__);
        define("BBPS_PLUGIN_PATH", plugin_dir_path(__FILE__));
        define("BBPS_PLUGIN_URL", plugin_dir_url(__FILE__));
        define("BBPS_INCLUDES_PATH", BBPS_PLUGIN_PATH . "includes/");
        define("BBPS_ASSETS_PATH", BBPS_PLUGIN_PATH . "assets/");
        define("BBPS_ASSETS_URL", BBPS_PLUGIN_URL . "assets/");
    }

    private function init_hooks()
    {
        register_activation_hook(__FILE__, [$this, "activate"]);
        register_uninstall_hook(__FILE__, ["BBPress_Support_Toolkit", "uninstall"]);
        add_action("plugins_loaded", [$this, "load_textdomain"]);
        add_action("wp_enqueue_scripts", [$this, "enqueue_styles"]);
        add_action("admin_enqueue_scripts", [$this, "enqueue_admin_scripts"]);
        add_action("widgets_init", [$this, "register_widgets"]);
    }

    private function includes()
    {
        require_once BBPS_INCLUDES_PATH . "core.php";

        if (is_admin()) {
            require_once BBPS_INCLUDES_PATH . "admin.php";
        }

        require_once BBPS_INCLUDES_PATH . "widgets.php";
    }

    public function activate()
    {
        $this->add_default_options();
        
        if (get_option("bbps_enable_search_url_rewrite")) {
            add_rewrite_rule('^search/([^/]+)/?$', 'index.php?s=$matches[1]', 'top');
            flush_rewrite_rules(false);
        }
        
        do_action("bbps_activation");
    }

    public static function uninstall()
    {
        delete_option("bbps_default_status");
        delete_option("bbps_enable_post_count");
        delete_option("bbps_enable_user_rank");
        delete_option("bbps_status_permissions");
        delete_option("bbps_reply_count");
        delete_option("bbps_used_status");
        delete_option("bbps_enable_topic_move");
        delete_option("bbps_status_permissions_urgent");
        delete_option("bbps_claim_topic");
        delete_option("bbps_claim_topic_display");
        delete_option("bbps_topic_assign");
        delete_option("bbps_notification_subject");
        delete_option("bbps_notification_message");
        delete_option("bbps_new_topic_days");
        delete_option("bbps_enable_new_topic_label");
        delete_option("bbps_enable_closed_topic_label");
        delete_option("bbps_enable_sticky_topic_label");
        delete_option("bbps_enable_search_integration");
        delete_option("bbps_enable_search_url_rewrite");
        delete_option("bbps_enable_post_author_label");
        delete_option("bbps_include_posts_in_search");
        delete_option("bbps_search_results_count");
        delete_option("bbps_enable_private_replies");
        delete_option("bbps_private_replies_capability");
        delete_option("bbps_enable_seo_optimization");
        delete_option("bbps_enable_meta_descriptions");
        delete_option("bbps_meta_description_length");
        delete_option("bbps_enable_open_graph");
        delete_option("bbps_enable_twitter_cards");
        delete_option("bbps_enable_schema_markup");
        delete_option("bbps_enable_canonical_urls");
        delete_option("bbps_forum_title_format");
        delete_option("bbps_enable_email_fix");
        delete_option("bbps_custom_email_from");
        delete_option("bbps_enable_title_length_fix");
        delete_option("bbps_max_title_length");
        delete_option("bbps_remove_topic_tags");
        delete_option("bbps_enable_default_forum");
        delete_option("bbps_default_forum_id");
        
        flush_rewrite_rules(false);
    }

    private function add_default_options()
    {
        $default_options = [
            "bbps_default_status" => "1",
            "bbps_enable_post_count" => "1",
            "bbps_enable_user_rank" => "1",
            "bbps_status_permissions" => [
                "admin" => "1",
                "mod" => "1",
                "user" => "1",
            ],
            "bbps_used_status" => [
                "res" => "1",
                "notres" => "1",
                "notsup" => "1",
            ],
            "bbps_enable_topic_move" => "1",
            "bbps_status_permissions_urgent" => "1",
            "bbps_claim_topic" => "1",
            "bbps_claim_topic_display" => "0",
            "bbps_topic_assign" => "1",
            "bbps_notification_subject" => __(
                "Your registration at %BLOGNAME%",
                "bbpress-support-toolkit"
            ),
            "bbps_notification_message" => __(
                "Thanks for signing up to our blog.\n\nYou can login with the following credentials by visiting %BLOGURL%\n\nUsername : %USERNAME%\nPassword : %PASSWORD%\n\nWe look forward to your next visit!\n\nThe team at %BLOGNAME%",
                "bbpress-support-toolkit"
            ),
            "bbps_new_topic_days" => "30",
            "bbps_enable_new_topic_label" => "1",
            "bbps_enable_closed_topic_label" => "1",
            "bbps_enable_sticky_topic_label" => "1",
            "bbps_enable_search_integration" => "1",
            "bbps_enable_search_url_rewrite" => "1",
            "bbps_enable_post_author_label" => "1",
            "bbps_include_posts_in_search" => "0",
            "bbps_search_results_count" => "20",
            "bbps_enable_private_replies" => "1",
            "bbps_private_replies_capability" => "moderate",
            "bbps_enable_seo_optimization" => "1",
            "bbps_enable_meta_descriptions" => "1",
            "bbps_meta_description_length" => "160",
            "bbps_enable_open_graph" => "1",
            "bbps_enable_twitter_cards" => "1",
            "bbps_enable_schema_markup" => "1",
            "bbps_enable_canonical_urls" => "1",
            "bbps_forum_title_format" => "%topic_title% - %forum_name% | %site_name%",
            "bbps_enable_email_fix" => "0",
            "bbps_custom_email_from" => "no-reply@example.com",
            "bbps_enable_title_length_fix" => "0",
            "bbps_max_title_length" => "150",
            "bbps_remove_topic_tags" => "0",
            "bbps_enable_default_forum" => "0",
            "bbps_default_forum_id" => ""
        ];

        foreach ($default_options as $key => $value) {
            add_option($key, $value);
        }
    }

    public function load_textdomain()
    {
        load_plugin_textdomain(
            "bbpress-support-toolkit",
            false,
            dirname(plugin_basename(__FILE__)) . "/languages/"
        );
    }

    public function enqueue_styles()
    {
        wp_enqueue_style(
            "bbps-style",
            BBPS_ASSETS_URL . "style.css",
            [],
            BBPS_VERSION
        );
    }

    public function enqueue_admin_scripts($hook)
    {
        // Only load on our plugin's admin page
        if ($hook !== 'settings_page_bbpress-support-toolkit') {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_style(
            "bbps-admin-style",
            BBPS_ASSETS_URL . "style.css",
            [],
            BBPS_VERSION
        );
    }

    public function register_widgets()
    {
        register_widget("BBPS_Support_Hours_Widget");
        register_widget("BBPS_Resolved_Count_Widget");
        register_widget("BBPS_Urgent_Topics_Widget");
        register_widget("BBPS_Recently_Resolved_Widget");
        register_widget("BBPS_Claimed_Topics_Widget");
        register_widget("BBPS_Register_Widget");
    }
}

function bbps()
{
    return BBPress_Support_Toolkit::instance();
}

bbps();