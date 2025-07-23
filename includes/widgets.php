<?php

if (!defined("ABSPATH")) {
    exit();
}

class BBPS_Support_Hours_Widget extends WP_Widget
{
    function __construct()
    {
        $widget_ops = [
            "classname" => "bbps_support_hours_widget",
            "description" => __(
                "Set your support times for your support forum - these will be displayed to your posters",
                "bbpress-support-toolkit",
            ),
        ];

        parent::__construct(
            "bbps_support_hours_widget",
            __("Forum Support Hours", "bbpress-support-toolkit"),
            $widget_ops,
        );
    }

    function form($instance)
    {
        $defaults = [
            "title" => __("Support Hours", "bbpress-support-toolkit"),
            "open_time" => "",
            "open_img" => "",
            "close_time" => "",
            "close_img" => "",
            "clock_html" => "",
            "forum_closed" => "",
            "forum_open_text" => __(
                "Our forums are open",
                "bbpress-support-toolkit",
            ),
            "forum_closed_text" => __(
                "Our forums are closed",
                "bbpress-support-toolkit",
            ),
            "closed_weekends" => "",
            "display_hours" => "",
        ];

        $instance = wp_parse_args((array) $instance, $defaults);
        ?>
        <p>
            <label for="<?php echo $this->get_field_id(
                "title",
            ); ?>"><?php esc_html_e("Title:", "bbpress-support-toolkit"); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id(
                "title",
            ); ?>" name="<?php echo $this->get_field_name("title"); ?>" type="text" value="<?php echo esc_attr($instance["title"]); ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id(
                "open_time",
            ); ?>"><?php esc_html_e("Open Time:", "bbpress-support-toolkit"); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id(
                "open_time",
            ); ?>" name="<?php echo $this->get_field_name("open_time"); ?>" type="text" value="<?php echo esc_attr($instance["open_time"]); ?>" />
            <small><?php esc_html_e(
                "Please enter the opening time for your support forum in 24 hour format eg: 9am 09:00",
                "bbpress-support-toolkit",
            ); ?></small>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id(
                "close_time",
            ); ?>"><?php esc_html_e("Close Time:", "bbpress-support-toolkit"); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id(
                "close_time",
            ); ?>" name="<?php echo $this->get_field_name("close_time"); ?>" type="text" value="<?php echo esc_attr($instance["close_time"]); ?>" />
            <small><?php esc_html_e(
                "Please enter the closing time for your support forum in 24 hour format eg: 5pm 17:00",
                "bbpress-support-toolkit",
            ); ?></small>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id(
                "open_img",
            ); ?>"><?php esc_html_e("Open Image:", "bbpress-support-toolkit"); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id(
                "open_img",
            ); ?>" name="<?php echo $this->get_field_name("open_img"); ?>" type="text" value="<?php echo esc_attr($instance["open_img"]); ?>" />
            <small><?php printf(
                esc_html__(
                    "Place your opening image into the following directory: %s then enter the name of your opening image here, please be careful to spell it correctly and add the file extension. eg openimage.png",
                    "bbpress-support-toolkit",
                ),
                "<strong>" . BBPS_ASSETS_PATH . "images</strong>",
            ); ?></small>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id(
                "close_img",
            ); ?>"><?php esc_html_e("Close Image:", "bbpress-support-toolkit"); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id(
                "close_img",
            ); ?>" name="<?php echo $this->get_field_name("close_img"); ?>" type="text" value="<?php echo esc_attr($instance["close_img"]); ?>" />
            <small><?php printf(
                esc_html__(
                    "Place your closing image into the following directory: %s then enter the name of your closing image here, please be careful to spell it correctly and add the file extension. eg closeimage.png",
                    "bbpress-support-toolkit",
                ),
                "<strong>" . BBPS_ASSETS_PATH . "images</strong>",
            ); ?></small>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id(
                "forum_open_text",
            ); ?>"><?php esc_html_e("Open Text:", "bbpress-support-toolkit"); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id(
                "forum_open_text",
            ); ?>" name="<?php echo $this->get_field_name("forum_open_text"); ?>" type="text" value="<?php echo esc_attr($instance["forum_open_text"]); ?>" />
            <small><?php esc_html_e(
                'This will be displayed to your users when the forums are open. This text has a class of "forum_text" if you would like to style it differently',
                "bbpress-support-toolkit",
            ); ?></small>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id(
                "forum_closed_text",
            ); ?>"><?php esc_html_e("Closed Text:", "bbpress-support-toolkit"); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id(
                "forum_closed_text",
            ); ?>" name="<?php echo $this->get_field_name("forum_closed_text"); ?>" type="text" value="<?php echo esc_attr($instance["forum_closed_text"]); ?>" />
            <small><?php esc_html_e(
                'This will be displayed to your users when the forums are closed. This text has a class of "forum_text" if you would like to style it differently',
                "bbpress-support-toolkit",
            ); ?></small>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id(
                "clock_html",
            ); ?>"><?php esc_html_e("Clock HTML:", "bbpress-support-toolkit"); ?></label>
            <textarea class="widefat" id="<?php echo $this->get_field_id(
                "clock_html",
            ); ?>" name="<?php echo $this->get_field_name("clock_html"); ?>"><?php echo esc_textarea($instance["clock_html"]); ?></textarea>
            <small><?php printf(
                esc_html__(
                    "If you would like to display a clock showing the time in your current time zone head over %s and make one, copy the code in the text area above and we will do the rest!",
                    "bbpress-support-toolkit",
                ),
                '<a href="http://www.timeanddate.com/clocks/free.html" target="_blank">here</a>',
            ); ?></small>
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked(
                $instance["closed_weekends"],
                "on",
            ); ?> id="<?php echo $this->get_field_id("closed_weekends"); ?>" name="<?php echo $this->get_field_name("closed_weekends"); ?>" />
            <label for="<?php echo $this->get_field_id(
                "closed_weekends",
            ); ?>"><?php esc_html_e("Forum Closed on Weekends?", "bbpress-support-toolkit"); ?></label>
            <small><?php esc_html_e(
                "Select this if your forum is closed on the weekends",
                "bbpress-support-toolkit",
            ); ?></small>
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked(
                $instance["forum_closed"],
                "on",
            ); ?> id="<?php echo $this->get_field_id("forum_closed"); ?>" name="<?php echo $this->get_field_name("forum_closed"); ?>" />
            <label for="<?php echo $this->get_field_id(
                "forum_closed",
            ); ?>"><?php esc_html_e("Forum Closed:", "bbpress-support-toolkit"); ?></label>
            <small><?php esc_html_e(
                "Checking this box turns your widget into closed mode until you uncheck it - perfect if you are away on holiday and not maintaining your forums.",
                "bbpress-support-toolkit",
            ); ?></small>
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked(
                $instance["display_hours"],
                "on",
            ); ?> id="<?php echo $this->get_field_id("display_hours"); ?>" name="<?php echo $this->get_field_name("display_hours"); ?>" />
            <label for="<?php echo $this->get_field_id(
                "display_hours",
            ); ?>"><?php esc_html_e("Display forum hours:", "bbpress-support-toolkit"); ?></label>
            <small><?php esc_html_e(
                "Select this if you would like to display your forum hours in the widget.",
                "bbpress-support-toolkit",
            ); ?></small>
        </p>
        <?php
    }

    function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $instance["title"] = sanitize_text_field($new_instance["title"]);
        $instance["open_time"] = sanitize_text_field(
            $new_instance["open_time"],
        );
        $instance["open_img"] = sanitize_text_field($new_instance["open_img"]);
        $instance["close_time"] = sanitize_text_field(
            $new_instance["close_time"],
        );
        $instance["close_img"] = sanitize_text_field(
            $new_instance["close_img"],
        );
        $instance["clock_html"] = wp_kses_post($new_instance["clock_html"]);
        $instance["forum_closed"] = sanitize_text_field(
            $new_instance["forum_closed"],
        );
        $instance["forum_closed_text"] = sanitize_text_field(
            $new_instance["forum_closed_text"],
        );
        $instance["forum_open_text"] = sanitize_text_field(
            $new_instance["forum_open_text"],
        );
        $instance["display_hours"] = sanitize_text_field(
            $new_instance["display_hours"],
        );
        $instance["closed_weekends"] = sanitize_text_field(
            $new_instance["closed_weekends"],
        );

        return $instance;
    }

    function widget($args, $instance)
    {
        extract($args);

        echo $before_widget;
        $title = apply_filters("widget_title", $instance["title"]);
        $open_time = empty($instance["open_time"])
            ? ""
            : $instance["open_time"];
        $open_img = empty($instance["open_img"]) ? "" : $instance["open_img"];
        $close_time = empty($instance["close_time"])
            ? ""
            : $instance["close_time"];
        $close_img = empty($instance["close_img"])
            ? ""
            : $instance["close_img"];
        $clock_html = empty($instance["clock_html"])
            ? ""
            : $instance["clock_html"];
        $forum_closed = empty($instance["forum_closed"])
            ? ""
            : $instance["forum_closed"];
        $forum_closed_text = empty($instance["forum_closed_text"])
            ? __("Our forums are closed", "bbpress-support-toolkit")
            : $instance["forum_closed_text"];
        $forum_open_text = empty($instance["forum_open_text"])
            ? __("Our forums are open", "bbpress-support-toolkit")
            : $instance["forum_open_text"];
        $display_hours = empty($instance["display_hours"])
            ? ""
            : $instance["display_hours"];
        $closed_weekends = empty($instance["closed_weekends"])
            ? ""
            : $instance["closed_weekends"];

        $gmt = 0;
        $closed = false;
        $open = false;

        $time = current_time("H:i");
        $day = current_time("l");
        $time = str_replace(":", "", $time);
        $open_time_raw = str_replace(":", "", $open_time);
        $close_time_raw = str_replace(":", "", $close_time);

        echo '<div id="supportwrapper">';
        if (!empty($title)) {
            echo $before_title . $title . $after_title;
        }

        if (
            $forum_closed == "on" ||
            ($closed_weekends == "on" &&
                ($day == "Saturday" || $day == "Sunday"))
        ) {
            $closed = true;
        } else {
            if (
                ($open_time_raw < $close_time_raw &&
                    ($time >= $open_time_raw && !($time >= $close_time_raw))) ||
                ($open_time_raw > $close_time_raw &&
                    ($time >= $open_time_raw && !($time <= $close_time_raw)))
            ) {
                $open = true;
            } else {
                $closed = true;
            }
        }

        if ($open == true) {
            echo '<span class="forum_text"><span class="green">' .
                esc_html($forum_open_text) .
                "</span></span><br />";
            if ($open_img != "") {
                echo '<img src="' .
                    esc_url(BBPS_ASSETS_URL . "images/" . $open_img) .
                    '" alt="' .
                    esc_attr__("Forum Open", "bbpress-support-toolkit") .
                    '">';
            }
            if ($display_hours == "on") {
                echo '<div class="forum_hours">' .
                    sprintf(
                        esc_html__(
                            "Our office hours are: %s - %s",
                            "bbpress-support-toolkit",
                        ),
                        bbps_format_time($open_time_raw),
                        bbps_format_time($close_time_raw),
                    ) .
                    "</div>";
            }
        }

        if ($closed == true) {
            echo '<span class="forum_text"><span class="red">' .
                esc_html($forum_closed_text) .
                "</span></span><br />";
            if ($close_img != "") {
                echo '<img src="' .
                    esc_url(BBPS_ASSETS_URL . "images/" . $close_img) .
                    '" alt="' .
                    esc_attr__("Forum Closed", "bbpress-support-toolkit") .
                    '">';
            }
            if ($display_hours == "on") {
                echo '<div class="forum_hours">' .
                    sprintf(
                        esc_html__(
                            "Our office hours are: %s - %s",
                            "bbpress-support-toolkit",
                        ),
                        bbps_format_time($open_time_raw),
                        bbps_format_time($close_time_raw),
                    ) .
                    "</div>";
            }
        }

        echo '<div id="html_clock">' .
            wp_kses_post($clock_html) .
            "</div></div>";
        echo $after_widget;
    }
}

