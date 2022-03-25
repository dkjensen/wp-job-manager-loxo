<?php
/**
 * Admin settings
 * 
 * @package WP Job Manager - Loxo Integration
 */


namespace SeattleWebCo\WPJobManager\Recruiter\Loxo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Settings {

    /**
     * Are we connected to the Loxo API?
     * 
     * @var boolean
     */
    public $connected = null;

    /**
     * Job statuses to sync
     *
     * @var array
     */
    public $job_statuses = null;


    public function __construct() {
        add_filter( 'job_manager_settings', array( $this, 'settings' ) );

        // Authorization field callback
        add_action( 'wp_job_manager_admin_field_loxo_setup', array( $this, 'setup_field_callback' ), 10, 4 );
        add_action( 'wp_job_manager_admin_field_loxo_authorization', array( $this, 'loxo_authorization_field_callback' ), 10, 4 );
        add_action( 'wp_job_manager_admin_field_loxo_job_statuses', array( $this, 'job_statuses_field_callback' ), 10, 4 );

        add_action( 'job_manager_loxo_settings', array( $this, 'loxo_authorization' ) );
        add_action( 'job_manager_loxo_settings', array( $this, 'loxo_deauthorization' ) );
        add_action( 'admin_init', array( $this, 'loxo_sync_jobs' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
    }


    /**
     * WP Job Manager settings
     *
     * @param array $settings
     * @return array
     */
    public function settings( $settings ) {
        $this->connected = $this->connected ?: WP_Job_Manager_Loxo()->clients['loxo']->connected();
        $this->job_statuses = $this->job_statuses ?: (array) WP_Job_Manager_loxo()->clients['loxo']->get_job_statuses();
        
        $settings = (array) $settings;
        $settings['loxo'] = array(
            __( 'Loxo', 'wp-job-manager-loxo' ),
            array(
                array(
                    'name'          => 'loxo_agency_slug',
                    'label'         => __( 'Loxo Agency Slug', 'wp-job-manager-loxo' ),
                    'type'          => 'text',
                ),
                array(
                    'name'          => 'loxo_username',
                    'label'         => __( 'Loxo API Username', 'wp-job-manager-loxo' ),
                    'type'          => 'text',
                ),
                array(
                    'name'          => 'loxo_password',
                    'label'         => __( 'Loxo API Password', 'wp-job-manager-loxo' ),
                    'type'          => 'password',
                ),
                array(
                    'name'          => 'loxo_authorization',
                    'label'         => __( 'Loxo Authorization', 'wp-job-manager-loxo' ),
                    'type'          => 'loxo_authorization',
                ),
                array(
                    'name'          => 'loxo_job_statuses',
                    'label'         => __( 'Job Statuses to Sync', 'wp-job-manager-loxo' ),
                    'type'          => 'loxo_job_statuses',
                ),
                array(
                    'name'          => 'loxo_applications',
                    'label'         => __( 'Post Applications to Loxo', 'wp-job-manager-loxo' ),
                    'type'          => 'checkbox',
                    'cb_label'      => __( 'Job applications submitted via the WP Job Manager - Applications plugin will be sent to Loxo', 'wp-job-manager-loxo' )
                ),
            ),
            array(
                'after' => sprintf( '<a href="%s">%s</a>', wp_nonce_url( admin_url( 'edit.php?post_type=job_listing&page=job-manager-settings&sync=loxo' ) ), __( 'Sync now', 'wp-job-manager-loxo' ) )
            ),
        );

        return $settings;
    }

    public function loxo_authorization_field_callback( $option, $attributes, $value, $placeholder ) {
        ?>

        <p><?php esc_html_e( $this->connected ? 'Connected' : 'Not Connected', 'wp-job-manager-loxo' ); ?></p>

        <?php
    }

    public function job_statuses_field_callback( $option, $attributes, $value, $placeholder ) {
        if ( ! $this->connected ) {
            return;
        }

        $synced_job_statuses = array_filter( (array) get_option( 'loxo_job_statuses' ) );

        ?>

        <fieldset>
            <legend class="screen-reader-text"><span><?php _e( 'Job Statuses To Sync', 'wp-job-manager-loxo' ); ?></span></legend>

            <?php foreach ( $this->job_statuses as $job_status ) : ?>
                
                <label>
                    <input name="loxo_job_statuses[]"  type="checkbox" value="<?php print esc_attr( $job_status->id ); ?>" <?php checked( true, in_array( $job_status->id, $synced_job_statuses ) ); ?>>
                    <?php echo esc_html( $job_status->name ); ?>
                </label><br>

            <?php endforeach; ?>

        </fieldset>

        <?php
    }


    public function loxo_sync_jobs() {
        if ( isset( $_GET['sync'] ) && $_GET['sync'] == 'loxo' && wp_verify_nonce( $_GET['_wpnonce'] ) && current_user_can( 'manage_options' ) ) {
            do_action( 'job_manager_loxo_sync_jobs' );
        }
    }


    public function scripts() {
        wp_enqueue_style( 'wp-job-manager-loxo-admin', WP_JOB_MANAGER_LOXO_PLUGIN_URL . '/assets/css/admin.min.css', array(), WP_JOB_MANAGER_LOXO_VER );

        wp_register_script( 'wp-job-manager-loxo-admin', WP_JOB_MANAGER_LOXO_PLUGIN_URL . '/assets/js/admin.min.js', array( 'jquery' ), WP_JOB_MANAGER_LOXO_VER, true );

        wp_localize_script( 'wp-job-manager-loxo-admin', 'job_manager_loxo', array(
            'application_form_column_loxo_label'    => __( 'Field', 'wp-job-manager-loxo' ),
            'application_form_fields'                   => get_option( 'job_application_form_fields' ),
            'application_clients'                       => array_keys( WP_Job_Manager_Loxo()->clients ),
            'application_client_fields'                 => array(
                'loxo' => array(
                    'name'                  => __( 'Name', 'wp-job-manager-loxo' ),
                    'email'                 => __( 'Email', 'wp-job-manager-loxo' ),
                    'phone'                 => __( 'Phone', 'wp-job-manager-loxo' ),
                    'resume'                => __( 'Resume', 'wp-job-manager-loxo' ),
                )
            )
        ) );

        wp_enqueue_script( 'wp-job-manager-loxo-admin' );
    }

}

return new Settings;
