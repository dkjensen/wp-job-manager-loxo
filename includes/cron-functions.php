<?php
/**
 * Cron jobs
 * 
 * @package WP Job Manager - Loxo Integration
 */


namespace SeattleWebCo\WPJobManager\Recruiter\Loxo;


function sync_jobs() {
    do_action( 'job_manager_loxo_before_job_sync' );

    foreach ( WP_Job_Manager_Loxo()->clients as $client ) {
        $client->sync_jobs();
    }

    do_action( 'job_manager_loxo_after_job_sync' );
}
add_action( 'job_manager_loxo_sync_jobs', __NAMESPACE__ . '\sync_jobs' );

function schedule_sync() {
    wp_clear_scheduled_hook( 'job_manager_loxo_sync_jobs' );

    wp_schedule_event( time(), 'loxo_sync', 'job_manager_loxo_sync_jobs' );
}
add_action( 'update_option_loxo_sync_interval', __NAMESPACE__ . '\schedule_sync' );
