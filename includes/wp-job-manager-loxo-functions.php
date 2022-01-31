<?php
/**
 * General functions
 * 
 * @package WP Job Manager - Loxo Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Adds custom job sync interval to the WP cron schedule
 *
 * @param array $schedules
 * @return array
 */
function job_manager_loxo_sync_interval( $schedules ) {
    $interval = (int) get_option( 'loxo_sync_interval', 15 );

    $schedules['loxo_sync'] = array(
        'interval'  => $interval * 60,
        'display'   => sprintf( _n( 'Every minute', 'Every %s minutes', $interval, 'wp-job-manager-loxo' ), $interval )
    );

    return $schedules;
}
add_filter( 'cron_schedules', 'job_manager_loxo_sync_interval' );


/**
 * Format fields into valid URLs
 *
 * @param string $value
 * @param array  $field
 * @return string
 */
function job_manager_loxo_format_url( $value, $field ) {
    $fields = array();

    if ( in_array( $field['loxo'], $fields ) ) {
        $value = esc_url( $value );
    }

    return $value;
}
add_filter( 'job_manager_application_field_value', 'job_manager_loxo_format_url', 10, 2 );
