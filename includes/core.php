<?php

if (!defined("ABSPATH")) {
    exit();
}

function bbps_is_premium_forum($forum_id)
{
    $premium_forum = get_post_meta($forum_id, "_bbps_is_premium", true);
    return $premium_forum == 1;
}

function bbps_is_support_forum($forum_id)
{
    $support_forum = get_post_meta($forum_id, "_bbps_is_support", true);
    return $support_forum == 1;
}

function bbps_is_topic_premium()
{
    $is_premium = get_post_meta(
        bbp_get_topic_forum_id(),
        "_bbps_is_premium",
        true
    );
    return $is_premium == 1;
}

function bbps_is_reply_premium()
{
    $is_premium = get_post_meta(
        bbp_get_reply_forum_id(),
        "_bbps_is_premium",
        true
    );
    return $is_premium == 1;
}

function bbps_get_all_premium_topic_ids()
{
    global $wpdb;

    $forum_query = $wpdb->prepare(
        "SELECT `post_id` FROM {$wpdb->postmeta} WHERE `meta_key` = %s AND `meta_value` = %s",
        "_bbps_is_premium",
        "1"
    );
    $premium_forums = $wpdb->get_col($forum_query);

    if (empty($premium_forums)) {
        return [];
    }

    $forum_ids = array_map("intval", $premium_forums);
    $placeholder = implode(",", array_fill(0, count($forum_ids), "%d"));

    $topics_query = $wpdb->prepare(
        "SELECT `ID` FROM {$wpdb->posts} WHERE `post_parent` IN ($placeholder) AND `post_type` = %s",
        array_merge($forum_ids, ["topic"])
    );
    $premium_topics = $wpdb->get_col($topics_query);

    return array_map("intval", $premium_topics);
}

function bbps_topic_resolved($topic_id)
{
    return get_post_meta($topic_id, "_bbps_topic_status", true) == 2;
}

function bbps_is_post_count_enabled()
{
    return get_option("bbps_enable_post_count");
}

function bbps_is_user_rank_enabled()
{
    return get_option("bbps_enable_user_rank");
}

function bbps_is_resolved_enabled()
{
    $options = get_option("bbps_used_status");
    return isset($options["res"]) ? $options["res"] : false;
}

function bbps_is_not_resolved_enabled()
{
    $options = get_option("bbps_used_status");
    return isset($options["notres"]) ? $options["notres"] : false;
}

function bbps_is_not_support_enabled()
{
    $options = get_option("bbps_used_status");
    return isset($options["notsup"]) ? $options["notsup"] : false;
}

function bbps_is_moderator_enabled()
{
    $options = get_option("bbps_status_permissions");
    return isset($options["mod"]) ? $options["mod"] : false;
}

function bbps_is_admin_enabled()
{
    $options = get_option("bbps_status_permissions");
    return isset($options["admin"]) ? $options["admin"] : false;
}

function bbps_is_user_enabled()
{
    $options = get_option("bbps_status_permissions");
    return isset($options["user"]) ? $options["user"] : false;
}

function bbps_is_topic_move_enabled()
{
    return get_option("bbps_enable_topic_move");
}

function bbps_is_topic_urgent_enabled()
{
    return get_option("bbps_status_permissions_urgent");
}

function bbps_is_topic_claim_enabled()
{
    return get_option("bbps_claim_topic");
}

function bbps_is_topic_claim_display_enabled()
{
    return get_option("bbps_claim_topic_display");
}

function bbps_is_topic_assign_enabled()
{
    return get_option("bbps_topic_assign");
}

function bbps_is_new_topic_label_enabled()
{
    return get_option("bbps_enable_new_topic_label");
}

function bbps_is_closed_topic_label_enabled()
{
    return get_option("bbps_enable_closed_topic_label");
}

function bbps_is_sticky_topic_label_enabled()
{
    return get_option("bbps_enable_sticky_topic_label");
}

function bbps_is_search_integration_enabled()
{
    return get_option("bbps_enable_search_integration");
}

function bbps_is_search_url_rewrite_enabled()
{
    return get_option("bbps_enable_search_url_rewrite");
}

function bbps_is_post_author_label_enabled()
{
    return get_option("bbps_enable_post_author_label");
}

function bbps_is_private_replies_enabled()
{
    return get_option("bbps_enable_private_replies");
}

function bbps_get_private_replies_capability()
{
    return get_option("bbps_private_replies_capability", "moderate");
}

function bbps_is_seo_optimization_enabled()
{
    return get_option("bbps_enable_seo_optimization");
}