function bbps_format_time($raw_time)
{
    if (empty($raw_time)) {
        return "";
    }

    $raw_time = intval($raw_time);

    if ($raw_time == 2400) {
        return "12:00 AM";
    }

    if ($raw_time > 1300 && $raw_time < 2400) {
        $format_time = $raw_time - 1200;
        $time_length = str_pad($format_time, 4, "0", STR_PAD_LEFT);
        return substr($time_length, 0, 2) .
            ":" .
            substr($time_length, 2, 2) .
            " PM";
    } else {
        $time_length = str_pad($raw_time, 4, "0", STR_PAD_LEFT);
        return substr($time_length, 0, 2) .
            ":" .
            substr($time_length, 2, 2) .
            " AM";
    }
}

class BBPS_Resolved_Count_Widget extends WP_Widget
{
    function __construct()
    {
        $widget_ops = [
            "classname" => "bbps_resolved_count_widget",
            "description" => __(
                "Display a count of resolved topics in your forum",
                "bbpress-support-toolkit",
            ),
        ];

        parent::__construct(
            "bbps_resolved_count_widget",
            __("Resolved Topic Count", "bbpress-support-toolkit"),
            $widget_ops,
        );
    }

    function form($instance)
    {
        $defaults = [
            "title" => __("Resolved Topics", "bbpress-support-toolkit"),
            "show_total" => "",
            "show_resolved" => "",
            "text_before_total" => "",
            "text_after_total" => "",
            "text_before_resolved" => "",
            "text_after_resolved" => "",
        ];

        $instance = wp_parse_args((array) $instance, $defaults);
        ?>
        <p>
            <label for="<?php echo $this->get_field_id(
                "title",
            ); ?>"><?php esc_html_e("Title:", "bbpress-support-toolkit"); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id(
                "title",
            ); ?>" name="<?php echo $this->get_field_name("title"); ?>" type="text" value="<?php echo esc_attr($instance["title"]); ?>" />
        </p>

        <p>
            <input class="checkbox" type="checkbox" <?php checked(
                $instance["show_total"],
                "on",
            ); ?> id="<?php echo $this->get_field_id("show_total"); ?>" name="<?php echo $this->get_field_name("show_total"); ?>" />
            <label for="<?php echo $this->get_field_id(
                "show_total",
            ); ?>"><?php esc_html_e("Display Total Topic Count", "bbpress-support-toolkit"); ?></label>
        </p>

        <p>
            <input class="checkbox" type="checkbox" <?php checked(
                $instance["show_resolved"],
                "on",
            ); ?> id="<?php echo $this->get_field_id("show_resolved"); ?>" name="<?php echo $this->get_field_name("show_resolved"); ?>" />
            <label for="<?php echo $this->get_field_id(
                "show_resolved",
            ); ?>"><?php esc_html_e("Display Resolved Topic Count", "bbpress-support-toolkit"); ?></label>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id(
                "text_before_total",
            ); ?>"><?php esc_html_e("Text Before Total:", "bbpress-support-toolkit"); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id(
                "text_before_total",
            ); ?>" name="<?php echo $this->get_field_name("text_before_total"); ?>" type="text" value="<?php echo esc_attr($instance["text_before_total"]); ?>" />
        </p>

        <p>
            <label for="<?php echo $this->get_field_id(
                "text_after_total",
            ); ?>"><?php esc_html_e("Text After Total:", "bbpress-support-toolkit"); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id(
                "text_after_total",
            ); ?>" name="<?php echo $this->get_field_name("text_after_total"); ?>" type="text" value="<?php echo esc_attr($instance["text_after_total"]); ?>" />
        </p>

        <p>
            <label for="<?php echo $this->get_field_id(
                "text_before_resolved",
            ); ?>"><?php esc_html_e("Text Before Resolved Total:", "bbpress-support-toolkit"); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id(
                "text_before_resolved",
            ); ?>" name="<?php echo $this->get_field_name("text_before_resolved"); ?>" type="text" value="<?php echo esc_attr($instance["text_before_resolved"]); ?>" />
        </p>

        <p>
            <label for="<?php echo $this->get_field_id(
                "text_after_resolved",
            ); ?>"><?php esc_html_e("Text After Resolved Total:", "bbpress-support-toolkit"); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id(
                "text_after_resolved",
            ); ?>" name="<?php echo $this->get_field_name("text_after_resolved"); ?>" type="text" value="<?php echo esc_attr($instance["text_after_resolved"]); ?>" />
        </p>
        <?php
    }

    function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $instance["title"] = sanitize_text_field($new_instance["title"]);
        $instance["show_total"] = sanitize_text_field(
            $new_instance["show_total"],
        );
        $instance["show_resolved"] = sanitize_text_field(
            $new_instance["show_resolved"],
        );
        $instance["text_before_total"] = sanitize_text_field(
            $new_instance["text_before_total"],
        );
        $instance["text_after_total"] = sanitize_text_field(
            $new_instance["text_after_total"],
        );
        $instance["text_before_resolved"] = sanitize_text_field(
            $new_instance["text_before_resolved"],
        );
        $instance["text_after_resolved"] = sanitize_text_field(
            $new_instance["text_after_resolved"],
        );

        return $instance;
    }

    function widget($args, $instance)
    {
        extract($args);

        echo $before_widget;
        $title = apply_filters("widget_title", $instance["title"]);

        $text_before_total = empty($instance["text_before_total"])
            ? ""
            : $instance["text_before_total"];
        $text_after_total = empty($instance["text_after_total"])
            ? ""
            : $instance["text_after_total"];
        $text_before_resolved = empty($instance["text_before_resolved"])
            ? ""
            : $instance["text_before_resolved"];
        $text_after_resolved = empty($instance["text_after_resolved"])
            ? ""
            : $instance["text_after_resolved"];
        $total_resolved = bbps_get_resolved_count();
        $total_topics = bbps_get_topic_count();

        if (!empty($title)) {
            echo $before_title . $title . $after_title;
        }

        echo esc_html($text_before_total) . " ";
        if ($instance["show_total"] == "on") {
            echo esc_html($total_topics) . " ";
        }
        echo esc_html($text_after_total) . "<br />";

        echo esc_html($text_before_resolved) . " ";
        if ($instance["show_resolved"] == "on") {
            echo esc_html($total_resolved) . " ";
        }
        echo esc_html($text_after_resolved) . " ";

        echo $after_widget;
    }
}

