<?php
/**
 * Plugin Name: WP Job Manager - Loxo Integration
 * Description: 
 * Version: 0.0.0-development
 * Author: Seattle Web Co.
 * Author URI: https://seattlewebco.com
 * Text Domain: wp-job-manager-loxo
 * Requires PHP: 7.2.5
 *
 * @package WP Job Manager - Loxo Integration
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WP_JOB_MANAGER_LOXO_VER', '0.0.0-development' );
define( 'WP_JOB_MANAGER_LOXO_PLUGIN_NAME', 'WP Job Manager - Loxo Integration' );
define( 'WP_JOB_MANAGER_LOXO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_JOB_MANAGER_LOXO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

if ( ! defined( 'WP_JOB_MANAGER_LOXO_LOG' ) ) {
    define( 'WP_JOB_MANAGER_LOXO_LOG', WP_JOB_MANAGER_LOXO_PLUGIN_DIR . 'logs/log-debug.log' );
}


require WP_JOB_MANAGER_LOXO_PLUGIN_DIR . 'vendor/autoload.php';
require WP_JOB_MANAGER_LOXO_PLUGIN_DIR . 'includes/class-recruiter.php';


function WP_Job_Manager_Loxo() {
    return \SeattleWebCo\WPJobManager\Recruiter\Loxo\Recruiter::instance();
}
WP_Job_Manager_Loxo();

register_activation_hook( __FILE__, function() {
    wp_clear_scheduled_hook( 'job_manager_loxo_sync_jobs' );

    wp_schedule_event( time(), 'loxo_sync', 'job_manager_loxo_sync_jobs' );
} );

register_deactivation_hook( __FILE__, function() {
    wp_clear_scheduled_hook( 'job_manager_loxo_sync_jobs' );
} );