function bbps_is_meta_descriptions_enabled()
{
    return get_option("bbps_enable_meta_descriptions");
}

function bbps_is_open_graph_enabled()
{
    return get_option("bbps_enable_open_graph");
}

function bbps_is_twitter_cards_enabled()
{
    return get_option("bbps_enable_twitter_cards");
}

function bbps_is_schema_markup_enabled()
{
    return get_option("bbps_enable_schema_markup");
}

function bbps_is_canonical_urls_enabled()
{
    return get_option("bbps_enable_canonical_urls");
}

function bbps_is_email_fix_enabled()
{
    return get_option("bbps_enable_email_fix");
}

function bbps_is_title_length_fix_enabled()
{
    return get_option("bbps_enable_title_length_fix");
}

function bbps_is_remove_topic_tags_enabled()
{
    return get_option("bbps_remove_topic_tags");
}

function bbps_is_default_forum_enabled()
{
    return get_option("bbps_enable_default_forum");
}

if (bbps_is_default_forum_enabled()) {
    add_filter('bbp_get_form_topic_forum', 'bbps_set_default_forum');
    add_action('bbp_theme_before_topic_form_forum', 'bbps_set_default_forum_js');
    add_filter('bbp_get_dropdown_forums', 'bbps_modify_forum_dropdown', 10, 2);
}

function bbps_set_default_forum($forum_id) {
    if (empty($forum_id)) {
        $default_forum_id = get_option('bbps_default_forum_id');
        if (!empty($default_forum_id)) {
            return intval($default_forum_id);
        }
    }
    return $forum_id;
}

function bbps_set_default_forum_js() {
    $default_forum_id = get_option('bbps_default_forum_id');
    if (!empty($default_forum_id)) {
        ?>
        <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            var forumSelect = document.getElementById('bbp_forum_id');
            if (forumSelect && forumSelect.value === '') {
                forumSelect.value = '<?php echo intval($default_forum_id); ?>';
            }
        });
        </script>
        <?php
    }
}

function bbps_modify_forum_dropdown($dropdown, $args) {
    $default_forum_id = get_option('bbps_default_forum_id');
    if (!empty($default_forum_id) && !empty($dropdown)) {
        $dropdown = str_replace(
            'value="' . $default_forum_id . '"',
            'value="' . $default_forum_id . '" selected="selected"',
            $dropdown
        );
    }
    return $dropdown;
}

add_filter("bbp_has_topics_query", "bbps_lock_to_author");

function bbps_lock_to_author($bbp_t)
{
    global $wp_query;

    if (
        (!bbps_is_premium_forum(bbp_get_forum_id()) ||
            current_user_can("administrator") ||
            current_user_can("bbp_moderator")) &&
        !bbp_is_single_user()
    ) {
        return $bbp_t;
    }

    if (bbp_is_single_user()) {
        $premium_topics = bbps_get_all_premium_topic_ids();
        $user_id = bbp_get_displayed_user_id();
        $bbp_t["post_author"] = $user_id;
        $bbp_t["author"] = $user_id;
        $bbp_t["post__not_in"] = $premium_topics;
        $bbp_t["post_type"] = "topic";
        return $bbp_t;
    } else {
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;

        if ($user_id == 0) {
            $user_id = 99999999;
        }

        $bbp_t["post_author"] = $user_id;
        $bbp_t["author"] = $user_id;
        $bbp_t["post_type"] = "topic";
        $bbp_t["show_stickies"] = 0;
        $bbp_t["posts_per_page"] = 30;

        return $bbp_t;
    }
}

function bbps_hide_author_link($author_link, $args = 0)
{
    $retval = "";

    if (
        !bbps_is_premium_forum(bbp_get_forum_id()) ||
        current_user_can("administrator") ||
        current_user_can("bbp_moderator")
    ) {
        $retval = $author_link;
    }

    return $retval;
}
add_filter("bbp_suppress_private_author_link", "bbps_hide_author_link", 5, 2);

function bbps_hide_forum_meta($retval, $forum_id = 0)
{
    if (
        !bbps_is_premium_forum(bbp_get_forum_id()) ||
        current_user_can("administrator") ||
        current_user_can("bbp_moderator")
    ) {
        return $retval;
    } else {
        return "-";
    }
}
add_filter("bbp_suppress_private_forum_meta", "bbps_hide_forum_meta", 10, 2);

