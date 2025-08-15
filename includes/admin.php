<?php

// Ensure WordPress is loaded and prevent direct access
if (!defined("ABSPATH")) {
    exit();
}

// bbPress activation check removed as requested

// Ensure we have WordPress admin functions available
// This file should only be loaded when WordPress is fully initialized
if (!function_exists('add_action') || !function_exists('wp_nonce_field') || !function_exists('esc_html_e')) {
    // WordPress is not fully loaded, exit gracefully
    return;
}



add_action("bbp_forum_metabox", "bbps_extend_forum_attributes_mb");

function bbps_extend_forum_attributes_mb($forum_id)
{
    $premium_forum = bbps_is_premium_forum($forum_id);
    $checked = $premium_forum ? "checked" : "";

    $support_forum = bbps_is_support_forum($forum_id);
    $checked1 = $support_forum ? "checked" : "";
    ?>
    <hr />
    <?php wp_nonce_field('bbps_forum_attributes', 'bbps_forum_attributes_nonce'); ?>

    <p>
        <strong><?php esc_html_e(
            "Premium Forum:",
            "bbpress-support-toolkit"
        ); ?></strong>
        <input type="checkbox" name="bbps-premium-forum" value="1" <?php echo $checked; ?>/>
        <br />
    </p>
    <p>
        <strong><?php esc_html_e(
            "Support Forum:",
            "bbpress-support-toolkit"
        ); ?></strong>
        <input type="checkbox" name="bbps-support-forum" value="1" <?php echo $checked1; ?>/>
        <br />
    </p>
    <?php
}

add_action(
    "bbp_forum_attributes_metabox_save",
    "bbps_forum_attributes_mb_save"
);

function bbps_forum_attributes_mb_save($forum_id)
{
    // Verify nonce
    if (!isset($_POST['bbps_forum_attributes_nonce']) || !wp_verify_nonce($_POST['bbps_forum_attributes_nonce'], 'bbps_forum_attributes')) {
        return;
    }

    if (!current_user_can("edit_forum", $forum_id)) {
        return;
    }

    $premium_forum = get_post_meta($forum_id, "_bbps_is_premium", true);
    $support_forum = get_post_meta($forum_id, "_bbps_is_support", true);

    if (!empty($_POST["bbps-premium-forum"])) {
        update_post_meta($forum_id, "_bbps_is_premium", 1);
    } elseif (!empty($premium_forum)) {
        update_post_meta($forum_id, "_bbps_is_premium", 0);
    }

    if (!empty($_POST["bbps-support-forum"])) {
        update_post_meta($forum_id, "_bbps_is_support", 1);
    } elseif (!empty($support_forum)) {
        update_post_meta($forum_id, "_bbps_is_support", 0);
    }

    return $forum_id;
}

add_action("admin_menu", "bbps_add_admin_menu");

function bbps_add_admin_menu()
{
    add_options_page(
        __("bbPress Support Toolkit", "bbpress-support-toolkit"),
        __("bbPress Support", "bbpress-support-toolkit"),
        "manage_options",
        "bbpress-support-toolkit",
        "bbps_admin_page"
    );
}

add_action("admin_init", "bbps_register_admin_settings");
add_action("wp_ajax_bbps_save_settings", "bbps_ajax_save_settings");
add_action("admin_enqueue_scripts", "bbps_enqueue_admin_scripts");

register_activation_hook(dirname(__FILE__) . '/../bbpress-support-toolkit.php', 'bbps_init_default_user_ranking');

