<?php

namespace BOILERPLATE\Inc;

use BOILERPLATE\Inc\Traits\Program_Logs;
use BOILERPLATE\Inc\Traits\Singleton;

class API_DB_Factory {

    use Singleton;
    use Program_Logs;

    public function __construct() {
        $this->setup_hooks();
    }

    public function setup_hooks() {
        // Register REST route for inserting users to database
        add_action( 'rest_api_init', [ $this, 'register_rest_route' ] );
    }

    public function register_rest_route() {

        register_rest_route( 'hello-again/v1', '/insert-users', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'insert_user' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( 'hello-again/v1', '/sync-users', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'sync_users' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function insert_user() {
        try {
            // Fetch the first page to get total users and pages.
            $users_json = $this->fetch_users_from_api( 1 );

            if ( empty( $users_json ) ) {
                return new \WP_Error( 'no_users', 'No users found to insert', [ 'status' => 404 ] );
            }

            // Initialize variables
            $total_users = 0;
            $total_pages = 0;

            // Decode JSON
            $users_array = json_decode( $users_json, true );

            // Get total users
            if ( array_key_exists( 'count', $users_array ) ) {
                $total_users = $users_array['count'];
            }

            // Update total users to options table
            update_option( 'sync_total_users', $total_users );

            // Calculate total pages
            $total_pages = ceil( $total_users / 100 );

            // Update total pages to options table
            update_option( 'sync_total_pages', $total_pages );

            global $wpdb;
            $table_name = $wpdb->prefix . 'sync_users';

            // Get the last processed page from options table or start from 1
            $start_page = get_option( 'sync_current_page', 1 );

            // Loop through all pages starting from the last unprocessed page
            for ( $page = $start_page; $page <= $total_pages; $page++ ) {

                // Fetch users
                $users_json = $this->fetch_users_from_api( $page );
                // Decode JSON
                $users_array = json_decode( $users_json, true );

                // Update current page to options table
                update_option( 'sync_current_page', $page );

                // Get users on the current page
                $users = $users_array['results'] ?? [];

                foreach ( $users as $user ) {
                    $user_id   = $user['id'];
                    $email     = $user['email'] ?? null;
                    $user_data = json_encode( $user );
                    $status    = 'pending';

                    $sql = $wpdb->prepare(
                        "INSERT INTO $table_name (user_id, email, user_data, status) VALUES (%s, %s, %s, %s)
                        ON DUPLICATE KEY UPDATE user_data = %s, status = %s",
                        $user_id,
                        $email,
                        $user_data,
                        $status,
                        $user_data,
                        $status
                    );

                    $result = $wpdb->query( $sql );

                    if ( $result === false ) {
                        return new \WP_Error( 'db_error', 'Failed to insert or update user in database', [ 'status' => 500 ] );
                    }
                }
            }

            // Reset current page after all pages have been processed
            update_option( 'sync_current_page', 1 );

            return 'All users inserted or updated successfully.';

        } catch (\Exception $e) {
            return new \WP_Error( 'exception', $e->getMessage(), [ 'status' => 500 ] );
        }
    }

    public function fetch_users_from_api( $page = 1 ) {

        // Credentials
        $api_base_url = '';
        $api_key      = '';

        $credentials_file = PLUGIN_BASE_PATH . '/credentials.json';
        if ( file_exists( $credentials_file ) ) {
            $credentials  = json_decode( file_get_contents( $credentials_file ), true );
            $api_base_url = $credentials['api_base_url'];
            $api_key      = $credentials['api_key'];
        }

        $curl = curl_init();
        curl_setopt_array( $curl, array(
            CURLOPT_URL            => $api_base_url . '/users/?limit=100&page=' . $page,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => array(
                'accept: application/json',
                'Authorization: API-Key ' . $api_key,
            ),
        ) );

        $response = curl_exec( $curl );

        if ( curl_errno( $curl ) ) {
            $error_msg = curl_error( $curl );
            curl_close( $curl );
            return new \WP_Error( 'curl_error', 'cURL error: ' . $error_msg, [ 'status' => 500 ] );
        }

        $http_code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
        curl_close( $curl );

        if ( $http_code !== 200 ) {
            return new \WP_Error( 'api_error', 'API responded with HTTP code ' . $http_code, [ 'status' => $http_code ] );
        }

        return $response;
    }

    public function sync_users() {

        try {

            $limit = 1;

            // Fetch users from the database
            global $wpdb;
            $table_name = $wpdb->prefix . 'sync_users';

            $sql   = "SELECT * FROM $table_name WHERE status = 'pending' LIMIT $limit";
            $users = $wpdb->get_results( $sql );

            if ( empty( $users ) ) {
                return new \WP_Error( 'no_pending_users', 'No pending users found', [ 'status' => 404 ] );
            }

            foreach ( $users as $user ) {

                $user_id   = $user->user_id;
                $email     = $user->email;
                $user_data = json_decode( $user->user_data, true );

                // User Name
                $first_name  = $user_data['first_name'] ?? '';
                $middle_name = $user_data['middle_name'] ?? '';
                $last_name   = $user_data['last_name'] ?? '';
                $full_name   = trim( "$first_name $middle_name $last_name" );

                // User Status
                $status = $user_data['status'] ?? [];

                // Collect user data
                $meta_data = [
                    '_email'         => $email,
                    '_company'       => $user_data['company'] ?? '',
                    '_phone_number'  => $user_data['phone_number'] ?? '',
                    '_birthday'      => $user_data['birthday'] ?? '',
                    '_gender'        => $user_data['gender'] ?? '',
                    '_points'        => $user_data['points'] ?? 0,
                    '_street'        => $user_data['address']['street'] ?? '',
                    '_city_code'     => $user_data['address']['city_code'] ?? '',
                    '_city'          => $user_data['address']['city'] ?? '',
                    '_street_number' => $user_data['address']['street_number'] ?? '',
                    '_state'         => $user_data['address']['state'] ?? '',
                    '_country'       => $user_data['address']['country'] ?? '',
                    '_fbid'          => $user_data['fbid'] ?? '',
                    '_apple_user_id' => $user_data['apple_user_id'] ?? '',
                    '_google_id'     => $user_data['google_id'] ?? '',
                    '_status'        => json_encode( $status ),
                ];

                $photos_urls = [];
                $photos      = $user_data['photos'];
                if ( !empty( $photos ) && is_array( $photos ) ) {
                    foreach ( $photos as $photo ) {
                        $photos_urls[] = [
                            'url'   => $photo['photo_url'],
                            'order' => $photo['order'],
                        ];
                    }
                }

                // $this->put_program_logs( 'photos_urls: ' . json_encode( $photos_urls ) );

                // Check if user already exists in sync_users post type by _sync_user_id meta key
                $existing_user_query = new \WP_Query( [
                    'post_type'  => 'sync_users',
                    'meta_query' => [
                        [
                            'key'     => '_sync_user_id',
                            'value'   => $user_id,
                            'compare' => '=',
                        ],
                    ],
                    'fields'     => 'ids',
                ] );

                // Check if user exists
                if ( $existing_user_query->have_posts() ) {
                    // User exists, get the post ID and update
                    $post_id = $existing_user_query->posts[0];

                    // Update user info
                    wp_update_post( [
                        'ID'         => $post_id,
                        'post_title' => $full_name,
                    ] );

                } else {
                    // User does not exist, create a new user post
                    $post_id = wp_insert_post( [
                        'post_type'   => 'sync_users',
                        'post_title'  => $full_name,
                        'post_status' => 'publish',
                    ] );

                    // Add unique user ID to post meta
                    add_post_meta( $post_id, '_sync_user_id', $user_id, true );
                }

                // Serialize and store the user data array as a single meta field
                update_post_meta( $post_id, '_sync_users', $meta_data );

                // Set the photo URL as the featured image if available
                if ( !empty( $photos_urls ) ) {
                    $this->set_featured_image_from_url( $post_id, $photos_urls );
                }

                // Update user status to 'completed' in the database
                $wpdb->update(
                    $table_name,
                    [ 'status' => 'completed' ],
                    [ 'id' => $user->id ]
                );
            }

            return 'User(s) processed successfully.';

        } catch (\Exception $e) {
            return new \WP_Error( 'exception', $e->getMessage(), [ 'status' => 500 ] );
        }
    }

    private function set_featured_image_from_url( $post_id, $images ) {

        foreach ( $images as $image ) {

            // Check if the order is 1, as we only want this image as the featured one
            if ( $image['order'] == 1 ) {

                $image_url  = $image['url'];
                $image_name = basename( $image_url );
                $upload_dir = wp_upload_dir();

                // Download the image from URL
                $image_data = file_get_contents( $image_url );

                if ( $image_data !== false ) {
                    $image_file = $upload_dir['path'] . '/' . $image_name;
                    file_put_contents( $image_file, $image_data );

                    // Prepare image data for attachment
                    $file_path = $upload_dir['path'] . '/' . $image_name;
                    $file_name = basename( $file_path );

                    $attachment = [
                        'post_mime_type' => mime_content_type( $file_path ),
                        'post_title'     => preg_replace( '/\.[^.]+$/', '', $file_name ),
                        'post_content'   => '',
                        'post_status'    => 'inherit',
                    ];

                    // Insert the image as an attachment and set as featured
                    $attach_id = wp_insert_attachment( $attachment, $file_path, $post_id );
                    // $this->put_program_logs( 'attach_id: ' . $attach_id );

                    require_once( ABSPATH . 'wp-admin/includes/image.php' );
                    $attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
                    wp_update_attachment_metadata( $attach_id, $attach_data );

                    // Set the image as the post thumbnail (featured image)
                    set_post_thumbnail( $post_id, $attach_id );

                    break; // Exit the loop after setting the featured image
                }
            }
        }
    }

}