function bbps_get_update_capabilities()
{
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    $topic_author_id = bbp_get_topic_author_id();
    $permissions = get_option("bbps_status_permissions");
    $can_edit = false;

    if (
        isset($permissions["admin"]) &&
        $permissions["admin"] == 1 &&
        current_user_can("administrator")
    ) {
        $can_edit = true;
    }
    if (
        isset($permissions["mod"]) &&
        $permissions["mod"] == 1 &&
        current_user_can("bbp_moderator")
    ) {
        $can_edit = true;
    }
    if (
        $user_id == $topic_author_id &&
        isset($permissions["user"]) &&
        $permissions["user"] == 1
    ) {
        $can_edit = true;
    }

    return $can_edit;
}

add_action(
    "bbp_template_before_single_topic",
    "bbps_add_support_forum_features"
);
function bbps_add_support_forum_features()
{
    if (bbps_is_support_forum(bbp_get_forum_id())) {
        $can_edit = bbps_get_update_capabilities();
        if ($can_edit) {

            $topic_id = bbp_get_topic_id();
            $status = bbps_get_topic_status($topic_id);
            $forum_id = bbp_get_forum_id();

            if (isset($_GET["bbps_message"])) {
                $message_type = sanitize_text_field($_GET["bbps_message"]);
                if ($message_type === "moved") {
                    echo '<div class="bbp-template-notice info"><p>' .
                        esc_html__(
                            "Topic has been successfully moved.",
                            "bbpress-support-toolkit"
                        ) .
                        "</p></div>";
                } elseif ($message_type === "move_error") {
                    echo '<div class="bbp-template-notice error"><p>' .
                        esc_html__(
                            "Error: Unable to move topic.",
                            "bbpress-support-toolkit"
                        ) .
                        "</p></div>";
                }
            }
            ?>
            <div class="row">
                <div class="col-md-6">
                    <div id="bbps_support_forum_options" class="well">
                        <?php if ($can_edit) {
                            bbps_generate_status_options($topic_id, $status);
                        } else {
                            echo esc_html(
                                sprintf(
                                    __(
                                        "This topic is: %s",
                                        "bbpress-support-toolkit"
                                    ),
                                    $status
                                )
                            );
                        } ?>
                    </div>
                    <?php if (
                        get_option("bbps_enable_topic_move") == 1 &&
                        (current_user_can("administrator") ||
                            current_user_can("bbp_moderator"))
                    ) { ?>
                        <div id="bbps_support_forum_move" class="span6 well">
                            <form id="bbps-topic-move" name="bbps_support_topic_move" action="" method="post">
                                <?php wp_nonce_field(
                                    "bbps_move_topic",
                                    "bbps_move_nonce"
                                ); ?>
                                <label for="bbp_forum_id"><?php esc_html_e(
                                    "Move topic to:",
                                    "bbpress-support-toolkit"
                                ); ?></label>
                                <?php bbps_forums_dropdown($forum_id); ?>
                                <input type="submit" value="<?php esc_attr_e(
                                    "Move",
                                    "bbpress-support-toolkit"
                                ); ?>" name="bbps_topic_move_submit" />
                                <input type="hidden" value="bbps_move_topic" name="bbps_action"/>
                                <input type="hidden" value="<?php echo esc_attr(
                                    $topic_id
                                ); ?>" name="bbps_topic_id" />
                                <input type="hidden" value="<?php echo esc_attr(
                                    $forum_id
                                ); ?>" name="bbp_old_forum_id" />
                            </form>
                        </div>
                        <?php } ?>
                </div>
            </div>
            <?php
        }
    }
}

add_action(
    "bbp_template_before_single_topic",
    "bbps_display_not_support_message",
    15
);
function bbps_display_not_support_message()
{
    $topic_id = bbp_get_topic_id();
    $status = get_post_meta($topic_id, "_bbps_topic_status", true);
    $default = get_option("bbps_default_status");
    $current_status = $status ? $status : $default;
    
    if ($current_status == 3) {
        ?>
        <div class="bbps-support-forums-message">
            <strong><?php esc_html_e("Notice:", "bbpress-support-toolkit"); ?></strong>
            <?php esc_html_e(
                "This is a non-technical issue or unsupported matter, and has been transferred to the business team for processing or follow-up. If there is no response, you can continue to provide additional background information. For technical support requests, please include the program and system versions you are using.",
                "bbpress-support-toolkit"
            ); ?>
        </div>
        <?php
    }
}

