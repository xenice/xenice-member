<?php
if (!defined('ABSPATH')) {
    exit;
}

class Xenice_Level_Duration {

    public function __construct() {
        add_filter('xenice_member_duration_options', [$this, 'duration_options']);
        add_filter('xenice_member_duration_values', [$this, 'duration_values']);
    }

    /**
     * Duration options for select dropdown (human readable)
     */
    public function duration_options($options) {
        $options = array(
            '0'   => __('Lifetime', 'xenice-member'),
            '365' => __('1 Year', 'xenice-member'),
            '90'  => __('90 Days', 'xenice-member'),
            '30'  => __('30 Days', 'xenice-member'),
            '7'   => __('7 Days', 'xenice-member'),
            '3'   => __('3 Days', 'xenice-member'),
            '1'   => __('1 Day', 'xenice-member'),
        );
        return $options;
    }

    /**
     * Duration values in seconds (for calculation)
     */
    public function duration_values($values) {
        $values = array(
            '0'   => 'lifetime',
            '365' => 86400 * 365,
            '90'  => 86400 * 90,
            '30'  => 86400 * 30,
            '7'   => 86400 * 7,
            '3'   => 86400 * 3,
            '1'   => 86400 * 1,
        );
        return $values;
    }
}