function bbps_get_resolved_count()
{
    global $wpdb;
    $resolved_query = $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE `meta_key` = %s AND `meta_value` = %s",
        "_bbps_topic_status",
        "2",
    );
    return (int) $wpdb->get_var($resolved_query);
}

function bbps_get_topic_count()
{
    global $wpdb;
    $topic_query = $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} WHERE `post_type` = %s AND `post_status` = %s",
        "topic",
        "publish",
    );
    return (int) $wpdb->get_var($topic_query);
}

class BBPS_Urgent_Topics_Widget extends WP_Widget
{
    function __construct()
    {
        $widget_ops = [
            "classname" => "bbps_urgent_topics_widget",
            "description" => __(
                "Display a list of urgent topics in your forum",
                "bbpress-support-toolkit",
            ),
        ];

        parent::__construct(
            "bbps_urgent_topics_widget",
            __("Urgent Topics", "bbpress-support-toolkit"),
            $widget_ops,
        );
    }

    function form($instance)
    {
        $defaults = [
            "title" => __("Urgent Topics", "bbpress-support-toolkit"),
            "show_urgent_list_admin" => "",
            "show_urgent_list_mod" => "",
            "show_urgent_list_user" => "",
        ];

        $instance = wp_parse_args((array) $instance, $defaults);
        ?>
        <p>
            <label for="<?php echo $this->get_field_id(
                "title",
            ); ?>"><?php esc_html_e("Title:", "bbpress-support-toolkit"); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id(
                "title",
            ); ?>" name="<?php echo $this->get_field_name("title"); ?>" type="text" value="<?php echo esc_attr($instance["title"]); ?>" />
        </p>
        <p><?php esc_html_e(
            "Select the user types who will be able to see the list of urgent topics",
            "bbpress-support-toolkit",
        ); ?></p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked(
                $instance["show_urgent_list_admin"],
                "on",
            ); ?> id="<?php echo $this->get_field_id("show_urgent_list_admin"); ?>" name="<?php echo $this->get_field_name("show_urgent_list_admin"); ?>" />
            <label for="<?php echo $this->get_field_id(
                "show_urgent_list_admin",
            ); ?>"><?php esc_html_e("Administrators", "bbpress-support-toolkit"); ?></label>
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked(
                $instance["show_urgent_list_mod"],
                "on",
            ); ?> id="<?php echo $this->get_field_id("show_urgent_list_mod"); ?>" name="<?php echo $this->get_field_name("show_urgent_list_mod"); ?>" />
            <label for="<?php echo $this->get_field_id(
                "show_urgent_list_mod",
            ); ?>"><?php esc_html_e("Moderators", "bbpress-support-toolkit"); ?></label>
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked(
                $instance["show_urgent_list_user"],
                "on",
            ); ?> id="<?php echo $this->get_field_id("show_urgent_list_user"); ?>" name="<?php echo $this->get_field_name("show_urgent_list_user"); ?>" />
            <label for="<?php echo $this->get_field_id(
                "show_urgent_list_user",
            ); ?>"><?php esc_html_e("Site Users", "bbpress-support-toolkit"); ?></label>
        </p>
        <?php
    }

    function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $instance["title"] = sanitize_text_field($new_instance["title"]);
        $instance["show_urgent_list_admin"] = sanitize_text_field(
            $new_instance["show_urgent_list_admin"],
        );
        $instance["show_urgent_list_mod"] = sanitize_text_field(
            $new_instance["show_urgent_list_mod"],
        );
        $instance["show_urgent_list_user"] = sanitize_text_field(
            $new_instance["show_urgent_list_user"],
        );

        return $instance;
    }

    function widget($args, $instance)
    {
        extract($args);

        if (
            ($instance["show_urgent_list_admin"] == "on" &&
                current_user_can("administrator")) ||
            ($instance["show_urgent_list_mod"] == "on" &&
                current_user_can("bbp_moderator")) ||
            ($instance["show_urgent_list_user"] == "on" && is_user_logged_in())
        ) {
            echo $before_widget;
            $title = apply_filters("widget_title", $instance["title"]);
            if (!empty($title)) {
                echo $before_title . $title . $after_title;
            }
            bbps_get_urgent_topic_list();
            echo $after_widget;
        }
    }
}