function bbps_forums_dropdown($current_forum_id = 0)
{
    $forums = get_posts([
        "post_type" => bbp_get_forum_post_type(),
        "post_status" => bbp_get_public_status_id(),
        "posts_per_page" => -1,
        "orderby" => "menu_order title",
        "order" => "ASC",
        "meta_query" => [
            "relation" => "OR",
            [
                "key" => "_bbp_forum_type",
                "value" => bbp_get_forum_post_type(),
                "compare" => "=",
            ],
            [
                "key" => "_bbp_forum_type",
                "compare" => "NOT EXISTS",
            ],
        ],
    ]);

    echo '<select id="bbp_forum_id" name="bbp_forum_id" required>';
    echo '<option value="">' .
        esc_html__("Select a forum", "bbpress-support-toolkit") .
        "</option>";

    foreach ($forums as $forum) {
        if (
            $forum->ID != $current_forum_id &&
            !bbp_is_forum_category($forum->ID)
        ) {
            echo '<option value="' .
                esc_attr($forum->ID) .
                '">' .
                esc_html($forum->post_title) .
                "</option>";
        }
    }

    echo "</select>";

    wp_reset_postdata();
}

function bbps_get_topic_status($topic_id)
{
    $default = get_option("bbps_default_status");
    $status = get_post_meta($topic_id, "_bbps_topic_status", true);
    $switch = $status ? $status : $default;

    switch ($switch) {
        case 1:
            return __("not resolved", "bbpress-support-toolkit");
        case 2:
            return __("resolved", "bbpress-support-toolkit");
        case 3:
            return __("not a support question", "bbpress-support-toolkit");
        default:
            return __("not resolved", "bbpress-support-toolkit");
    }
}

function bbps_generate_status_options($topic_id, $status, $button = true)
{
    $dropdown_options = get_option("bbps_used_status");
    $status_value = get_post_meta($topic_id, "_bbps_topic_status", true);
    $default = get_option("bbps_default_status");
    $value = $status_value ? $status_value : $default;

    if ($button): ?>
        <form id="bbps-topic-status" name="bbps_support" action="" method="post">
            <?php wp_nonce_field("bbps_update_status", "bbps_status_nonce"); ?>
    <?php endif;
    ?>

    <label for="bbps_support_options"><?php esc_html_e(
        "Topic Status:",
        "bbpress-support-toolkit"
    ); ?></label>
    <select name="bbps_support_option" id="bbps_support_options">
        <?php if (
            isset($dropdown_options["notres"]) &&
            $dropdown_options["notres"] == 1
        ): ?>
            <option value="1" <?php selected($value, 1); ?>><?php esc_html_e(
    "Not Resolved",
    "bbpress-support-toolkit"
); ?></option>
        <?php endif; ?>
        <?php if (
            isset($dropdown_options["res"]) &&
            $dropdown_options["res"] == 1
        ): ?>
            <option value="2" <?php selected($value, 2); ?>><?php esc_html_e(
    "Resolved",
    "bbpress-support-toolkit"
); ?></option>
        <?php endif; ?>
        <?php if (
            isset($dropdown_options["notsup"]) &&
            $dropdown_options["notsup"] == 1
        ): ?>
            <option value="3" <?php selected($value, 3); ?>><?php esc_html_e(
    "Not a support question",
    "bbpress-support-toolkit"
); ?></option>
        <?php endif; ?>
    </select>

    <?php if ($button): ?>
        <input type="submit" value="<?php esc_attr_e(
            "Update",
            "bbpress-support-toolkit"
        ); ?>" name="bbps_support_submit" />
    <?php endif; ?>

    <input type="hidden" value="bbps_update_status" name="bbps_action"/>
    <input type="hidden" value="<?php echo esc_attr(
        $topic_id
    ); ?>" name="bbps_topic_id" />

    <?php if ($button): ?>
        </form>
    <?php endif;
}

function bbps_update_status()
{
    if (
        !isset($_POST["bbps_status_nonce"]) ||
        !wp_verify_nonce($_POST["bbps_status_nonce"], "bbps_update_status")
    ) {
        return;
    }

    $can_edit = bbps_get_update_capabilities();
    if (!$can_edit) {
        return;
    }

    $topic_id = intval($_POST["bbps_topic_id"]);
    $status = intval($_POST["bbps_support_option"]);

    $has_status = get_post_meta($topic_id, "_bbps_topic_status", true);
    $is_urgent = get_post_meta($topic_id, "_bbps_urgent_topic", true);
    $is_claimed = get_post_meta($topic_id, "_bbps_topic_claimed", true);

    if ($has_status) {
        delete_post_meta($topic_id, "_bbps_topic_status");
    }

    if ($status == 2 || $status == 3) {
        if ($is_urgent) {
            delete_post_meta($topic_id, "_bbps_urgent_topic");
        }
        if ($is_claimed) {
            delete_post_meta($topic_id, "_bbps_topic_claimed");
        }
    }

    update_post_meta($topic_id, "_bbps_topic_status", $status);

    if (isset($_POST["bbps_minutes_spent"])) {
        $thread_time = get_post_meta($topic_id, "_bbps_topic_minutes", true);
        if (!is_array($thread_time)) {
            $thread_time = [];
        }
        $id = get_current_user_id();
        if ($id) {
            $thread_time[] = [
                "user_id" => $id,
                "recorded" => time(),
                "time" => sanitize_text_field($_POST["bbps_minutes_spent"]),
            ];
            update_post_meta($topic_id, "_bbps_topic_minutes", $thread_time);
        }
    }
}

