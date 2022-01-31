<?php
/**
 * Main WP_Job_Manager_Loxo class file
 * 
 * @package WP Job Manager - Loxo Integration
 */


namespace SeattleWebCo\WPJobManager\Recruiter\Loxo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Recruiter {

    /**
	 * Plugin object
	 */
    private static $instance;
    

    /**
     * Logger class
     *
     * @var Log
     */
    public $log;


    /**
     * Provider client
     *
     * @var Client
     */
    public $clients;

    
    /**
     * Insures that only one instance of WP_Job_Manager_Loxo exists in memory at any one time.
     * 
     * @return Recruiter The one true instance of Recruiter
     */
    public static function instance() {
        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof WP_Job_Manager_Loxo ) ) {
            self::$instance = new Recruiter;
            self::$instance->includes();
            self::$instance->clients        = array(
                'loxo'      => new Client( new Adapter\LoxoAdapter )
            );
            self::$instance->log            = new Log;

            do_action_ref_array( 'wp_job_manager_loxo_loaded', self::$instance ); 
        }
        
        return self::$instance;
    }


    /**
     * Include the goodies
     *
     * @return void
     */
    public function includes() {
        require_once WP_JOB_MANAGER_LOXO_PLUGIN_DIR . 'includes/class-applications.php';
        require_once WP_JOB_MANAGER_LOXO_PLUGIN_DIR . 'includes/cron-functions.php';
        require_once WP_JOB_MANAGER_LOXO_PLUGIN_DIR . 'includes/wp-job-manager-loxo-functions.php';

        if ( is_admin() ) {
            require_once WP_JOB_MANAGER_LOXO_PLUGIN_DIR . 'includes/admin/class-settings.php';
        }
    }


    /**
     * Throw error on object clone
     *
     * @return void
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wp-job-manager-loxo' ), '1.0.0' );
    }


    /**
     * Disable unserializing of the class
     * 
     * @return void
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wp-job-manager-loxo' ), '1.0.0' );
    }
}