function bbps_get_urgent_topic_list()
{
    global $wpdb;

    $urgent_query = $wpdb->prepare(
        "SELECT `post_id` FROM {$wpdb->postmeta} WHERE `meta_key` = %s AND `meta_value` = %s ORDER BY `meta_id` ASC",
        "_bbps_urgent_topic",
        "1",
    );
    $urgent_topics = $wpdb->get_col($urgent_query);

    echo "<ul>";
    foreach ((array) $urgent_topics as $urgent_topic) {
        $post = get_post($urgent_topic);
        if ($post && $post->post_status === "publish") {
            echo '<li><a href="' .
                esc_url(get_permalink($urgent_topic)) .
                '"> ' .
                esc_html(get_the_title($urgent_topic)) .
                "</a></li>";
        }
    }
    echo "</ul>";
}

class BBPS_Recently_Resolved_Widget extends WP_Widget
{
    function __construct()
    {
        $widget_ops = [
            "classname" => "bbps_recently_resolved_widget",
            "description" => __(
                "Display a list of recently resolved topics in your forum",
                "bbpress-support-toolkit",
            ),
        ];

        parent::__construct(
            "bbps_recently_resolved_widget",
            __("Recently Resolved", "bbpress-support-toolkit"),
            $widget_ops,
        );
    }

