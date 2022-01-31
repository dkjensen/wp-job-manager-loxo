<?php
/**
 * Loxo API adapter
 * 
 * @package WP Job Manager - Loxo Integration
 */


namespace SeattleWebCo\WPJobManager\Recruiter\Loxo\Adapter;

use SeattleWebCo\WPJobManager\Recruiter\Loxo\Exception;
use GuzzleHttp\Psr7;

class LoxoAdapter implements Adapter {

    public function connected() {
        return is_wp_error( $this->request( 'GET', 'jobs?per_page=1' ) ) ? false : true;
    }


    public function get_jobs() {
        $jobs = $this->request( 'GET', 'jobs?per_page=100' );
        $jobs = isset( $jobs->results ) ? (array) $jobs->results : array();

        return $jobs;
    }


    public function sync_jobs() {
        $jobs = array();

        foreach ( $this->get_jobs() as $job ) {
            $job = $this->get_job( $job->id );

            if ( isset( $job->category ) && isset( $job->category->name ) ) {
                $category = get_term_by( 'name', $job->category->name, 'job_listing_category', ARRAY_A );

                if ( ! $category ) {
                    $category = wp_insert_term( $job->category->name, 'job_listing_category' );
                }
            }

            $job_type = get_term_by( 'name', isset( $job->job_type ) && isset( $job->job_type->name ) ? $job->job_type->name : '', 'job_listing_type', ARRAY_A );
            $job_type = apply_filters( 'wp_job_manager_loxo_loxo_job_type', $job_type, $job );

            $jobs[] = array(
                'post_title' 		=> isset( $job->title ) ? $job->title : __( 'Untitled job', 'wp-job-manager-loxo' ),
                'post_content' 		=> isset( $job->description ) ? $job->description : '',
                'post_status'		=> 'publish',
                'post_type'			=> 'job_listing',
                'tax_input'         => array(
                    'job_listing_category' => ! empty( $category ) && ! is_wp_error( $category ) ? $category['term_id'] : null,
                    'job_listing_type'     => ! empty( $job_type ) && ! is_wp_error( $job_type ) ? $job_type['term_id'] : null,
                ),
                'meta_input'		=> array(
                    '_jid'			        => $job->id,
                    '_job_salary'           => isset( $job->salary ) ? trim( $job->salary, " \n\r\t\v\x00/" ) : '',
                    '_job_location'         => isset( $job->macro_address ) ? $job->macro_address : '',
                    '_job_expires'          => isset( $job->published_end_date ) ? date( 'Y-m-d', strtotime( $job->published_end_date ) ) : '',
                    '_application'          => isset( $job->public_url ) ? esc_url( $job->public_url ) : get_option( 'admin_email' ),
                    '_company_name'         => get_option( 'blogname' ),
                    '_filled'               => isset( $job->status ) && isset( $job->status->name ) && $job->status->name === 'Active' ? 0 : 1,
                    '_imported_from'        => 'loxo',
                ),
            );
        }

        return $jobs;
    }


    public function job_exists( $id ) {
        global $wpdb;

        $exists = $wpdb->get_var( $wpdb->prepare( "
            SELECT post_id 
            FROM   $wpdb->postmeta 
            WHERE  meta_key = '_jid'
            AND    meta_value = '%s' 
            LIMIT  1", $id 
        ) );

        if ( ! $exists ) {
            return false;
        }

        return $exists;
    }


    public function get_job( $job ) {
        $job = $this->request( 'GET', 'jobs/' . $job );

        if ( ! is_wp_error( $job ) ) {
            return $job;
        }

        return false;
    }


    public function post_job_application( $job_id, $fields, $application_id ) {
        if ( empty( $fields['email'] ) || empty( $fields['name'] ) || empty( $fields['phone'] ) || empty( $fields['resume'] ) ) {
            return;
        }

        $loxo_job_id = get_post_meta( $job_id, '_jid', true );

        $client = new \GuzzleHttp\Client();

        $formdata = array();

        foreach ( $fields as $key => $value ) {
            if ( 'resume' === $key && $value ) {
                $value = fopen( $value, 'r' );
            }


            $formdata[] = array(
                'name'     => $key,
                'contents' => $value,
            );
        }

        $response = $client->request( 'POST', 'https://loxo.co/api/' . get_option( 'loxo_agency_slug', '' ) . '/jobs/' . $loxo_job_id . '/apply', [
            'multipart' => $formdata,
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . \base64_encode( get_option( 'loxo_username', '' ) . ':' . get_option( 'loxo_password', '' ) )
            ],
        ] );

        $response_body = json_decode( (string) $response->getBody() );

        if ( ! empty( $response_body->errors ) ) {
            throw new \Exception( implode( ', ', $response_body->errors ) );
        }
    }


    public function request( $method, $endpoint, $json = array(), $headers = array() ) {
        try {
            $response = wp_remote_request( 'https://loxo.co/api/' . get_option( 'loxo_agency_slug', '' ) . '/' . $endpoint, array(
                'headers'       => wp_parse_args( $headers, array( 
                    'Content-Type'      => 'application/json',
                    'Authorization'     => 'Basic ' . \base64_encode( get_option( 'loxo_username', '' ) . ':' . get_option( 'loxo_password', '' ) )
                ) ),
                'method'        => $method,
                'data_format'   => 'body',
                'body'          => ! empty( $json ) ? json_encode( $json ) : null
            ) );

            $body = json_decode( (string) wp_remote_retrieve_body( $response ) );
            $code = wp_remote_retrieve_response_code( $response );

            if ( substr( $code, 0, 1 ) != 2 ) {
                throw new Exception( isset( $body->message ) ? $body->message : 'An error occurred', $code, isset( $body->errors ) ? $body->errors : null );
            }

            return $body;
        } catch ( Exception $e ) {
            return new \WP_Error( 'job_manager_loxo_request', esc_html( $e->getMessage() ), $e->getDetails() );
        }
    }
}