add_action("init", "bbps_handle_actions");
function bbps_handle_actions()
{
    if (
        !empty($_POST["bbps_action"]) &&
        $_POST["bbps_action"] == "bbps_update_status"
    ) {
        bbps_update_status();
    }

    if (!empty($_POST["bbps_topic_move_submit"])) {
        bbps_move_topic();
    }

    if (isset($_GET["action"]) && isset($_GET["topic_id"])) {
        $action = sanitize_text_field($_GET["action"]);
        $topic_id = intval($_GET["topic_id"]);

        if (
            $action == "bbps_make_topic_urgent" &&
            wp_verify_nonce($_GET["nonce"], "bbps_urgent_" . $topic_id)
        ) {
            bbps_urgent_topic();
        }

        if (
            $action == "bbps_make_topic_not_urgent" &&
            wp_verify_nonce($_GET["nonce"], "bbps_urgent_" . $topic_id)
        ) {
            bbps_not_urgent_topic();
        }

        if (
            $action == "bbps_claim_topic" &&
            wp_verify_nonce($_GET["nonce"], "bbps_claim_" . $topic_id)
        ) {
            bbps_claim_topic();
        }

        if (
            $action == "bbps_unclaim_topic" &&
            wp_verify_nonce($_GET["nonce"], "bbps_claim_" . $topic_id)
        ) {
            bbps_unclaim_topic();
        }
    }
}

function bbps_move_topic()
{
    if (
        !isset($_POST["bbps_move_nonce"]) ||
        !wp_verify_nonce($_POST["bbps_move_nonce"], "bbps_move_topic")
    ) {
        return false;
    }

    if (
        !current_user_can("administrator") &&
        !current_user_can("bbp_moderator")
    ) {
        wp_die(
            esc_html__(
                "You do not have permission to move topics.",
                "bbpress-support-toolkit"
            )
        );
    }

    $topic_id = intval($_POST["bbps_topic_id"]);
    $new_forum_id = intval($_POST["bbp_forum_id"]);
    $old_forum_id = intval($_POST["bbp_old_forum_id"]);

    if (
        !$topic_id ||
        !$new_forum_id ||
        !$old_forum_id ||
        $new_forum_id === $old_forum_id
    ) {
        return false;
    }

    global $wpdb;

    $wpdb->query("START TRANSACTION");

    try {
        $result = $wpdb->update(
            $wpdb->posts,
            ["post_parent" => $new_forum_id],
            ["ID" => $topic_id],
            ["%d"],
            ["%d"]
        );

        if ($result === false) {
            throw new Exception("Failed to update topic parent");
        }

        update_post_meta($topic_id, "_bbp_forum_id", $new_forum_id);

        $replies = get_posts([
            "post_type" => bbp_get_reply_post_type(),
            "post_parent" => $topic_id,
            "posts_per_page" => -1,
            "post_status" => ["publish", "private", "hidden"],
        ]);

        foreach ($replies as $reply) {
            update_post_meta($reply->ID, "_bbp_forum_id", $new_forum_id);
        }

        if (function_exists("bbp_update_forum")) {
            bbp_update_forum(["forum_id" => $new_forum_id]);
            bbp_update_forum(["forum_id" => $old_forum_id]);
        }

        $wpdb->query("COMMIT");

        do_action("bbps_topic_moved", $topic_id, $old_forum_id, $new_forum_id);

        $redirect_url = add_query_arg(
            "bbps_message",
            "moved",
            bbp_get_topic_permalink($topic_id)
        );
        wp_redirect($redirect_url);
        exit();
    } catch (Exception $e) {
        $wpdb->query("ROLLBACK");
        error_log("BBPS Move Topic Error: " . $e->getMessage());
        return false;
    }
}

function bbps_increment_post_count()
{
    $current_user = wp_get_current_user();
    $post_type = get_post_type();

    if ($post_type == "topic" || $post_type == "reply") {
        $user_id = $current_user->ID;
        $user_rank = get_user_meta($user_id, "_bbps_rank_info", true);

        if (empty($user_rank)) {
            bbps_create_user_ranking_meta($user_id);
        }

        bbps_check_ranking($user_id);
    }
}
add_action("save_post", "bbps_increment_post_count");