    function form($instance)
    {
        $defaults = [
            "title" => __("Recently Resolved", "bbpress-support-toolkit"),
            "number_of_topics" => "10",
        ];

        $instance = wp_parse_args((array) $instance, $defaults);
        ?>
        <p>
            <label for="<?php echo $this->get_field_id(
                "title",
            ); ?>"><?php esc_html_e("Title:", "bbpress-support-toolkit"); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id(
                "title",
            ); ?>" name="<?php echo $this->get_field_name("title"); ?>" type="text" value="<?php echo esc_attr($instance["title"]); ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id(
                "number_of_topics",
            ); ?>"><?php esc_html_e("Topics to show:", "bbpress-support-toolkit"); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id(
                "number_of_topics",
            ); ?>" name="<?php echo $this->get_field_name("number_of_topics"); ?>" type="number" min="1" max="50" value="<?php echo esc_attr($instance["number_of_topics"]); ?>" />
            <small><?php esc_html_e(
                "How many resolved topics would you like to display?",
                "bbpress-support-toolkit",
            ); ?></small>
        </p>
        <?php
    }

    function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $instance["title"] = sanitize_text_field($new_instance["title"]);
        $instance["number_of_topics"] = absint(
            $new_instance["number_of_topics"],
        );
        return $instance;
    }

    function widget($args, $instance)
    {
        extract($args);
        $number_topics = isset($instance["number_of_topics"])
            ? absint($instance["number_of_topics"])
            : 10;

        echo $before_widget;
        $title = apply_filters("widget_title", $instance["title"]);
        if (!empty($title)) {
            echo $before_title . $title . $after_title;
        }
        bbps_get_resolved_topic_list($number_topics);
        echo $after_widget;
    }
}

