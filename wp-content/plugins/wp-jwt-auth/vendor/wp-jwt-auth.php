<?php
/**
 * Plugin Name: WP JWT Auth
 * Description: Generate and validate JWT token manually.
 * Version: 1.1
 */
//autoload composer classes
require_once __DIR__ . '/vendor/autoload.php';
//import library
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Register API routes
add_action('rest_api_init', function () {
    // Token generation route
    register_rest_route('custom-jwt/v1', '/token', [
        'methods'  => 'POST',
        'callback' => 'generate_custom_jwt_token',
        'permission_callback' => '__return_true',
    ]);

    // Token validation route
    register_rest_route('custom-jwt/v1', '/validate', [
        'methods'  => 'GET',
        'callback' => 'validate_custom_jwt_token',
        'permission_callback' => '__return_true',
    ]);
});

// Function to generate token
function generate_custom_jwt_token($request) {
    $params = $request->get_json_params();
    $username = sanitize_text_field($params['username'] ?? '');
    $password = sanitize_text_field($params['password'] ?? '');

    $user = wp_authenticate($username, $password);//check from database username and password available

    if (is_wp_error($user)) {
        return new WP_Error('invalid_login', 'Invalid username or password', ['status' => 403]);
    }

    $user_id = $user->ID;

    $issuedAt = time();
    $expire = $issuedAt + (60 * 60);    
    //$expire=$issueAt +(2*60*60);
    $payload = [
        'iss' => get_bloginfo('url'),
        'iat' => $issuedAt,
        'exp' => $expire,
        'user_id' => $user_id,
    ];

    $token = JWT::encode($payload, JWT_AUTH_SECRET_KEY, 'HS256');//algorithm

    return rest_ensure_response([
        'token' => $token,
        'user_id' => $user_id,
    ]);
}

// Function to validate token
function validate_custom_jwt_token($request) {
    $headers = getallheaders();//retrive all php header request
    error_log(print_r($headers));
    $auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (!$auth_header || stripos($auth_header, 'Bearer ') !== 0) {
        return new WP_Error('jwt_auth_no_token', 'Authorization header not found or invalid.', ['status' => 403]);
    }

    $token = trim(str_ireplace('Bearer', '', $auth_header));

    try {
        $decoded = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        $user_id = $decoded->user_id ?? 0;

        if ($user_id && get_user_by('id', $user_id)) {
            return rest_ensure_response([
                'success' => true,
                'message' => 'Token is valid',
                'user_id' => $user_id
            ]);
        } else {
            return new WP_Error('jwt_auth_invalid_user', 'Invalid token user.', ['status' => 403]);
        }

    } catch (Exception $e) {
        return new WP_Error('jwt_auth_invalid_token', 'Token validation failed: ' . $e->getMessage(), ['status' => 403]);
    }
}

//authentication for viewing posts
add_filter('rest_authentication_errors',function($result){
       if (!empty($result)) {
        return $result;
    }
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';

    //only allow token available for endpoint
     if (strpos($request_uri, '/wp-json/custom-jwt/v1/token') !== false ||
        strpos($request_uri, '/wp-json/custom-jwt/v1/validate') !== false) {
        return $result;
    }

    //check the token of endpoint also authorization required for viewing posts
    if (strpos($request_uri, '/wp-json/wp/v2/posts') !== false) {
        $headers = getallheaders();//retrive all header request
        $auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (!$auth_header || stripos($auth_header, 'Bearer ') !== 0) {
            return new WP_Error('jwt_auth_no_token', 'Token required to access posts.', ['status' => 403]);
        }

        $token = trim(str_ireplace('Bearer', '', $auth_header));

        try {
            $decoded = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
            $user_id = $decoded->user_id ?? 0;

            if ($user_id && get_user_by('id', $user_id)) {
                wp_set_current_user($user_id); 
                return $result;
            }

            return new WP_Error('jwt_auth_invalid_user', 'Invalid user in token.', ['status' => 403]);

        } catch (Exception $e) {
            return new WP_Error('jwt_auth_invalid_token', 'Token validation failed: ' . $e->getMessage(), ['status' => 403]);
        }
    }
    return $result;
});