function bbps_check_ranking($user_id)
{
    $user_rank = get_user_meta($user_id, "_bbps_rank_info", true);

    if (!is_array($user_rank)) {
        $user_rank = ["post_count" => 0, "current_ranking" => ""];
    }

    $post_count = isset($user_rank["post_count"])
        ? $user_rank["post_count"]
        : 0;
    $current_rank = isset($user_rank["current_ranking"])
        ? $user_rank["current_ranking"]
        : "";
    $post_count = $post_count + 1;
    $rankings = get_option("bbps_reply_count");

    if (is_array($rankings)) {
        foreach ($rankings as $rank) {
            if (
                !is_array($rank) ||
                !isset($rank["end"], $rank["start"], $rank["title"])
            ) {
                continue;
            }

            if ($post_count - 1 == $rank["end"]) {
                $current_rank = "";
            }

            if ($post_count == $rank["start"]) {
                $current_rank = $rank["title"];
            }
        }
    }

    $meta = [
        "post_count" => $post_count,
        "current_ranking" => $current_rank,
    ];

    update_user_meta($user_id, "_bbps_rank_info", $meta);
}

function bbps_create_user_ranking_meta($user_id)
{
    $meta = [
        "post_count" => "0",
        "current_ranking" => "",
    ];

    update_user_meta($user_id, "_bbps_rank_info", $meta);
}

function bbps_display_user_title()
{
    if (get_option("bbps_enable_user_rank") == 1) {
        $user_id = bbp_get_reply_author_id();
        $user_rank = get_user_meta($user_id, "_bbps_rank_info", true);

        if (is_array($user_rank) && !empty($user_rank["current_ranking"])) {
            echo '<div id="bbps-user-title">' .
                esc_html($user_rank["current_ranking"]) .
                "</div>";
        }
    }
}

function bbps_display_user_post_count()
{
    if (get_option("bbps_enable_post_count") == 1) {
        $user_id = bbp_get_reply_author_id();
        $user_rank = get_user_meta($user_id, "_bbps_rank_info", true);

        if (is_array($user_rank) && !empty($user_rank["post_count"])) {
            echo '<div id="bbps-post-count">' .
                sprintf(
                    __("Post count: %s", "bbpress-support-toolkit"),
                    esc_html($user_rank["post_count"])
                ) .
                "</div>";
        }
    }
}

add_action("bbp_theme_after_reply_author_details", "bbps_display_user_title");
add_action(
    "bbp_theme_after_reply_author_details",
    "bbps_display_user_post_count"
);

function bbps_urgent_topic()
{
    $topic_id = intval($_GET["topic_id"]);
    if (!wp_verify_nonce($_GET["nonce"], "bbps_urgent_" . $topic_id)) {
        return;
    }
    if (
        !current_user_can("administrator") &&
        !current_user_can("bbp_moderator")
    ) {
        return;
    }
    update_post_meta($topic_id, "_bbps_urgent_topic", 1);
}

function bbps_not_urgent_topic()
{
    $topic_id = intval($_GET["topic_id"]);
    if (!wp_verify_nonce($_GET["nonce"], "bbps_urgent_" . $topic_id)) {
        return;
    }
    if (
        !current_user_can("administrator") &&
        !current_user_can("bbp_moderator")
    ) {
        return;
    }
    delete_post_meta($topic_id, "_bbps_urgent_topic");
}

function bbps_claim_topic()
{
    $user_id = intval($_GET["user_id"]);
    $topic_id = intval($_GET["topic_id"]);

    if (!wp_verify_nonce($_GET["nonce"], "bbps_claim_" . $topic_id)) {
        return;
    }

    if (
        !current_user_can("administrator") &&
        !current_user_can("bbp_moderator")
    ) {
        return;
    }

    bbp_add_user_subscription($user_id, $topic_id);
    update_post_meta($topic_id, "_bbps_topic_claimed", $user_id);
}

function bbps_unclaim_topic()
{
    $user_id = intval($_GET["user_id"]);
    $topic_id = intval($_GET["topic_id"]);

    if (!wp_verify_nonce($_GET["nonce"], "bbps_claim_" . $topic_id)) {
        return;
    }

    if (
        !current_user_can("administrator") &&
        !current_user_can("bbp_moderator")
    ) {
        return;
    }

    bbp_remove_user_subscription($user_id, $topic_id);
    delete_post_meta($topic_id, "_bbps_topic_claimed");
}