function bbps_get_resolved_topic_list($number_topics)
{
    global $wpdb;

    $resolved_query = $wpdb->prepare(
        "SELECT `meta_id`, `post_id` FROM {$wpdb->postmeta} WHERE `meta_key` = %s AND `meta_value` = %s ORDER BY `meta_id` DESC LIMIT %d",
        "_bbps_topic_status",
        "2",
        $number_topics,
    );

    $resolved_topics = $wpdb->get_results($resolved_query);

    echo "<ul>";
    foreach ((array) $resolved_topics as $resolved_topic) {
        $post = get_post($resolved_topic->post_id);
        if ($post && $post->post_status === "publish") {
            echo '<li><a href="' .
                esc_url(get_permalink($resolved_topic->post_id)) .
                '"> ' .
                esc_html(get_the_title($resolved_topic->post_id)) .
                "</a></li>";
        }
    }
    echo "</ul>";
}

class BBPS_Claimed_Topics_Widget extends WP_Widget
{
    function __construct()
    {
        $widget_ops = [
            "classname" => "bbps_claimed_topics_widget",
            "description" => __(
                "Display a list of the users claimed topics",
                "bbpress-support-toolkit",
            ),
        ];

        parent::__construct(
            "bbps_claimed_topics_widget",
            __("Claimed Topics", "bbpress-support-toolkit"),
            $widget_ops,
        );
    }

