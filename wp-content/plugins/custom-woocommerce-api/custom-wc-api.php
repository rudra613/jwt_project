<?php
/**
 * Plugin Name: Custom Woocommerce API
 * Description: Custom Rest Api For Woocommerce using get,post,put and delete
 * Version: 1.0
 * Author: Rudra Pandit
 */

require_once __DIR__ . '/../wp-jwt-auth/vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

add_action('rest_api_init', function () {
    register_rest_route('custom-wc/v1', '/token', [
        'methods' => 'POST',
        'callback' => 'custom_jwt_generate_token',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('custom-wc/v1', '/products', [
        'methods' => 'GET',
        'callback' => 'custom_wc_get_products',
        'permission_callback' => 'custom_jwt_verify_token',
    ]);

    register_rest_route('custom-wc/v1','/products',[
        'methods'=>'POST',
        'callback'=>'custom_wc_add_products',
        'permission_callback' =>'custom_jwt_verify_token',
    ]);

    
    register_rest_route('custom-wc/v1', '/products/(?P<id>\d+)', [
        'methods'  => 'DELETE',
        'callback' => 'custom_wc_delete_products',
        'permission_callback' => 'custom_jwt_verify_token',

    ]);

   register_rest_route('custom-wc/v1', '/products/(?P<id>\d+)', [
    'methods'  => 'PUT',
    'callback' => 'custom_wc_edit_product',
    'permission_callback' => 'custom_jwt_verify_token',
]);

    register_rest_route('custom-wc/v1','/products/(?P<id>\d+)',[
        'methods'=>'GET',
        'callback'=>'get_product_by_id',
        'permission_callback' => 'custom_jwt_verify_token',
    ]);
   

});

function custom_jwt_generate_token($request) {
    $params = $request->get_json_params();
    $username = $params['username'] ?? '';
    $password = $params['password'] ?? '';

    $user = wp_authenticate($username, $password);
    if (is_wp_error($user)) {
        return new WP_Error('unauthorized', 'Invalid credentials', ['status' => 403]);
    }

    $payload = [
        'iss' => get_bloginfo('url'),
        'iat' => time(),
        'exp' => time() + (DAY_IN_SECONDS * 7),
        'data' => [
            'user_id' => $user->ID,
        ],
    ];

    $token = JWT::encode($payload, JWT_AUTH_SECRET_KEY, 'HS256');

    return ['token' => $token];
}

function custom_jwt_verify_token($request) {
    $auth = $request->get_header('authorization');

    if (!$auth || !preg_match('/Bearer\s(\S+)/', $auth, $matches)) {
        return new WP_Error('unauthorized', 'No token provided', ['status' => 403]);
    }

    $token = $matches[1];

    try {
        $decoded = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        return true;
    } catch (Exception $e) {
        return new WP_Error('unauthorized', 'Invalid token: ' . $e->getMessage(), ['status' => 403]);
    }
}

function custom_wc_get_products() {
    $products = wc_get_products(["limit" => 5]);
    $data = [];

    foreach ($products as $product) {
        $data[] = [
            'id'    => $product->get_id(),
            'name'  => $product->get_name(),
            'price' => $product->get_price(),
        ];
    }

    return $data;
}

//add the product
function custom_wc_add_products($request){
    $params=$request->get_json_params();
    $name=sanitize_text_field( $params['name'] ?? '' );
    $price = floatval($params['price'] ?? 0);
    $description=sanitize_textarea_field( $params['description'] ?? '');
   if (empty($name) || $price <= 0) {
    return new WP_Error('missing_data', 'Product name and price are required', ['status' => 400]);
}

    $product=new WC_Product_Simple();
    $product->set_name($name);
    $product->set_price($price);
    $product->set_description($description);
    $product->save();
    return [
        'message'=>'Product Created',
        'product_id'=>$product->get_id()
    ];
}

//delete product
function custom_wc_delete_products($request){
    $id=(int) $request['id'];
    $product=wc_get_product($id);
    if(!$product){
        return new WP_Error('not_found','Product not found',['status' => 404]);
    }
    $result=wp_delete_post($id,true);
    if($result){
        return [
            'message'=>'Product deleted successfully',
            'product_id'=>$id
        ];
    }
    else{
        return new WP_Error('deletion error','deletion problem',['status'=>500]);
    }
}

//edit product
function custom_wc_edit_product($request){
    $id=(int)$request['id'];
    $params=$request->get_json_params();
    $name=sanitize_text_field( $params['name'] ?? '' );
    $price=floatval($params['price'] ?? 0);
    $description=sanitize_textarea_field( $params['description'] ?? '' );
    $product=wc_get_product($id);
    if(!$product){
        return new WP_Error('not_found','Product not found',['status'=>404]);
    }
    if(empty($name)){
        return new WP_Error('missing_data','Name is required',['status'=>400]);
    }
    if($price <= 0){
        return new WP_Error('missing_data','Valid price is required',['status'=>400]);
    }
    
    $product->set_name($name);
    $product->set_regular_price($price);            
    $product->set_price($price);
    if(!empty($description)){
        $product->set_description($description);
    }
    $product->save();
    return [
        'message'=>'Product updated successfully',
        'product_id'=>$product->get_id(),
        'product_name'=>$product->get_name(),
        'product_description'=>$product->get_description(),
    ];
}

//get product by id
function get_product_by_id($request){
    $id=(int)$request['id'];
      if(!$id){
        return new WP_Error('not_found','ID not found',['status'=>404]);
    }
    $product=wc_get_product($id);   
    if(!$product){
        return new WP_Error('not_found','Product not found',['status'=>404]);
    }
    return [
        'message'=>'Product is found',
        'product_id'=>$product->get_id(),   
        'product_name'=>$product->get_name(),
        'product_price'=>$product->get_price(),
    ];
}