function bbps_is_new_topic($topic_id = 0)
{
    if (!bbps_is_new_topic_label_enabled()) {
        return false;
    }

    $topic_id = $topic_id ? $topic_id : bbp_get_topic_id();
    $days = get_option("bbps_new_topic_days", 30);
    $offset = $days * 24 * 60 * 60;
    
    return get_post_time('U', false, $topic_id) > (current_time('timestamp') - $offset);
}

function bbps_is_closed_topic($topic_id = 0)
{
    if (!bbps_is_closed_topic_label_enabled()) {
        return false;
    }

    $topic_id = $topic_id ? $topic_id : bbp_get_topic_id();
    return bbp_is_topic_closed($topic_id);
}

add_action("bbp_theme_before_topic_title", "bbps_modify_title");
function bbps_modify_title($title = "", $topic_id = 0)
{
    $topic_id = bbp_get_topic_id($topic_id);

    if (bbps_is_new_topic($topic_id)) {
        echo '<span class="label label-new">' .
            esc_html__("New", "bbpress-support-toolkit") .
            "</span>";
    }

    if (bbps_is_closed_topic($topic_id)) {
        echo '<span class="label label-closed">' .
            esc_html__("Closed", "bbpress-support-toolkit") .
            "</span>";
    }

    if (bbps_is_sticky_topic_label_enabled() && bbp_is_topic_sticky($topic_id) && !bbp_is_topic_closed($topic_id)) {
        echo '<span class="label label-sticky">' .
            esc_html__("Sticky", "bbpress-support-toolkit") .
            "</span>";
    }

    if (get_post_meta($topic_id, "_bbps_topic_status", true) == 2) {
        echo '<span class="label label-success">' .
            esc_html__("Resolved", "bbpress-support-toolkit") .
            "</span>";
    }

    if (get_post_meta($topic_id, "_bbps_topic_status", true) == 3) {
        echo '<span class="label label-secondary">' .
            esc_html__("Not Support Question", "bbpress-support-toolkit") .
            "</span>";
    }

    if (
        get_post_meta($topic_id, "_bbps_urgent_topic", true) == 1 &&
        (current_user_can("administrator") || current_user_can("bbp_moderator"))
    ) {
        echo '<span class="label label-warning">' .
            esc_html__("Urgent", "bbpress-support-toolkit") .
            "</span>";
    }

    if (get_post_meta($topic_id, "_bbps_topic_claimed", true) > 0) {
        if (get_option("bbps_claim_topic_display") == 1) {
            $claimed_user_id = get_post_meta(
                $topic_id,
                "_bbps_topic_claimed",
                true
            );
            $user_info = get_userdata($claimed_user_id);
            if ($user_info) {
                echo '<span class="label label-info">[' .
                    esc_html($user_info->user_login) .
                    "]</span>";
            }
        } else {
            echo '<span class="label label-info">' .
                esc_html__("Claimed", "bbpress-support-toolkit") .
                "</span>";
        }
    }
}

if (bbps_is_post_author_label_enabled()) {
    add_action("bbp_theme_after_reply_author_details", "bbps_display_post_author_label");
}

function bbps_display_post_author_label()
{
    $topic_author_id = bbp_get_topic_author_id();
    $reply_author_id = bbp_get_reply_author_id();
    
    if ($topic_author_id == $reply_author_id) {
        echo '<div class="post-starter-label">' .
            esc_html__("Original Poster", "bbpress-support-toolkit") .
            "</div>";
    }
}

if (bbps_is_search_integration_enabled()) {
    add_filter("pre_get_posts", "bbps_include_forum_posts_in_search");
    add_filter("the_excerpt", "bbps_enhance_search_excerpt");
    add_filter("get_the_excerpt", "bbps_enhance_search_excerpt");
}

