<?php
/**
 * Logging class
 * 
 * @package WP Job Manager - Loxo Integration
 */


namespace SeattleWebCo\WPJobManager\Recruiter\Loxo;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Log {

	/**
	 * Instance of the logger class
	 *
	 * @var Monolog\Logger
	 */
	protected $log = null;


	/**
	 * Setup
	 */
	public function __construct() {
		$this->log = new Logger( 'wp-job-manager-loxo' );
    	$this->log->pushHandler( new StreamHandler( WP_JOB_MANAGER_LOXO_LOG, Logger::DEBUG ) );
	}


	/**
	 * Logs an info message
	 *
	 * @param string $message
	 * @param array  $details
	 * @return void
	 */
	public function info( $message, $details = array() ) {
		$this->log->info( esc_html( $message ), (array) $details );
	}


	/**
	 * Logs an error message
	 *
	 * @param string $message
	 * @param array  $details
	 * @return void
	 */
	public function error( $message, $details = array() ) {
		$this->log->error( esc_html( $message ), (array) $details );
	}
	
}