    function form($instance)
    {
        $defaults = [
            "title" => __("My Claimed Topics", "bbpress-support-toolkit"),
            "number_of_claimed_topics" => "10",
        ];

        $instance = wp_parse_args((array) $instance, $defaults);
        ?>
        <p>
            <label for="<?php echo $this->get_field_id(
                "title",
            ); ?>"><?php esc_html_e("Title:", "bbpress-support-toolkit"); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id(
                "title",
            ); ?>" name="<?php echo $this->get_field_name("title"); ?>" type="text" value="<?php echo esc_attr($instance["title"]); ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id(
                "number_of_claimed_topics",
            ); ?>"><?php esc_html_e("Topics to show:", "bbpress-support-toolkit"); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id(
                "number_of_claimed_topics",
            ); ?>" name="<?php echo $this->get_field_name("number_of_claimed_topics"); ?>" type="number" min="1" max="50" value="<?php echo esc_attr($instance["number_of_claimed_topics"]); ?>" />
            <small><?php esc_html_e(
                "How many claimed topics would you like to display?",
                "bbpress-support-toolkit",
            ); ?></small>
        </p>
        <?php
    }

    function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $instance["title"] = sanitize_text_field($new_instance["title"]);
        $instance["number_of_claimed_topics"] = absint(
            $new_instance["number_of_claimed_topics"],
        );
        return $instance;
    }

    function widget($args, $instance)
    {
        if (!is_user_logged_in()) {
            return;
        }

        extract($args);
        $number_claimed_topics = isset($instance["number_of_claimed_topics"])
            ? absint($instance["number_of_claimed_topics"])
            : 10;

        echo $before_widget;
        $title = apply_filters("widget_title", $instance["title"]);
        if (!empty($title)) {
            echo $before_title . $title . $after_title;
        }
        bbps_get_users_claimed_topics($number_claimed_topics);
        echo $after_widget;
    }
}