function bbps_register_admin_settings()
{
    bbps_init_default_user_ranking();
    
    add_settings_section(
        "bbps-user-ranking",
        __("User Ranking", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_ranking_section",
        "bbpress-support-toolkit"
    );

    register_setting("bbpress-support-toolkit", "bbps_reply_count", "bbps_validate_options");

    for ($i = 1; $i < 6; $i++) {
        add_settings_field(
            "bbps_reply_count_" . $i,
            sprintf(__("User ranking level %d", "bbpress-support-toolkit"), $i),
            "bbps_admin_setting_callback_reply_count",
            "bbpress-support-toolkit",
            "bbps-user-ranking",
            [$i]
        );
    }

    add_settings_field(
        "bbps_enable_post_count",
        __("Show forum post count", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_post_count",
        "bbpress-support-toolkit",
        "bbps-user-ranking"
    );
    register_setting("bbpress-support-toolkit", "bbps_enable_post_count", "intval");

    add_settings_field(
        "bbps_enable_user_rank",
        __("Show Rank", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_user_rank",
        "bbpress-support-toolkit",
        "bbps-user-ranking"
    );
    register_setting("bbpress-support-toolkit", "bbps_enable_user_rank", "intval");

    add_settings_section(
        "bbps-topic-status",
        __("Topic Status Settings", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_status_section",
        "bbpress-support-toolkit"
    );

    register_setting("bbpress-support-toolkit", "bbps_default_status", "intval");
    add_settings_field(
        "bbps_default_status",
        __("Default Status:", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_default_status",
        "bbpress-support-toolkit",
        "bbps-topic-status"
    );

    register_setting(
        "bbpress-support-toolkit",
        "bbps_used_status",
        "bbps_validate_checkbox_group"
    );
    add_settings_field(
        "bbps_used_status_1",
        __("Display Status:", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_displayed_status_res",
        "bbpress-support-toolkit",
        "bbps-topic-status"
    );
    add_settings_field(
        "bbps_used_status_2",
        "",
        "bbps_admin_setting_callback_displayed_status_notres",
        "bbpress-support-toolkit",
        "bbps-topic-status"
    );
    add_settings_field(
        "bbps_used_status_3",
        "",
        "bbps_admin_setting_callback_displayed_status_notsup",
        "bbpress-support-toolkit",
        "bbps-topic-status"
    );

    register_setting(
        "bbpress-support-toolkit",
        "bbps_status_permissions",
        "bbps_validate_checkbox_group"
    );
    add_settings_field(
        "bbps_status_permissions_admin",
        __("Admin", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_permission_admin",
        "bbpress-support-toolkit",
        "bbps-topic-status"
    );
    add_settings_field(
        "bbps_status_permissions_user",
        __("Topic Creator", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_permission_user",
        "bbpress-support-toolkit",
        "bbps-topic-status"
    );
    add_settings_field(
        "bbps_status_permissions_moderator",
        __("Forum Moderator", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_permission_moderator",
        "bbpress-support-toolkit",
        "bbps-topic-status"
    );

    add_settings_section(
        "bbps-support-forum",
        __("Support Forum Settings", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_support_forum_section",
        "bbpress-support-toolkit"
    );

    register_setting("bbpress-support-toolkit", "bbps_status_permissions_urgent", "intval");
    add_settings_field(
        "bbps_status_permissions_urgent",
        __("Urgent Topic Status", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_urgent",
        "bbpress-support-toolkit",
        "bbps-support-forum"
    );

    add_settings_field(
        "bbps_enable_topic_move",
        __("Move topics", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_move_topic",
        "bbpress-support-toolkit",
        "bbps-support-forum"
    );
    register_setting("bbpress-support-toolkit", "bbps_enable_topic_move", "intval");

    add_settings_field(
        "bbps_topic_assign",
        __("Assign topics", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_assign_topic",
        "bbpress-support-toolkit",
        "bbps-support-forum"
    );
    register_setting("bbpress-support-toolkit", "bbps_topic_assign", "intval");

    add_settings_field(
        "bbps_claim_topic",
        __("Claim topics", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_claim_topic",
        "bbpress-support-toolkit",
        "bbps-support-forum"
    );
    register_setting("bbpress-support-toolkit", "bbps_claim_topic", "intval");

    add_settings_field(
        "bbps_claim_topic_display",
        __("Display Username:", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_claim_topic_display",
        "bbpress-support-toolkit",
        "bbps-support-forum"
    );
    register_setting("bbpress-support-toolkit", "bbps_claim_topic_display", "intval");

    add_settings_field(
        "bbps_notification_subject",
        __("Email Notification Subject:", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_notification_subject",
        "bbpress-support-toolkit",
        "bbps-support-forum"
    );
    register_setting(
        "bbpress-support-toolkit",
        "bbps_notification_subject",
        "sanitize_text_field"
    );

    add_settings_field(
        "bbps_notification_message",
        __("Email Notification Message:", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_notification_message",
        "bbpress-support-toolkit",
        "bbps-support-forum"
    );
    register_setting(
        "bbpress-support-toolkit",
        "bbps_notification_message",
        "sanitize_textarea_field"
    );

    add_settings_section(
        "bbps-topic-labels",
        __("Topic Labels", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_label_section",
        "bbpress-support-toolkit"
    );

    add_settings_field(
        "bbps_enable_new_topic_label",
        __("Enable New Topic Label", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_new_topic_label",
        "bbpress-support-toolkit",
        "bbps-topic-labels"
    );
    register_setting("bbpress-support-toolkit", "bbps_enable_new_topic_label", "intval");

    add_settings_field(
        "bbps_new_topic_days",
        __("New Topic Days", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_new_topic_days",
        "bbpress-support-toolkit",
        "bbps-topic-labels"
    );
    register_setting("bbpress-support-toolkit", "bbps_new_topic_days", "intval");

    add_settings_field(
        "bbps_enable_closed_topic_label",
        __("Enable Closed Topic Label", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_closed_topic_label",
        "bbpress-support-toolkit",
        "bbps-topic-labels"
    );
    register_setting("bbpress-support-toolkit", "bbps_enable_closed_topic_label", "intval");

    add_settings_field(
        "bbps_enable_sticky_topic_label",
        __("Enable Sticky Topic Label", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_sticky_topic_label",
        "bbpress-support-toolkit",
        "bbps-topic-labels"
    );
    register_setting("bbpress-support-toolkit", "bbps_enable_sticky_topic_label", "intval");

    add_settings_field(
        "bbps_enable_post_author_label",
        __("Enable Original Poster Label", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_post_author_label",
        "bbpress-support-toolkit",
        "bbps-topic-labels"
    );
    register_setting("bbpress-support-toolkit", "bbps_enable_post_author_label", "intval");

    add_settings_section(
        "bbps-search-settings",
        __("Search Settings", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_search_section",
        "bbpress-support-toolkit"
    );

    add_settings_field(
        "bbps_enable_search_integration",
        __("Enable Forum Search Integration", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_search_integration",
        "bbpress-support-toolkit",
        "bbps-search-settings"
    );
    register_setting("bbpress-support-toolkit", "bbps_enable_search_integration", "intval");

    add_settings_field(
        "bbps_enable_search_url_rewrite",
        __("Enable Search URL Rewrite", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_search_url_rewrite",
        "bbpress-support-toolkit",
        "bbps-search-settings"
    );
    register_setting("bbpress-support-toolkit", "bbps_enable_search_url_rewrite", "bbps_validate_search_rewrite");

    add_settings_field(
        "bbps_include_posts_in_search",
        __("Include Blog Posts in Search", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_include_posts_search",
        "bbpress-support-toolkit",
        "bbps-search-settings"
    );
    register_setting("bbpress-support-toolkit", "bbps_include_posts_in_search", "intval");

    add_settings_field(
        "bbps_search_results_count",
        __("Search Results Count", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_search_results_count",
        "bbpress-support-toolkit",
        "bbps-search-settings"
    );
    register_setting("bbpress-support-toolkit", "bbps_search_results_count", "intval");

    add_settings_section(
        "bbps-private-replies",
        __("Private Replies", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_private_section",
        "bbpress-support-toolkit"
    );

    add_settings_field(
        "bbps_enable_private_replies",
        __("Enable Private Replies", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_private_replies",
        "bbpress-support-toolkit",
        "bbps-private-replies"
    );
    register_setting("bbpress-support-toolkit", "bbps_enable_private_replies", "intval");

    // Additional Features Section
    add_settings_section(
        "bbps-additional-features",
        __("Additional Features", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_additional_section",
        "bbpress-support-toolkit"
    );

    add_settings_field(
        "bbps_disable_user_page",
        __("Disable User Pages", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_disable_user_page",
        "bbpress-support-toolkit",
        "bbps-additional-features"
    );
    register_setting("bbpress-support-toolkit", "bbps_disable_user_page", "intval");

    add_settings_field(
        "bbps_remove_avatars",
        __("Remove User Avatars", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_remove_avatars",
        "bbpress-support-toolkit",
        "bbps-additional-features"
    );
    register_setting("bbpress-support-toolkit", "bbps_remove_avatars", "intval");

    add_settings_field(
        "bbps_redirect_single_replies",
        __("Redirect Single Replies to Topics", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_redirect_single_replies",
        "bbpress-support-toolkit",
        "bbps-additional-features"
    );
    register_setting("bbpress-support-toolkit", "bbps_redirect_single_replies", "intval");

    add_settings_field(
        "bbps_custom_notifications",
        __("Enable Custom Notifications", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_custom_notifications",
        "bbpress-support-toolkit",
        "bbps-additional-features"
    );
    register_setting("bbpress-support-toolkit", "bbps_custom_notifications", "intval");

    // Custom Notification Settings (only show when custom notifications are enabled)
    if (get_option('bbps_custom_notifications')) {
        add_settings_section(
            "bbps-notification-settings",
            __("Notification Settings", "bbpress-support-toolkit"),
            "bbps_admin_setting_callback_notification_settings_section",
            "bbpress-support-toolkit"
        );

        add_settings_field(
            "bbps_topic_notice_title",
            __("Topic Notification Subject", "bbpress-support-toolkit"),
            "bbps_admin_setting_callback_topic_notice_title",
            "bbpress-support-toolkit",
            "bbps-notification-settings"
        );
        register_setting("bbpress-support-toolkit", "bbps_topic_notice_title", "sanitize_text_field");

        add_settings_field(
            "bbps_topic_notice_body",
            __("Topic Notification Body", "bbpress-support-toolkit"),
            "bbps_admin_setting_callback_topic_notice_body",
            "bbpress-support-toolkit",
            "bbps-notification-settings"
        );
        register_setting("bbpress-support-toolkit", "bbps_topic_notice_body", "sanitize_textarea_field");

        add_settings_field(
            "bbps_reply_notice_title",
            __("Reply Notification Subject", "bbpress-support-toolkit"),
            "bbps_admin_setting_callback_reply_notice_title",
            "bbpress-support-toolkit",
            "bbps-notification-settings"
        );
        register_setting("bbpress-support-toolkit", "bbps_reply_notice_title", "sanitize_text_field");

        add_settings_field(
            "bbps_reply_notice_body",
            __("Reply Notification Body", "bbpress-support-toolkit"),
            "bbps_admin_setting_callback_reply_notice_body",
            "bbpress-support-toolkit",
            "bbps-notification-settings"
        );
        register_setting("bbpress-support-toolkit", "bbps_reply_notice_body", "sanitize_textarea_field");
    }
    register_setting("bbpress-support-toolkit", "bbps_enable_private_replies", "intval");

    add_settings_field(
        "bbps_private_replies_capability",
        __("Private Replies Capability", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_private_capability",
        "bbpress-support-toolkit",
        "bbps-private-replies"
    );
    register_setting("bbpress-support-toolkit", "bbps_private_replies_capability", "sanitize_text_field");

    add_settings_section(
        "bbps-seo-settings",
        __("SEO Settings", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_seo_section",
        "bbpress-support-toolkit"
    );

    add_settings_field(
        "bbps_enable_seo_optimization",
        __("Enable SEO Optimization", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_seo_optimization",
        "bbpress-support-toolkit",
        "bbps-seo-settings"
    );
    register_setting("bbpress-support-toolkit", "bbps_enable_seo_optimization", "intval");

    add_settings_field(
        "bbps_enable_meta_descriptions",
        __("Auto Generate Meta Descriptions", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_meta_descriptions",
        "bbpress-support-toolkit",
        "bbps-seo-settings"
    );
    register_setting("bbpress-support-toolkit", "bbps_enable_meta_descriptions", "intval");

    add_settings_field(
        "bbps_meta_description_length",
        __("Meta Description Length", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_meta_description_length",
        "bbpress-support-toolkit",
        "bbps-seo-settings"
    );
    register_setting("bbpress-support-toolkit", "bbps_meta_description_length", "intval");

    add_settings_field(
        "bbps_enable_open_graph",
        __("Enable Open Graph Tags", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_open_graph",
        "bbpress-support-toolkit",
        "bbps-seo-settings"
    );
    register_setting("bbpress-support-toolkit", "bbps_enable_open_graph", "intval");

    add_settings_field(
        "bbps_enable_twitter_cards",
        __("Enable Twitter Cards", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_twitter_cards",
        "bbpress-support-toolkit",
        "bbps-seo-settings"
    );
    register_setting("bbpress-support-toolkit", "bbps_enable_twitter_cards", "intval");

    add_settings_field(
        "bbps_enable_schema_markup",
        __("Enable Schema Markup", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_schema_markup",
        "bbpress-support-toolkit",
        "bbps-seo-settings"
    );
    register_setting("bbpress-support-toolkit", "bbps_enable_schema_markup", "intval");

    add_settings_field(
        "bbps_enable_canonical_urls",
        __("Enable Canonical URLs", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_canonical_urls",
        "bbpress-support-toolkit",
        "bbps-seo-settings"
    );
    register_setting("bbpress-support-toolkit", "bbps_enable_canonical_urls", "intval");

    add_settings_field(
        "bbps_forum_title_format",
        __("Forum Title Format", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_forum_title_format",
        "bbpress-support-toolkit",
        "bbps-seo-settings"
    );
    register_setting("bbpress-support-toolkit", "bbps_forum_title_format", "sanitize_text_field");

    add_settings_section(
        "bbps-forum-enhancements",
        __("Forum Enhancements", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_enhancement_section",
        "bbpress-support-toolkit"
    );

    add_settings_field(
        "bbps_enable_email_fix",
        __("Enable Email From Fix", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_email_fix",
        "bbpress-support-toolkit",
        "bbps-forum-enhancements"
    );
    register_setting("bbpress-support-toolkit", "bbps_enable_email_fix", "intval");

    add_settings_field(
        "bbps_custom_email_from",
        __("Custom Email From Address", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_custom_email_from",
        "bbpress-support-toolkit",
        "bbps-forum-enhancements"
    );
    register_setting("bbpress-support-toolkit", "bbps_custom_email_from", "sanitize_email");

    add_settings_field(
        "bbps_enable_title_length_fix",
        __("Enable Title Length Fix", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_title_length_fix",
        "bbpress-support-toolkit",
        "bbps-forum-enhancements"
    );
    register_setting("bbpress-support-toolkit", "bbps_enable_title_length_fix", "intval");

    add_settings_field(
        "bbps_max_title_length",
        __("Maximum Title Length", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_max_title_length",
        "bbpress-support-toolkit",
        "bbps-forum-enhancements"
    );
    register_setting("bbpress-support-toolkit", "bbps_max_title_length", "intval");

    add_settings_field(
        "bbps_remove_topic_tags",
        __("Remove Topic Tags Input", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_remove_topic_tags",
        "bbpress-support-toolkit",
        "bbps-forum-enhancements"
    );
    register_setting("bbpress-support-toolkit", "bbps_remove_topic_tags", "intval");

    add_settings_field(
        "bbps_enable_default_forum",
        __("Enable Default Forum Selection", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_enable_default_forum",
        "bbpress-support-toolkit",
        "bbps-forum-enhancements"
    );
    register_setting("bbpress-support-toolkit", "bbps_enable_default_forum", "intval");

    add_settings_field(
        "bbps_default_forum_id",
        __("Default Forum ID", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_default_forum_id",
        "bbpress-support-toolkit",
        "bbps-forum-enhancements"
    );
    register_setting("bbpress-support-toolkit", "bbps_default_forum_id", "intval");

    // Advanced Features Section
    add_settings_section(
        "bbps-advanced-features",
        __("Advanced Features", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_advanced_section",
        "bbpress-support-toolkit"
    );

    // Admin Notes
    add_settings_field(
        "bbps_enable_admin_notes",
        __("Enable Admin Notes", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_admin_notes",
        "bbpress-support-toolkit",
        "bbps-advanced-features"
    );
    register_setting("bbpress-support-toolkit", "bbps_enable_admin_notes", "intval");

    // Live Preview
    add_settings_field(
        "bbps_enable_live_preview",
        __("Enable Live Preview", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_live_preview",
        "bbpress-support-toolkit",
        "bbps-advanced-features"
    );
    register_setting("bbpress-support-toolkit", "bbps_enable_live_preview", "intval");

    // Mark as Read
    add_settings_field(
        "bbps_enable_mark_as_read",
        __("Enable Mark as Read", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_mark_as_read",
        "bbpress-support-toolkit",
        "bbps-advanced-features"
    );
    register_setting("bbpress-support-toolkit", "bbps_enable_mark_as_read", "intval");

    // Canned Replies
    add_settings_field(
        "bbps_enable_canned_replies",
        __("Enable Canned Replies", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_canned_replies",
        "bbpress-support-toolkit",
        "bbps-advanced-features"
    );
    register_setting("bbpress-support-toolkit", "bbps_enable_canned_replies", "intval");

    // Report Content
    add_settings_field(
        "bbps_enable_report_content",
        __("Enable Report Content", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_report_content",
        "bbpress-support-toolkit",
        "bbps-advanced-features"
    );
    register_setting("bbpress-support-toolkit", "bbps_enable_report_content", "intval");

    // Topic Lock
    add_settings_field(
        "bbps_enable_topic_lock",
        __("Enable Topic Lock", "bbpress-support-toolkit"),
        "bbps_admin_setting_callback_topic_lock",
        "bbpress-support-toolkit",
        "bbps-advanced-features"
    );
    register_setting("bbpress-support-toolkit", "bbps_enable_topic_lock", "intval");
}

function bbps_admin_page()
{
    // 标签页映射：将旧标签页映射到新的分组
    $tab_mapping = array(
        'user-ranking' => 'basic-settings',
        'support-forum' => 'basic-settings',
        'topic-status' => 'topic-management',
        'topic-labels' => 'topic-management',
        'private-replies' => 'forum-features',
        'forum-enhancements' => 'forum-features',
        'search-settings' => 'search-seo',
        'seo-settings' => 'search-seo',
        'additional-features' => 'advanced-features',
        'advanced-features' => 'advanced-features'
    );
    
    $requested_tab = isset($_GET['tab']) ? $_GET['tab'] : 'basic-settings';
     $active_tab = isset($tab_mapping[$requested_tab]) ? $tab_mapping[$requested_tab] : 'basic-settings';
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?>
            <span style="font-size: 13px; padding-left: 10px;"><?php printf(esc_html__('Version: %s', 'bbpress-support-toolkit'), esc_html(BBPS_VERSION)); ?></span>
            <a href="https://sharecms.com/document/bbpress-support-toolkit" target="_blank" class="button button-secondary" style="margin-left: 10px;"><?php esc_html_e('Documentation', 'bbpress-support-toolkit'); ?></a>
            <a href="https://meta.cyberforums.com/tag/bbpress" target="_blank" class="button button-secondary"><?php esc_html_e('Support', 'bbpress-support-toolkit'); ?></a>
        </h1>
        
        <?php if (bbps_is_default_forum_enabled()) : ?>
            <div class="notice notice-info">
                <p>
                    <strong><?php esc_html_e('Default Forum Status:', 'bbpress-support-toolkit'); ?></strong>
                    <?php 
                    $default_forum_id = get_option('bbps_default_forum_id');
                    if (!empty($default_forum_id)) {
                        $forum_title = get_the_title($default_forum_id);
                        printf(
                            esc_html__('Default forum is set to "%s" (ID: %d)', 'bbpress-support-toolkit'),
                            esc_html($forum_title),
                            intval($default_forum_id)
                        );
                    } else {
                        esc_html_e('No default forum selected', 'bbpress-support-toolkit');
                    }
                    ?>
                </p>
            </div>
        <?php endif; ?>
        <div class="card">
        <div class="bbps-settings-tabs">
            <button type="button" class="bbps-tab <?php echo $active_tab == 'basic-settings' ? 'active' : ''; ?>" data-tab="basic-settings">
                <?php esc_html_e('Basic Settings', 'bbpress-support-toolkit'); ?>
            </button>
            <button type="button" class="bbps-tab <?php echo $active_tab == 'topic-management' ? 'active' : ''; ?>" data-tab="topic-management">
                <?php esc_html_e('Topic Management', 'bbpress-support-toolkit'); ?>
            </button>
            <button type="button" class="bbps-tab <?php echo $active_tab == 'forum-features' ? 'active' : ''; ?>" data-tab="forum-features">
                <?php esc_html_e('Forum Features', 'bbpress-support-toolkit'); ?>
            </button>
            <button type="button" class="bbps-tab <?php echo $active_tab == 'search-seo' ? 'active' : ''; ?>" data-tab="search-seo">
                <?php esc_html_e('Search & SEO', 'bbpress-support-toolkit'); ?>
            </button>
            <button type="button" class="bbps-tab <?php echo $active_tab == 'advanced-features' ? 'active' : ''; ?>" data-tab="advanced-features">
                <?php esc_html_e('Advanced Features', 'bbpress-support-toolkit'); ?>
            </button>
        </div>

        <div class="bbps-settings-content">
            <form action="options.php" method="post">
                <?php settings_fields("bbpress-support-toolkit"); ?>
 
                <!-- 基础设置：用户排名 + 支持论坛 -->
                <div id="bbps-basic-settings-section" class="bbps-section" data-section="basic-settings" style="<?php echo $active_tab === 'basic-settings' ? '' : 'display: none;'; ?>">
                        <?php do_settings_sections_for_tab("bbpress-support-toolkit", "bbps-user-ranking"); ?>
                        <?php do_settings_sections_for_tab("bbpress-support-toolkit", "bbps-support-forum"); ?>
                </div>

                <!-- 主题管理：主题状态 + 主题标签 + 私密回复 -->
                <div id="bbps-topic-management-section" class="bbps-section" data-section="topic-management" style="<?php echo $active_tab === 'topic-management' ? '' : 'display: none;'; ?>">
                        <?php do_settings_sections_for_tab("bbpress-support-toolkit", "bbps-topic-status"); ?>
                        <?php do_settings_sections_for_tab("bbpress-support-toolkit", "bbps-topic-labels"); ?>
                        <?php do_settings_sections_for_tab("bbpress-support-toolkit", "bbps-private-replies"); ?>
                </div>
                
                <!-- 功能增强：论坛增强 + 附加功能 -->
                <div id="bbps-forum-features-section" class="bbps-section" data-section="forum-features" style="<?php echo $active_tab === 'forum-features' ? '' : 'display: none;'; ?>">
                        <?php do_settings_sections_for_tab("bbpress-support-toolkit", "bbps-forum-enhancements"); ?>
                        <?php do_settings_sections_for_tab("bbpress-support-toolkit", "bbps-additional-features"); ?>
                        <?php if (get_option('bbps_custom_notifications')) : ?>
                            <?php do_settings_sections_for_tab("bbpress-support-toolkit", "bbps-notification-settings"); ?>
                        <?php endif; ?>
                </div>
                                
                <!-- 搜索与SEO：搜索设置 + SEO设置 -->
                <div id="bbps-search-seo-section" class="bbps-section" data-section="search-seo" style="<?php echo $active_tab === 'search-seo' ? '' : 'display: none;'; ?>">
                        <?php do_settings_sections_for_tab("bbpress-support-toolkit", "bbps-search-settings"); ?>
                        <?php do_settings_sections_for_tab("bbpress-support-toolkit", "bbps-seo-settings"); ?>
                </div>

                <div id="bbps-advanced-features-section" class="bbps-section" data-section="advanced-features" style="<?php echo $active_tab === 'advanced-features' ? '' : 'display: none;'; ?>">
                        <?php do_settings_sections_for_tab("bbpress-support-toolkit", "bbps-advanced-features"); ?>
                </div>

                <div id="bbps-save-status" class="notice" style="display: none; margin-top: 10px;"></div>
                <p class="submit">
                    <button type="button" id="bbps-save-settings" class="button button-primary"><?php esc_html_e('Save Settings', 'bbpress-support-toolkit'); ?></button>
                    <span class="spinner" id="bbps-spinner" style="float: none; margin-left: 10px;"></span>
                </p>
            </form>
        </div>
    </div>
   </div>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        var currentTab = '<?php echo esc_js($active_tab); ?>';
        
        // 如果当前标签页不在新的标签页列表中，设置为默认标签页
        var validTabs = ['basic-settings', 'topic-management', 'forum-features', 'search-seo', 'advanced-features'];
        if (validTabs.indexOf(currentTab) === -1) {
            currentTab = 'basic-settings';
        }
        
        $('.bbps-tab').on('click', function() {
            $('.bbps-tab').removeClass('active');
            $(this).addClass('active');
            var tab = $(this).data('tab');
            currentTab = tab;
            $('.bbps-section').hide();
            $('.bbps-section[data-section="' + tab + '"]').show();
            
            // Update URL without page reload
            var url = new URL(window.location);
            url.searchParams.set('tab', tab);
            window.history.pushState({}, '', url);
        });
        
        // Ajax保存功能
        $('#bbps-save-settings').on('click', function() {
            var $button = $(this);
            var $spinner = $('#bbps-spinner');
            var $status = $('#bbps-save-status');
            var formData = $('form').serialize();
            
            $button.prop('disabled', true);
            $spinner.addClass('is-active');
            $status.hide();
            
            $.ajax({
                url: bbps_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'bbps_save_settings',
                    form_data: formData,
                    _ajax_nonce: bbps_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $status
                            .removeClass('notice-error')
                            .addClass('notice-success')
                            .html('<p>' + response.data.message + '</p>')
                            .show()
                            .delay(3000)
                            .fadeOut();
                    } else {
                        $status
                            .removeClass('notice-success')
                            .addClass('notice-error')
                            .html('<p>' + (response.data || bbps_ajax.error_text) + '</p>')
                            .show();
                    }
                },
                error: function() {
                    $status
                        .removeClass('notice-success')
                        .addClass('notice-error')
                        .html('<p>' + bbps_ajax.error_text + '</p>')
                        .show();
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        });
        
        // 页面加载时确保正确的标签页显示
        if (currentTab) {
            $('.bbps-section').hide();
            $('.bbps-section[data-section="' + currentTab + '"]').show();
            $('.bbps-tab[data-tab="' + currentTab + '"]').addClass('active');
        }
    });
    </script>
    <?php
}

function do_settings_sections_for_tab($page, $section_id) {
    global $wp_settings_sections, $wp_settings_fields;

    if (!isset($wp_settings_sections[$page])) {
        return;
    }

    foreach ($wp_settings_sections[$page] as $section) {
        if ($section['id'] == $section_id) {
            if ($section['title']) {
                echo "<h2>{$section['title']}</h2>\n";
            }

            if ($section['callback']) {
                call_user_func($section['callback'], $section);
            }

            if (!isset($wp_settings_fields) || !isset($wp_settings_fields[$page]) || !isset($wp_settings_fields[$page][$section['id']])) {
                continue;
            }

            echo '<table class="form-table" role="presentation">';
            do_settings_fields($page, $section['id']);
            echo '</table>';
            break;
        }
    }
}

function bbps_admin_setting_callback_email_fix()
{
    ?>
    <input id="bbps_enable_email_fix" name="bbps_enable_email_fix" <?php checked(
        get_option('bbps_enable_email_fix'),
        1
    ); ?> type="checkbox" value="1" />
    <label for="bbps_enable_email_fix"><?php esc_html_e(
        "Override default WordPress email sender address for forum notifications",
        "bbpress-support-toolkit"
    ); ?></label>
    <?php
}

function bbps_admin_setting_callback_custom_email_from()
{
    $default_domain = 'example.com';
    if (isset($_SERVER['HTTP_HOST'])) {
        $default_domain = str_replace('www.', '', $_SERVER['HTTP_HOST']);
    }
    $email = get_option('bbps_custom_email_from', 'no-reply@' . $default_domain);
    ?>
    <input id="bbps_custom_email_from" name="bbps_custom_email_from" type="email" class="regular-text" value="<?php echo esc_attr($email); ?>" />
    <p class="description"><?php esc_html_e(
        "Email address to use as sender for forum notification emails",
        "bbpress-support-toolkit"
    ); ?></p>
    <?php
}

function bbps_admin_setting_callback_title_length_fix()
{
    ?>
    <input id="bbps_enable_title_length_fix" name="bbps_enable_title_length_fix" <?php checked(
        get_option('bbps_enable_title_length_fix'),
        1
    ); ?> type="checkbox" value="1" />
    <label for="bbps_enable_title_length_fix"><?php esc_html_e(
        "Allow longer topic titles (increases the default character limit)",
        "bbpress-support-toolkit"
    ); ?></label>
    <?php
}

function bbps_admin_setting_callback_max_title_length()
{
    $length = get_option('bbps_max_title_length', 150);
    ?>
    <input id="bbps_max_title_length" name="bbps_max_title_length" type="number" min="50" max="500" value="<?php echo esc_attr($length); ?>" />
    <label for="bbps_max_title_length"><?php esc_html_e(
        "Maximum characters allowed in topic titles",
        "bbpress-support-toolkit"
    ); ?></label>
    <?php
}

function bbps_admin_setting_callback_remove_topic_tags()
{
    ?>
    <input id="bbps_remove_topic_tags" name="bbps_remove_topic_tags" <?php checked(
        get_option('bbps_remove_topic_tags'),
        1
    ); ?> type="checkbox" value="1" />
    <label for="bbps_remove_topic_tags"><?php esc_html_e(
        "Remove the topic tags input field from the new topic form",
        "bbpress-support-toolkit"
    ); ?></label>
    <?php
}

function bbps_admin_setting_callback_enable_default_forum()
{
    ?>
    <input id="bbps_enable_default_forum" name="bbps_enable_default_forum" <?php checked(
        get_option('bbps_enable_default_forum'),
        1
    ); ?> type="checkbox" value="1" />
    <label for="bbps_enable_default_forum"><?php esc_html_e(
        "Automatically pre-select a default forum when creating new topics",
        "bbpress-support-toolkit"
    ); ?></label>
    <?php
}

function bbps_admin_setting_callback_default_forum_id()
{
    $forum_id = get_option('bbps_default_forum_id', '');
    $forums = get_posts([
        'post_type' => 'forum',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'menu_order title',
        'order' => 'ASC'
    ]);
    ?>
    <select id="bbps_default_forum_id" name="bbps_default_forum_id">
        <option value=""><?php esc_html_e('Select a forum', 'bbpress-support-toolkit'); ?></option>
        <?php foreach ($forums as $forum) : 
            if (!bbp_is_forum_category($forum->ID)) :
        ?>
            <option value="<?php echo esc_attr($forum->ID); ?>" <?php selected($forum_id, $forum->ID); ?>>
                <?php echo esc_html($forum->post_title); ?>
                <?php 
                if ($forum->post_parent) {
                    $parent_title = get_the_title($forum->post_parent);
                    echo ' (' . esc_html($parent_title) . ')';
                }
                ?>
            </option>
        <?php 
            endif;
        endforeach; ?>
    </select>
    <p class="description"><?php esc_html_e(
        "Forum to be pre-selected when users create new topics (categories are excluded)",
        "bbpress-support-toolkit"
    ); ?></p>
    <?php
}

function bbps_validate_search_rewrite($input)
{
    $old_value = get_option("bbps_enable_search_url_rewrite");
    $new_value = intval($input);
    
    if ($old_value != $new_value) {
        if ($new_value) {
            add_rewrite_rule('^search/([^/]+)/?$', 'index.php?s=$matches[1]', 'top');
        }
        flush_rewrite_rules(false);
    }
    
    return $new_value;
}

function bbps_validate_checkbox_group($input)
{
    $newoptions = [];
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $newoptions[sanitize_key($key)] = sanitize_text_field($value);
        }
    }
    return $newoptions;
}

function bbps_validate_options($input)
{
    $options = get_option("bbps_reply_count", []);

    if (!is_array($options)) {
        $options = [];
    }

    // 如果选项为空，设置默认参数
    if (empty($options)) {
        $options = bbps_get_default_user_ranking_options();
    }

    if (is_array($input)) {
        $i = 1;
        foreach ($input as $array) {
            if (is_array($array)) {
                if (!isset($options[$i]) || !is_array($options[$i])) {
                    $options[$i] = [];
                }
                foreach ($array as $key => $value) {
                    $options[$i][sanitize_key($key)] = sanitize_text_field(
                        $value
                    );
                }
            }
            $i++;
        }
    }
    return $options;
}

/**
 * 初始化用户等级默认参数
 */
function bbps_init_default_user_ranking()
{
    $existing_options = get_option('bbps_reply_count', []);
    
    // 如果选项不存在或为空，设置默认值
    if (empty($existing_options)) {
        $default_options = bbps_get_default_user_ranking_options();
        update_option('bbps_reply_count', $default_options);
    }
}

/**
 * 获取用户等级的默认参数
 */
function bbps_get_default_user_ranking_options()
{
    return [
        1 => [
            'title' => __('新手', 'bbpress-support-toolkit'),
            'start' => '1',
            'end' => '10'
        ],
        2 => [
            'title' => __('活跃用户', 'bbpress-support-toolkit'),
            'start' => '11',
            'end' => '50'
        ],
        3 => [
            'title' => __('资深用户', 'bbpress-support-toolkit'),
            'start' => '51',
            'end' => '100'
        ],
        4 => [
            'title' => __('专家用户', 'bbpress-support-toolkit'),
            'start' => '101',
            'end' => '200'
        ],
        5 => [
            'title' => __('论坛大师', 'bbpress-support-toolkit'),
            'start' => '201',
            'end' => '999999'
        ]
    ];
}

function bbps_admin_setting_callback_ranking_section()
{
    ?>
    <p><?php esc_html_e(
        "User ranking allows you to differentiate and reward your forum users with Custom Titles based on the number of topics and replies they have contributed to.",
        "bbpress-support-toolkit"
    ); ?></p>
    <?php
}

function bbps_admin_setting_callback_seo_optimization()
{
    ?>
    <input id="bbps_enable_seo_optimization" name="bbps_enable_seo_optimization" <?php checked(
        get_option('bbps_enable_seo_optimization'),
        1
    ); ?> type="checkbox" value="1" />
    <label for="bbps_enable_seo_optimization"><?php esc_html_e(
        "Enable comprehensive SEO optimization for forum content",
        "bbpress-support-toolkit"
    ); ?></label>
    <?php
}

function bbps_admin_setting_callback_meta_descriptions()
{
    ?>
    <input id="bbps_enable_meta_descriptions" name="bbps_enable_meta_descriptions" <?php checked(
        get_option('bbps_enable_meta_descriptions'),
        1
    ); ?> type="checkbox" value="1" />
    <label for="bbps_enable_meta_descriptions"><?php esc_html_e(
        "Automatically generate meta descriptions from topic and reply content",
        "bbpress-support-toolkit"
    ); ?></label>
    <?php
}

function bbps_admin_setting_callback_meta_description_length()
{
    $length = get_option('bbps_meta_description_length', 160);
    ?>
    <input id="bbps_meta_description_length" name="bbps_meta_description_length" type="number" min="100" max="300" value="<?php echo esc_attr($length); ?>" />
    <label for="bbps_meta_description_length"><?php esc_html_e(
        "Maximum length for auto-generated meta descriptions (characters)",
        "bbpress-support-toolkit"
    ); ?></label>
    <?php
}

function bbps_admin_setting_callback_open_graph()
{
    ?>
    <input id="bbps_enable_open_graph" name="bbps_enable_open_graph" <?php checked(
        get_option('bbps_enable_open_graph'),
        1
    ); ?> type="checkbox" value="1" />
    <label for="bbps_enable_open_graph"><?php esc_html_e(
        "Add Open Graph meta tags for better social media sharing",
        "bbpress-support-toolkit"
    ); ?></label>
    <?php
}

function bbps_admin_setting_callback_twitter_cards()
{
    ?>
    <input id="bbps_enable_twitter_cards" name="bbps_enable_twitter_cards" <?php checked(
        get_option('bbps_enable_twitter_cards'),
        1
    ); ?> type="checkbox" value="1" />
    <label for="bbps_enable_twitter_cards"><?php esc_html_e(
        "Add Twitter Card meta tags for enhanced Twitter sharing",
        "bbpress-support-toolkit"
    ); ?></label>
    <?php
}

function bbps_admin_setting_callback_schema_markup()
{
    ?>
    <input id="bbps_enable_schema_markup" name="bbps_enable_schema_markup" <?php checked(
        get_option('bbps_enable_schema_markup'),
        1
    ); ?> type="checkbox" value="1" />
    <label for="bbps_enable_schema_markup"><?php esc_html_e(
        "Add structured data markup for better search engine understanding",
        "bbpress-support-toolkit"
    ); ?></label>
    <?php
}

function bbps_admin_setting_callback_canonical_urls()
{
    ?>
    <input id="bbps_enable_canonical_urls" name="bbps_enable_canonical_urls" <?php checked(
        get_option('bbps_enable_canonical_urls'),
        1
    ); ?> type="checkbox" value="1" />
    <label for="bbps_enable_canonical_urls"><?php esc_html_e(
        "Add canonical URLs to prevent duplicate content issues",
        "bbpress-support-toolkit"
    ); ?></label>
    <?php
}

function bbps_admin_setting_callback_forum_title_format()
{
    $format = get_option('bbps_forum_title_format', '%topic_title% - %forum_name% | %site_name%');
    ?>
    <input id="bbps_forum_title_format" name="bbps_forum_title_format" type="text" class="regular-text" value="<?php echo esc_attr($format); ?>" />
    <p class="description">
        <?php esc_html_e('Available placeholders:', 'bbpress-support-toolkit'); ?><br>
        <code>%topic_title%</code> - <?php esc_html_e('Topic title', 'bbpress-support-toolkit'); ?><br>
        <code>%forum_name%</code> - <?php esc_html_e('Forum name', 'bbpress-support-toolkit'); ?><br>
        <code>%site_name%</code> - <?php esc_html_e('Site name', 'bbpress-support-toolkit'); ?><br>
        <code>%author_name%</code> - <?php esc_html_e('Topic author name', 'bbpress-support-toolkit'); ?>
    </p>
    <?php
}

function bbps_admin_setting_callback_status_section()
{
    ?>
    <p><?php esc_html_e(
        "Enable and configure the settings for topic statuses these will be displayed on each topic",
        "bbpress-support-toolkit"
    ); ?></p>
    <?php
}

function bbps_admin_setting_callback_support_forum_section()
{
    ?>
    <p><?php esc_html_e(
        "Enable and configure the settings for support forums, these options will be displayed on each topic within your support forums",
        "bbpress-support-toolkit"
    ); ?></p>
    <?php
}

function bbps_admin_setting_callback_label_section()
{
    ?>
    <p><?php esc_html_e(
        "Configure topic labels that will be displayed on topics based on different conditions.",
        "bbpress-support-toolkit"
    ); ?></p>
    <?php
}

function bbps_admin_setting_callback_search_section()
{
    ?>
    <p><?php esc_html_e(
        "Configure search functionality and user interface enhancements for your forum. Search will prioritize forum topics over blog posts.",
        "bbpress-support-toolkit"
    ); ?></p>
    <?php
}

function bbps_admin_setting_callback_private_section()
{
    ?>
    <p><?php esc_html_e(
        "Allow users to mark their replies as private, visible only to the topic author, moderators, and administrators.",
        "bbpress-support-toolkit"
    ); ?></p>
    <?php
}

function bbps_admin_setting_callback_seo_section()
{
    ?>
    <p><?php esc_html_e(
        "Configure SEO settings to make your forum more search engine friendly. These settings will help improve your forum's visibility in search results.",
        "bbpress-support-toolkit"
    ); ?></p>
    <?php
}

function bbps_admin_setting_callback_enhancement_section()
{
    ?>
    <p><?php esc_html_e(
        "Additional enhancements and fixes for bbPress functionality to improve user experience and forum management.",
        "bbpress-support-toolkit"
    ); ?></p>
    <?php
}

function bbps_admin_setting_callback_reply_count(array $args)
{
    $i = isset($args[0]) ? absint($args[0]) : 1;
    $options = get_option("bbps_reply_count", []);
    ?>
    <?php esc_html_e("Rank Title", "bbpress-support-toolkit"); ?>
    <input name="bbps_reply_count[<?php echo esc_attr(
        $i
    ); ?>][title]" type="text" id="bbps_reply_count_title" value="<?php echo isset($options[$i]["title"]) ? esc_attr($options[$i]["title"]) : ""; ?>" />
    <?php esc_html_e(
        "is granted when a user has at least",
        "bbpress-support-toolkit"
    ); ?>
    <input name="bbps_reply_count[<?php echo esc_attr(
        $i
    ); ?>][start]" type="text" id="bbps_reply_count_start" value="<?php echo isset($options[$i]["start"]) ? esc_attr($options[$i]["start"]) : ""; ?>" class="small-text" />
    <?php esc_html_e("posts but not more than", "bbpress-support-toolkit"); ?>
    <input name="bbps_reply_count[<?php echo esc_attr(
        $i
    ); ?>][end]" type="text" id="bbps_reply_count_end" value="<?php echo isset($options[$i]["end"]) ? esc_attr($options[$i]["end"]) : ""; ?>" class="small-text" />
    <?php esc_html_e("posts", "bbpress-support-toolkit"); ?>
    <?php
}

function bbps_admin_setting_callback_post_count()
{
    ?>
    <input id="bbps_enable_post_count" name="bbps_enable_post_count" type="checkbox" <?php checked(
        bbps_is_post_count_enabled(),
        1
    ); ?> value="1" />
    <label for="bbps_enable_post_count"><?php esc_html_e(
        "Show the users post count below their gravatar?",
        "bbpress-support-toolkit"
    ); ?></label>
    <?php
}

function bbps_admin_setting_callback_user_rank()
{
    ?>
    <input id="bbps_enable_user_rank" name="bbps_enable_user_rank" type="checkbox" <?php checked(
        bbps_is_user_rank_enabled(),
        1
    ); ?> value="1" />
    <label for="bbps_enable_user_rank"><?php esc_html_e(
        "Display the users rank title below their gravatar?",
        "bbpress-support-toolkit"
    ); ?></label>
    <?php
}

function bbps_admin_setting_callback_default_status()
{
    $option = get_option("bbps_default_status"); ?>
    <select name="bbps_default_status" id="bbps_default_status">
        <option value="1" <?php selected($option, 1); ?>><?php esc_html_e(
    "not resolved",
    "bbpress-support-toolkit"
); ?></option>
        <option value="2" <?php selected($option, 2); ?>><?php esc_html_e(
    "resolved",
    "bbpress-support-toolkit"
); ?></option>
        <option value="3" <?php selected($option, 3); ?>><?php esc_html_e(
    "not a support question",
    "bbpress-support-toolkit"
); ?></option>
    </select>
    <label for="bbps_default_status"><?php esc_html_e(
        "This is the default status that will get displayed on all topics",
        "bbpress-support-toolkit"
    ); ?></label>
    <?php
}

function bbps_admin_setting_callback_displayed_status_res()
{
    ?>
    <input id="bbps_used_status" name="bbps_used_status[res]" <?php checked(
        bbps_is_resolved_enabled(),
        1
    ); ?> type="checkbox" value="1" />
    <label for="bbps_used_status"><?php esc_html_e(
        "Resolved",
        "bbpress-support-toolkit"
    ); ?></label>
    <?php
}

function bbps_admin_setting_callback_displayed_status_notres()
{
    ?>
    <input id="bbps_used_status_notres" name="bbps_used_status[notres]" <?php checked(
        bbps_is_not_resolved_enabled(),
        1
    ); ?> type="checkbox" value="1" />
    <label for="bbps_used_status_notres"><?php esc_html_e(
        "Not Resolved",
        "bbpress-support-toolkit"
    ); ?></label>
    <?php
}

function bbps_admin_setting_callback_displayed_status_notsup()
{
    ?>
    <input id="bbps_used_status_notsup" name="bbps_used_status[notsup]" <?php checked(
        bbps_is_not_support_enabled(),
        1
    ); ?> type="checkbox" value="1" />
    <label for="bbps_used_status_notsup"><?php esc_html_e(
        "Not a support question",
        "bbpress-support-toolkit"
    ); ?></label>
    <?php
}

function bbps_admin_setting_callback_permission_admin()
{
    ?>
    <input id="bbps_status_permissions_admin" name="bbps_status_permissions[admin]" <?php checked(
        bbps_is_admin_enabled(),
        1
    ); ?> type="checkbox" value="1" />
    <label for="bbps_status_permissions_admin"><?php esc_html_e(
        "Allow the admin to update the topic status (recommended).",
        "bbpress-support-toolkit"
    ); ?></label>
    <?php
}

function bbps_admin_setting_callback_permission_user()
{
    ?>
    <input id="bbps_status_permissions_user" name="bbps_status_permissions[user]" <?php checked(
        bbps_is_user_enabled(),
        1
    ); ?> type="checkbox" value="1" />
    <label for="bbps_status_permissions_user"><?php esc_html_e(
        "Allow the person who created the topic to update the status.",
        "bbpress-support-toolkit"
    ); ?></label>
    <?php
}

function bbps_admin_setting_callback_permission_moderator()
{
    ?>
    <input id="bbps_status_permissions_mod" name="bbps_status_permissions[mod]" <?php checked(
        bbps_is_moderator_enabled(),
        1
    ); ?> type="checkbox" value="1" />
    <label for="bbps_status_permissions_mod"><?php esc_html_e(
        "Allow the forum moderators to update the post status.",
        "bbpress-support-toolkit"
    ); ?></label>
    <?php
}

function bbps_admin_setting_callback_move_topic()
{
    ?>
    <input id="bbps_enable_topic_move" name="bbps_enable_topic_move" <?php checked(
        bbps_is_topic_move_enabled(),
        1
    ); ?> type="checkbox" value="1" />
    <label for="bbps_enable_topic_move"><?php esc_html_e(
        "Allow the forum moderators and admin to move topics to other forums.",
        "bbpress-support-toolkit"
    ); ?></label>
    <?php
}

function bbps_admin_setting_callback_urgent()
{
    ?>
    <input id="bbps_status_permissions_urgent" name="bbps_status_permissions_urgent" <?php checked(
        bbps_is_topic_urgent_enabled(),
        1
    ); ?> type="checkbox" value="1" />
    <label for="bbps_status_permissions_urgent"><?php esc_html_e(
        "Allow the forum moderators and admin to mark a topic as Urgent, this will mark the topic title with [urgent].",
        "bbpress-support-toolkit"
    ); ?></label>
    <?php
}

function bbps_admin_setting_callback_claim_topic()
{
    ?>
    <input id="bbps_claim_topic" name="bbps_claim_topic" <?php checked(
        bbps_is_topic_claim_enabled(),
        1
    ); ?> type="checkbox" value="1" />
    <label for="bbps_claim_topic"><?php esc_html_e(
        "Allow the forum moderators and admin to claim a topic, this will mark the topic title with [claimed] but will only show to forum moderators and admin users",
        "bbpress-support-toolkit"
    ); ?></label>
    <?php
}

function bbps_admin_setting_callback_claim_topic_display()
{
    ?>
    <input id="bbps_claim_topic_display" name="bbps_claim_topic_display" <?php checked(
        bbps_is_topic_claim_display_enabled(),
        1
    ); ?> type="checkbox" value="1" />
    <label for="bbps_claim_topic_display"><?php esc_html_e(
        "By selecting this option if a topic is claimed the claimed persons username will be displayed next to the topic title instead of the words [claimed], leaving this unchecked will default to [claimed]",
        "bbpress-support-toolkit"
    ); ?></label>
    <?php
}

function bbps_admin_setting_callback_assign_topic()
{
    ?>
    <input id="bbps_topic_assign" name="bbps_topic_assign" <?php checked(
        bbps_is_topic_assign_enabled(),
        1
    ); ?> type="checkbox" value="1" />
    <label for="bbps_topic_assign"><?php esc_html_e(
        "Allow administrators and forum moderators to assign topics to other administrators and forum moderators",
        "bbpress-support-toolkit"
    ); ?></label>
    <?php
}

function bbps_admin_setting_callback_new_topic_label()
{
    ?>
    <input id="bbps_enable_new_topic_label" name="bbps_enable_new_topic_label" <?php checked(
        bbps_is_new_topic_label_enabled(),
        1
    ); ?> type="checkbox" value="1" />
    <label for="bbps_enable_new_topic_label"><?php esc_html_e(
        "Display a 'New' label on recent topics",
        "bbpress-support-toolkit"
    ); ?></label>
    <?php
}

function bbps_admin_setting_callback_new_topic_days()
{
    $days = get_option("bbps_new_topic_days", 30);
    ?>
    <input id="bbps_new_topic_days" name="bbps_new_topic_days" type="number" min="1" max="365" value="<?php echo esc_attr($days); ?>" />
    <label for="bbps_new_topic_days"><?php esc_html_e(
        "Number of days to consider a topic as 'new'",
        "bbpress-support-toolkit"
    ); ?></label>
    <?php
}

function bbps_admin_setting_callback_closed_topic_label()
{
    ?>
    <input id="bbps_enable_closed_topic_label" name="bbps_enable_closed_topic_label" <?php checked(
        bbps_is_closed_topic_label_enabled(),
        1
    ); ?> type="checkbox" value="1" />
    <label for="bbps_enable_closed_topic_label"><?php esc_html_e(
        "Display a 'Closed' label on closed topics",
        "bbpress-support-toolkit"
    ); ?></label>
    <?php
}

function bbps_admin_setting_callback_sticky_topic_label()
{
    ?>
    <input id="bbps_enable_sticky_topic_label" name="bbps_enable_sticky_topic_label" <?php checked(
        bbps_is_sticky_topic_label_enabled(),
        1
    ); ?> type="checkbox" value="1" />
    <label for="bbps_enable_sticky_topic_label"><?php esc_html_e(
        "Display a 'Sticky' label on sticky topics",
        "bbpress-support-toolkit"
    ); ?></label>
    <?php
}

function bbps_admin_setting_callback_search_integration()
{
    ?>
    <input id="bbps_enable_search_integration" name="bbps_enable_search_integration" <?php checked(
        bbps_is_search_integration_enabled(),
        1
    ); ?> type="checkbox" value="1" />
    <label for="bbps_enable_search_integration"><?php esc_html_e(
        "Include forum topics in WordPress search results",
        "bbpress-support-toolkit"
    ); ?></label>
    <?php
}

function bbps_admin_setting_callback_search_url_rewrite()
{
    ?>
    <input id="bbps_enable_search_url_rewrite" name="bbps_enable_search_url_rewrite" <?php checked(
        bbps_is_search_url_rewrite_enabled(),
        1
    ); ?> type="checkbox" value="1" />
    <label for="bbps_enable_search_url_rewrite"><?php esc_html_e(
        "Rewrite search URLs to use /search/ format",
        "bbpress-support-toolkit"
    ); ?></label>
    <?php
}

function bbps_admin_setting_callback_post_author_label()
{
    ?>
    <input id="bbps_enable_post_author_label" name="bbps_enable_post_author_label" <?php checked(
        bbps_is_post_author_label_enabled(),
        1
    ); ?> type="checkbox" value="1" />
    <label for="bbps_enable_post_author_label"><?php esc_html_e(
        "Display 'Original Poster' label for topic authors in replies",
        "bbpress-support-toolkit"
    ); ?></label>
    <?php
}

function bbps_admin_setting_callback_include_posts_search()
{
    ?>
    <input id="bbps_include_posts_in_search" name="bbps_include_posts_in_search" <?php checked(
        get_option('bbps_include_posts_in_search'),
        1
    ); ?> type="checkbox" value="1" />
    <label for="bbps_include_posts_in_search"><?php esc_html_e(
        "Also include blog posts in search results (forum topics will still be prioritized)",
        "bbpress-support-toolkit"
    ); ?></label>
    <?php
}

function bbps_admin_setting_callback_search_results_count()
{
    $count = get_option('bbps_search_results_count', 20);
    ?>
    <input id="bbps_search_results_count" name="bbps_search_results_count" type="number" min="5" max="100" value="<?php echo esc_attr($count); ?>" />
    <label for="bbps_search_results_count"><?php esc_html_e(
        "Number of search results to display per page",
        "bbpress-support-toolkit"
    ); ?></label>
    <?php
}

function bbps_admin_setting_callback_private_replies()
{
    ?>
    <input id="bbps_enable_private_replies" name="bbps_enable_private_replies" <?php checked(
        bbps_is_private_replies_enabled(),
        1
    ); ?> type="checkbox" value="1" />
    <label for="bbps_enable_private_replies"><?php esc_html_e(
        "Allow users to mark their replies as private",
        "bbpress-support-toolkit"
    ); ?></label>
    <p class="description"><?php esc_html_e(
        "Private replies are visible only to the topic author, reply author, and users with the specified capability.",
        "bbpress-support-toolkit"
    ); ?></p>
    <?php
}

function bbps_admin_setting_callback_private_capability()
{
    $capability = bbps_get_private_replies_capability();
    ?>
    <select id="bbps_private_replies_capability" name="bbps_private_replies_capability">
        <option value="moderate" <?php selected($capability, 'moderate'); ?>><?php esc_html_e('Moderate (Forum Moderators)', 'bbpress-support-toolkit'); ?></option>
        <option value="manage_options" <?php selected($capability, 'manage_options'); ?>><?php esc_html_e('Manage Options (Administrators)', 'bbpress-support-toolkit'); ?></option>
        <option value="edit_others_posts" <?php selected($capability, 'edit_others_posts'); ?>><?php esc_html_e('Edit Others Posts (Editors)', 'bbpress-support-toolkit'); ?></option>
    </select>
    <p class="description"><?php esc_html_e(
        "Users with this capability can view all private replies.",
        "bbpress-support-toolkit"
    ); ?></p>
    <?php
}

function bbps_admin_setting_callback_notification_subject()
{
    $subject = get_option("bbps_notification_subject");
    if (empty($subject)) {
        $subject = __(
            "Your registration at %BLOGNAME%",
            "bbpress-support-toolkit"
        );
    }
    ?>
    <input type="text" name="bbps_notification_subject" value='<?php echo esc_attr(
        $subject
    ); ?>' class='regular-text' />
    <br/>
    <i><?php esc_html_e(
        "<code>%USERNAME%</code> will be replaced with a username.",
        "bbpress-support-toolkit"
    ); ?></i><br />
    <i><?php esc_html_e(
        '<code>%PASSWORD%</code> will be replaced with the user\'s password.',
        "bbpress-support-toolkit"
    ); ?></i><br />
    <i><?php esc_html_e(
        "<code>%BLOGNAME%</code> will be replaced with the name of your blog.",
        "bbpress-support-toolkit"
    ); ?></i><br />
    <i><?php esc_html_e(
        "<code>%BLOGURL%</code> will be replaced with the url of your blog.",
        "bbpress-support-toolkit"
    ); ?></i>
    <?php
}

function bbps_admin_setting_callback_notification_message()
{
    $message = get_option("bbps_notification_message");
    if (empty($message)) {
        $message = __(
            'Thanks for signing up to our blog.

You can login with the following credentials by visiting %BLOGURL%

Username : %USERNAME%
Password : %PASSWORD%

We look forward to your next visit!

The team at %BLOGNAME%',
            "bbpress-support-toolkit"
        );
    }
    ?>
    <textarea name="bbps_notification_message" class='large-text' rows="10"><?php echo esc_textarea(
        $message
    ); ?></textarea>
    <br/>
    <i><?php esc_html_e(
        "<code>%BLOGNAME%</code> will be replaced with the name of your blog.",
        "bbpress-support-toolkit"
    ); ?></i><br />
    <i><?php esc_html_e(
        "<code>%BLOGURL%</code> will be replaced with the url of your blog.",
        "bbpress-support-toolkit"
    ); ?></i>
    <?php
}

add_filter("manage_topic_posts_columns", "bbps_add_topic_status_column");
add_action(
    "manage_topic_posts_custom_column",
    "bbps_show_topic_status_column",
    10,
    2
);
add_filter("bulk_actions-edit-topic", "bbps_add_bulk_actions");
add_filter("handle_bulk_actions-edit-topic", "bbps_handle_bulk_actions", 10, 3);

function bbps_add_topic_status_column($columns)
{
    $columns["bbps_status"] = __("Support Status", "bbpress-support-toolkit");
    $columns["bbps_forum"] = __("Forum Type", "bbpress-support-toolkit");
    return $columns;
}

function bbps_show_topic_status_column($column_name, $post_id)
{
    if ($column_name == "bbps_status") {
        $status = get_post_meta($post_id, "_bbps_topic_status", true);
        $default = get_option("bbps_default_status");
        $current_status = $status ? $status : $default;

        switch ($current_status) {
            case 1:
                echo '<span class="dashicons dashicons-clock" title="' .
                    esc_attr__("Not Resolved", "bbpress-support-toolkit") .
                    '"></span>';
                break;
            case 2:
                echo '<span class="dashicons dashicons-yes-alt" style="color: green;" title="' .
                    esc_attr__("Resolved", "bbpress-support-toolkit") .
                    '"></span>';
                break;
            case 3:
                echo '<span class="dashicons dashicons-info" style="color: #6c757d;" title="' .
                    esc_attr__("Not a Support Question", "bbpress-support-toolkit") .
                    '"></span>';
                break;
        }

        if (get_post_meta($post_id, "_bbps_urgent_topic", true)) {
            echo ' <span class="dashicons dashicons-warning" style="color: red;" title="' .
                esc_attr__("Urgent", "bbpress-support-toolkit") .
                '"></span>';
        }

        if (get_post_meta($post_id, "_bbps_topic_claimed", true)) {
            echo ' <span class="dashicons dashicons-admin-users" style="color: blue;" title="' .
                esc_attr__("Claimed", "bbpress-support-toolkit") .
                '"></span>';
        }
    }

    if ($column_name == "bbps_forum") {
        $forum_id = bbp_get_topic_forum_id($post_id);
        $forum_types = [];

        if (bbps_is_support_forum($forum_id)) {
            $forum_types[] = __("Support", "bbpress-support-toolkit");
        }
        if (bbps_is_premium_forum($forum_id)) {
            $forum_types[] = __("Premium", "bbpress-support-toolkit");
        }

        echo empty($forum_types)
            ? __("Standard", "bbpress-support-toolkit")
            : implode(", ", $forum_types);
    }
}

function bbps_add_bulk_actions($bulk_actions)
{
    $bulk_actions["bbps_mark_resolved"] = __(
        "Mark as Resolved",
        "bbpress-support-toolkit"
    );
    $bulk_actions["bbps_mark_not_resolved"] = __(
        "Mark as Not Resolved",
        "bbpress-support-toolkit"
    );
    $bulk_actions["bbps_mark_not_support"] = __(
        "Mark as Not Support Question",
        "bbpress-support-toolkit"
    );
    $bulk_actions["bbps_mark_urgent"] = __(
        "Mark as Urgent",
        "bbpress-support-toolkit"
    );
    $bulk_actions["bbps_unmark_urgent"] = __(
        "Remove Urgent Status",
        "bbpress-support-toolkit"
    );
    return $bulk_actions;
}

function bbps_handle_bulk_actions($redirect_to, $action, $post_ids)
{
    if (
        !in_array($action, [
            "bbps_mark_resolved",
            "bbps_mark_not_resolved",
            "bbps_mark_not_support",
            "bbps_mark_urgent",
            "bbps_unmark_urgent",
        ])
    ) {
        return $redirect_to;
    }

    $count = 0;

    foreach ($post_ids as $post_id) {
        switch ($action) {
            case "bbps_mark_resolved":
                update_post_meta($post_id, "_bbps_topic_status", 2);
                delete_post_meta($post_id, "_bbps_urgent_topic");
                delete_post_meta($post_id, "_bbps_topic_claimed");
                $count++;
                break;
            case "bbps_mark_not_resolved":
                update_post_meta($post_id, "_bbps_topic_status", 1);
                $count++;
                break;
            case "bbps_mark_not_support":
                update_post_meta($post_id, "_bbps_topic_status", 3);
                delete_post_meta($post_id, "_bbps_urgent_topic");
                delete_post_meta($post_id, "_bbps_topic_claimed");
                $count++;
                break;
            case "bbps_mark_urgent":
                update_post_meta($post_id, "_bbps_urgent_topic", 1);
                $count++;
                break;
            case "bbps_unmark_urgent":
                delete_post_meta($post_id, "_bbps_urgent_topic");
                $count++;
                break;
        }
    }

    $redirect_to = add_query_arg("bbps_bulk_updated", $count, $redirect_to);
    return $redirect_to;
}

add_action("admin_notices", "bbps_bulk_action_admin_notice");
function bbps_bulk_action_admin_notice()
{
    if (!empty($_REQUEST["bbps_bulk_updated"])) {
        $count = intval($_REQUEST["bbps_bulk_updated"]);
        printf(
            '<div id="message" class="updated notice is-dismissible"><p>' .
            _n(
                "Updated %s topic.",
                "Updated %s topics.",
                $count,
                "bbpress-support-toolkit"
            ) .
            "</p></div>",
            $count
        );
    }
}

// Additional Features Callback Functions
function bbps_admin_setting_callback_additional_section()
{
    echo '<p>' . __("Additional features to enhance your bbPress forums.", "bbpress-support-toolkit") . '</p>';
}

function bbps_admin_setting_callback_disable_user_page()
{
    $option = get_option("bbps_disable_user_page", 0);
    echo '<input type="checkbox" name="bbps_disable_user_page" value="1" ' . checked(1, $option, false) . ' />';
    echo '<label for="bbps_disable_user_page">' . __("Disable bbPress user profile pages", "bbpress-support-toolkit") . '</label>';
    echo '<p class="description">' . __("This will disable the bbPress user profile pages to prevent spam.", "bbpress-support-toolkit") . '</p>';
}

function bbps_admin_setting_callback_remove_avatars()
{
    $option = get_option("bbps_remove_avatars", 0);
    echo '<input type="checkbox" name="bbps_remove_avatars" value="1" ' . checked(1, $option, false) . ' />';
    echo '<label for="bbps_remove_avatars">' . __("Remove user avatars from bbPress", "bbpress-support-toolkit") . '</label>';
    echo '<p class="description">' . __("This will remove avatars from topics, replies, and user profiles.", "bbpress-support-toolkit") . '</p>';
}

function bbps_admin_setting_callback_redirect_single_replies()
{
    $option = get_option("bbps_redirect_single_replies", 0);
    echo '<input type="checkbox" name="bbps_redirect_single_replies" value="1" ' . checked(1, $option, false) . ' />';
    echo '<label for="bbps_redirect_single_replies">' . __("Redirect single reply pages to parent topics", "bbpress-support-toolkit") . '</label>';
    echo '<p class="description">' . __("This will redirect users from single reply pages to the parent topic page.", "bbpress-support-toolkit") . '</p>';
}

function bbps_admin_setting_callback_custom_notifications()
{
    $option = get_option("bbps_custom_notifications", 0);
    echo '<input type="checkbox" name="bbps_custom_notifications" value="1" ' . checked(1, $option, false) . ' />';
    echo '<label for="bbps_custom_notifications">' . __("Enable custom email notifications", "bbpress-support-toolkit") . '</label>';
    echo '<p class="description">' . __("This will allow you to customize the email notifications sent to forum subscribers.", "bbpress-support-toolkit") . '</p>';
}

// Notification Settings Callback Functions
function bbps_admin_setting_callback_notification_settings_section()
{
    echo '<p>' . __("Customize the email notifications sent to forum and topic subscribers.", "bbpress-support-toolkit") . '</p>';
}

function bbps_admin_setting_callback_topic_notice_title()
{
    $default_title = '[' . get_option('blogname') . '] {title}';
    $value = get_option('bbps_topic_notice_title', $default_title);
    echo '<input name="bbps_topic_notice_title" type="text" id="bbps_topic_notice_title" value="' . esc_attr($value) . '" class="regular-text" />';
    echo '<p class="description">' . __("The subject of the topic notification email. Use {title} for the topic title.", "bbpress-support-toolkit") . '</p>';
}

function bbps_admin_setting_callback_topic_notice_body()
{
    $default = '{author} wrote:\n\n{content}\n\nPost Link: {url}\n\n-----------\n\nYou are receiving this email because you subscribed to the {forum_name} forum.\n\nLogin and visit the forum to unsubscribe from these emails.';
    $value = get_option('bbps_topic_notice_body', $default);
    echo '<textarea name="bbps_topic_notice_body" class="large-text" rows="15" id="bbps_topic_notice_body">' . esc_textarea($value) . '</textarea>';
    echo '<p class="description">' . __("Email message sent to forum subscribers when a new topic is posted. Available tokens: {author}, {content}, {url}, {forum_name}", "bbpress-support-toolkit") . '</p>';
}

function bbps_admin_setting_callback_reply_notice_title()
{
    $default_title = '[' . get_option('blogname') . '] {title}';
    $value = get_option('bbps_reply_notice_title', $default_title);
    echo '<input name="bbps_reply_notice_title" type="text" id="bbps_reply_notice_title" value="' . esc_attr($value) . '" class="regular-text" />';
    echo '<p class="description">' . __("The subject of the reply notification email. Use {title} for the topic title.", "bbpress-support-toolkit") . '</p>';
}

function bbps_admin_setting_callback_reply_notice_body()
{
    $default = '{author} wrote:\n\n{content}\n\nPost Link: {url}\n\n-----------\n\nYou are receiving this email because you subscribed to a forum topic.\n\nLogin and visit the topic to unsubscribe from these emails.';
    $value = get_option('bbps_reply_notice_body', $default);
    echo '<textarea name="bbps_reply_notice_body" class="large-text" rows="15" id="bbps_reply_notice_body">' . esc_textarea($value) . '</textarea>';
    echo '<p class="description">' . __("Email message sent to topic subscribers when a new reply is posted. Available tokens: {author}, {content}, {url}", "bbpress-support-toolkit") . '</p>';
}

// Ajax处理函数
function bbps_ajax_save_settings() {
    // 验证nonce
    if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'bbps_settings_nonce')) {
        wp_send_json_error(__('Invalid security token. Please refresh the page and try again.', 'bbpress-support-toolkit'));
    }

    // 检查权限
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('You do not have permission to perform this action.', 'bbpress-support-toolkit'));
    }

    // 获取表单数据
    $form_data = isset($_POST['form_data']) ? $_POST['form_data'] : array();
    
    if (empty($form_data)) {
        wp_send_json_error(__('No settings data provided.', 'bbpress-support-toolkit'));
    }

    // 解析表单数据
    parse_str($form_data, $parsed_data);
    
    // 保存设置
    $saved_count = 0;
    
    // 获取所有已注册的设置
    global $wp_settings_fields;
    $settings_to_save = array();
    
    if (isset($wp_settings_fields['bbpress-support-toolkit'])) {
        foreach ($wp_settings_fields['bbpress-support-toolkit'] as $section => $fields) {
            foreach ($fields as $field_id => $field) {
                if (strpos($field_id, 'bbps_') === 0) {
                    $settings_to_save[] = $field_id;
                }
            }
        }
    }
    
    foreach ($settings_to_save as $setting_name) {
        // 对于复选框类型的设置，如果没有在表单数据中，说明未选中，应该设为0
        if (strpos($setting_name, '_enable') !== false) {
            $value = isset($parsed_data[$setting_name]) ? 1 : 0;
        } else {
            $value = isset($parsed_data[$setting_name]) ? $parsed_data[$setting_name] : '';
            
            // 基本验证
            if (strpos($setting_name, '_count') !== false) {
                $value = intval($value);
            } else {
                $value = sanitize_text_field($value);
            }
        }
        
        update_option($setting_name, $value);
        $saved_count++;
    }

    wp_send_json_success(array(
        'message' => __('Settings saved successfully!', 'bbpress-support-toolkit'),
        'saved_count' => $saved_count
    ));
}

// 加载管理脚本
function bbps_enqueue_admin_scripts($hook) {
    if ($hook !== 'settings_page_bbpress-support-toolkit') {
        return;
    }
    
    wp_enqueue_script('jquery');
    wp_localize_script('jquery', 'bbps_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('bbps_settings_nonce'),
        'saving_text' => __('Saving...', 'bbpress-support-toolkit'),
        'saved_text' => __('Settings saved successfully!', 'bbpress-support-toolkit'),
        'error_text' => __('Error saving settings. Please try again.', 'bbpress-support-toolkit')
    ));
}

// Advanced Features Callback Functions
function bbps_admin_setting_callback_advanced_section()
{
    echo '<p>' . __('Advanced features to enhance your bbPress forums with additional functionality.', 'bbpress-support-toolkit') . '</p>';
}

function bbps_admin_setting_callback_admin_notes()
{
    $option = get_option('bbps_enable_admin_notes', 0);
    echo '<input type="checkbox" name="bbps_enable_admin_notes" value="1" ' . checked(1, $option, false) . ' />';
    echo '<label for="bbps_enable_admin_notes">' . __('Enable admin notes for topics and replies', 'bbpress-support-toolkit') . '</label>';
    echo '<p class="description">' . __('Allows administrators to add private notes to topics and replies that are only visible to other administrators.', 'bbpress-support-toolkit') . '</p>';
}

function bbps_admin_setting_callback_live_preview()
{
    $option = get_option('bbps_enable_live_preview', 0);
    echo '<input type="checkbox" name="bbps_enable_live_preview" value="1" ' . checked(1, $option, false) . ' />';
    echo '<label for="bbps_enable_live_preview">' . __('Enable live preview for topics and replies', 'bbpress-support-toolkit') . '</label>';
    echo '<p class="description">' . __('Allows users to preview their posts in real-time before submitting.', 'bbpress-support-toolkit') . '</p>';
}

function bbps_admin_setting_callback_mark_as_read()
{
    $option = get_option('bbps_enable_mark_as_read', 0);
    echo '<input type="checkbox" name="bbps_enable_mark_as_read" value="1" ' . checked(1, $option, false) . ' />';
    echo '<label for="bbps_enable_mark_as_read">' . __('Enable mark as read functionality', 'bbpress-support-toolkit') . '</label>';
    echo '<p class="description">' . __('Allows users to mark topics as read/unread and track their reading progress.', 'bbpress-support-toolkit') . '</p>';
}

function bbps_admin_setting_callback_canned_replies()
{
    $option = get_option('bbps_enable_canned_replies', 0);
    echo '<input type="checkbox" name="bbps_enable_canned_replies" value="1" ' . checked(1, $option, false) . ' />';
    echo '<label for="bbps_enable_canned_replies">' . __('Enable canned replies', 'bbpress-support-toolkit') . '</label>';
    echo '<p class="description">' . __('Allows administrators and moderators to create and use pre-written reply templates.', 'bbpress-support-toolkit') . '</p>';
}

function bbps_admin_setting_callback_report_content()
{
    $option = get_option('bbps_enable_report_content', 0);
    echo '<input type="checkbox" name="bbps_enable_report_content" value="1" ' . checked(1, $option, false) . ' />';
    echo '<label for="bbps_enable_report_content">' . __('Enable content reporting', 'bbpress-support-toolkit') . '</label>';
    echo '<p class="description">' . __('Allows users to report inappropriate topics and replies to administrators.', 'bbpress-support-toolkit') . '</p>';
}

function bbps_admin_setting_callback_topic_lock()
{
    $option = get_option('bbps_enable_topic_lock', 0);
    echo '<input type="checkbox" name="bbps_enable_topic_lock" value="1" ' . checked(1, $option, false) . ' />';
    echo '<label for="bbps_enable_topic_lock">' . __('Enable topic lock notifications', 'bbpress-support-toolkit') . '</label>';
    echo '<p class="description">' . __('Warns moderators when another moderator is currently viewing the same topic to prevent conflicts.', 'bbpress-support-toolkit') . '</p>';
}