function bbps_include_forum_posts_in_search($query)
{
    if (!is_admin() && $query->is_main_query() && $query->is_search()) {
        $current_types = $query->get('post_type');
        
        if (empty($current_types)) {
            $current_types = array('post');
        } else if (!is_array($current_types)) {
            $current_types = array($current_types);
        }
        
        if (post_type_exists('topic') && !in_array('topic', $current_types)) {
            $current_types[] = 'topic';
        }
        
        if (post_type_exists('reply') && !in_array('reply', $current_types)) {
            $current_types[] = 'reply';
        }
        
        if (!get_option("bbps_include_posts_in_search") && in_array('post', $current_types)) {
            $current_types = array_diff($current_types, array('post'));
        }
        
        $query->set('post_type', $current_types);
        $query->set('posts_per_page', get_option("bbps_search_results_count", 20));
        
        $meta_query = array(
            'relation' => 'AND',
            array(
                'relation' => 'OR',
                array(
                    'key' => '_bbp_forum_id',
                    'compare' => 'EXISTS'
                ),
                array(
                    'key' => '_bbp_forum_id',
                    'compare' => 'NOT EXISTS'
                )
            )
        );
        
        if (!current_user_can('administrator') && !current_user_can('bbp_moderator')) {
            $premium_forums = get_posts(array(
                'post_type' => 'forum',
                'meta_key' => '_bbps_is_premium',
                'meta_value' => '1',
                'fields' => 'ids',
                'posts_per_page' => -1
            ));
            
            if (!empty($premium_forums)) {
                $meta_query[] = array(
                    'key' => '_bbp_forum_id',
                    'value' => $premium_forums,
                    'compare' => 'NOT IN'
                );
            }
        }
        
        $query->set('meta_query', $meta_query);
        $query->set('orderby', array('post_type' => 'ASC', 'date' => 'DESC'));
    }
    
    return $query;
}

function bbps_enhance_search_excerpt($excerpt)
{
    if (is_search()) {
        global $post;
        
        if ($post->post_type == 'topic') {
            $forum_title = get_the_title($post->post_parent);
            $excerpt = '<strong>' . __('Topic in:', 'bbpress-support-toolkit') . '</strong> ' . $forum_title . '<br>' . $excerpt;
        } elseif ($post->post_type == 'reply') {
            $topic_id = get_post_meta($post->ID, '_bbp_topic_id', true);
            $topic_title = get_the_title($topic_id);
            $excerpt = '<strong>' . __('Reply in:', 'bbpress-support-toolkit') . '</strong> ' . $topic_title . '<br>' . $excerpt;
        }
    }
    
    return $excerpt;
}

if (bbps_is_private_replies_enabled()) {
    add_filter("bbp_get_reply_content", "bbps_filter_private_replies", 10, 2);
    add_action("bbp_theme_before_reply_form_content", "bbps_add_private_reply_checkbox");
    add_action("bbp_new_reply", "bbps_save_private_reply_meta");
}

function bbps_filter_private_replies($content, $reply_id)
{
    if (get_post_meta($reply_id, "_bbps_private_reply", true)) {
        $topic_id = bbp_get_reply_topic_id($reply_id);
        $topic_author_id = bbp_get_topic_author_id($topic_id);
        $reply_author_id = bbp_get_reply_author_id($reply_id);
        $current_user_id = get_current_user_id();
        
        $can_view = (
            $current_user_id == $topic_author_id ||
            $current_user_id == $reply_author_id ||
            current_user_can(bbps_get_private_replies_capability())
        );
        
        if (!$can_view) {
            return '<em>' . esc_html__("This reply is private.", "bbpress-support-toolkit") . '</em>';
        }
    }
    
    return $content;
}

function bbps_add_private_reply_checkbox()
{
    if (!is_user_logged_in()) {
        return;
    }
    
    ?>
    <p>
        <input type="checkbox" name="bbps_private_reply" id="bbps_private_reply" value="1" />
        <label for="bbps_private_reply"><?php esc_html_e("Make this reply private", "bbpress-support-toolkit"); ?></label>
        <br />
        <small><?php esc_html_e("Private replies are only visible to you, the topic author, and moderators.", "bbpress-support-toolkit"); ?></small>
    </p>
    <?php
}

function bbps_save_private_reply_meta($reply_id)
{
    if (isset($_POST["bbps_private_reply"]) && $_POST["bbps_private_reply"] == "1") {
        update_post_meta($reply_id, "_bbps_private_reply", 1);
    }
}

if (bbps_is_search_url_rewrite_enabled()) {
    add_action("template_redirect", "bbps_change_search_url_rewrite");
    add_filter("get_search_form", "bbps_modify_search_form");
}

function bbps_change_search_url_rewrite()
{
    if (is_search() && !empty($_GET['s'])) {
        wp_redirect(home_url("/search/") . urlencode(get_query_var('s')));
        exit();
    }
}

function bbps_modify_search_form($form)
{
    $form = str_replace('method="get"', 'method="get" onsubmit="return bbps_redirect_search(this);"', $form);
    $form .= '<script>
        function bbps_redirect_search(form) {
            var searchTerm = form.querySelector(\'input[name="s"]\').value;
            if (searchTerm) {
                window.location.href = "' . home_url('/search/') . '" + encodeURIComponent(searchTerm);
                return false;
            }
            return true;
        }
    </script>';
    return $form;
}