function bbps_get_users_claimed_topics($number_claimed_topics)
{
    global $wpdb;

    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;

    if (!$user_id) {
        echo "<p>" .
            esc_html__(
                "Please log in to view your claimed topics.",
                "bbpress-support-toolkit",
            ) .
            "</p>";
        return;
    }

    $claimed_query = $wpdb->prepare(
        "SELECT `meta_id`, `post_id` FROM {$wpdb->postmeta} WHERE `meta_key` = %s AND `meta_value` = %s ORDER BY `meta_id` DESC LIMIT %d",
        "_bbps_topic_claimed",
        $user_id,
        $number_claimed_topics,
    );
    $claimed_topics = $wpdb->get_results($claimed_query);

    if (empty($claimed_topics)) {
        echo "<p>" .
            esc_html__(
                "You have not claimed any topics yet.",
                "bbpress-support-toolkit",
            ) .
            "</p>";
        return;
    }

    echo "<ul>";
    foreach ((array) $claimed_topics as $claimed_topic) {
        $post = get_post($claimed_topic->post_id);
        if ($post && $post->post_status === "publish") {
            echo '<li><a href="' .
                esc_url(get_permalink($claimed_topic->post_id)) .
                '"> ' .
                esc_html(get_the_title($claimed_topic->post_id)) .
                "</a></li>";
        }
    }
    echo "</ul>";
}

class BBPS_Register_Widget extends WP_Widget
{
    function __construct()
    {
        $widget_ops = [
            "classname" => "bbps_register_widget",
            "description" => __(
                "Display a registration form for your forum",
                "bbpress-support-toolkit",
            ),
        ];

        parent::__construct(
            "bbps_register_widget",
            __("Forum Registration", "bbpress-support-toolkit"),
            $widget_ops,
        );
    }

    function form($instance)
    {
        $defaults = [
            "title" => __("Forum Registration", "bbpress-support-toolkit"),
        ];
        $instance = wp_parse_args((array) $instance, $defaults);
        ?>
        <p>
            <label for="<?php echo $this->get_field_id(
                "title",
            ); ?>"><?php esc_html_e("Title:", "bbpress-support-toolkit"); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id(
                "title",
            ); ?>" name="<?php echo $this->get_field_name("title"); ?>" type="text" value="<?php echo esc_attr($instance["title"]); ?>" />
        </p>
        <?php
    }

    function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $instance["title"] = sanitize_text_field($new_instance["title"]);
        return $instance;
    }

    function widget($args, $instance)
    {
        if (is_user_logged_in()) {
            return;
        }

        extract($args);
        echo $before_widget;
        $title = apply_filters("widget_title", $instance["title"]);
        if (!empty($title)) {
            echo $before_title . $title . $after_title;
        }

        $register_link = wp_registration_url();
        ?>
        <p><?php esc_html_e(
            "Join our forum community!",
            "bbpress-support-toolkit",
        ); ?></p>
        <a href="<?php echo esc_url(
            $register_link,
        ); ?>" class="button"><?php esc_html_e("Register Now", "bbpress-support-toolkit"); ?></a>
        <?php echo $after_widget;
    }
}