    <?php
    /**
     * Plugin Name: Custom Woocommerce Order API
     * Description: Custom Rest Api for Woocommerce using get, post, put and delete
     * Version: 1.0
     * Author: Rudra Pandit
     */

    require_once __DIR__ . '/../wp-jwt-auth/vendor/autoload.php';

    use Firebase\JWT\JWT;
    use Firebase\JWT\Key;

    define('CUSTOM_JWT_SECRET_KEY', 'hello@123hgbdhbffuir#xvdfh'); // Define secret key constant

    add_action('rest_api_init', function () {
        register_rest_route('orders-wc/v1', '/token', [
            'methods' => 'POST',
            'callback' => 'custom_jwt_token',
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('orders-wc/v1','/orders',[
            'methods'=>'GET',
            'callback'=>'custom_wc_get_orders',
            'permission_callback'=>'custom_jwt_verify',
        ]);

        register_rest_route('orders-wc/v1','/orders',[
            'methods'=>'POST',
            'callback'=>'custom_wc_add_orders',
            'permission_callback'=>'custom_jwt_verify',
        ]);
        register_rest_route('orders-wc/v1', '/orders/(?P<id>\d+)', [
    'methods' => 'DELETE',
    'callback' => 'custom_wc_delete_orders',
    'permission_callback' => 'custom_jwt_verify',
        ]);
        register_rest_route('orders-wc/v1','/orders/(?P<id>\d+)',[
            'methods' => 'PUT',
            'callback' => 'custom_wc_update_orders',
            'permission_callback' => 'custom_jwt_verify'
        ]);
        register_rest_route('orders-wc/v1','/orders/(?P<id>\d+)',[
            'methods' => 'GET',
            'callback' => 'custom_wc_order_by_id',
            'permission_callback' => 'custom_jwt_verify'
        ]);

    });

    function custom_jwt_token($request)
    {
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

        //$token = JWT::encode($payload, CUSTOM_JWT_SECRET_KEY, 'HS256');
        $token=JWT::encode($payload, JWT_AUTH_SECRET_KEY, 'HS256');

        return rest_ensure_response([
            'token' => $token,
            'user_id' => $user->ID,
        ]);
    }

    function custom_jwt_verify($request) {
        $auth = $request->get_header('authorization');

        if (!$auth || !preg_match('/Bearer\s(\S+)/', $auth, $matches)) {
            return new WP_Error('unauthorized', 'No token provided', ['status' => 403]);
        }

        $token = $matches[1];

        try {
            //$decoded = JWT::decode($token, new Key(CUSTOM_JWT_SECRET_KEY, 'HS256'));
            $decoded = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
            return true;
        } catch (Exception $e) {
            return new WP_Error('unauthorized', 'Invalid token: ' . $e->getMessage(), ['status' => 403]);
        }
    }

    function custom_wc_get_orders() {
        $orders = wc_get_orders(['limit' => -1]);
        $data = [];

        foreach ($orders as $order) {
            $data[] = [
                'id' => $order->get_id(),
                'first_name' => $order->get_billing_first_name(),
                'last_name' => $order->get_billing_last_name(),
                'total' => $order->get_total(),
                'status' => $order->get_status(),
            ];
        }

        return rest_ensure_response([
            'message' => 'Orders fetched successfully',
            'orders' => $data,
        ]);
    }
function custom_wc_add_orders($request) {
        $params = $request->get_json_params();
        $customer_id = $params['customer_id'] ?? 0;
        $product_id = $params['product_id'] ?? 0;
        $quantity = $params['quantity'] ?? 1;


        $product = wc_get_product($product_id);
        if (!$product) {
            return new WP_Error('invalid_product', 'Product not found', ['status' => 404]);
        }

        
        $order = wc_create_order([
            'customer_id' => $customer_id,
        ]);

       
        $order->add_product($product, $quantity);

        
        $order->set_address([
            'first_name' => 'Test',
            'last_name'  => 'Customer',
            'email'      => 'test@example.com',
            'phone'      => '123456789',
            'address_1'  => '123 Test St',
            'city'       => 'Test City',
            'postcode'   => '12345',
            'country'    => 'IN',
        ], 'billing');

        $order->calculate_totals();

        return rest_ensure_response([
            'message'  => 'Order created successfully',
            'order_id' => $order->get_id(),
        ]);
    }
function custom_wc_delete_orders($request) {
    $order_id = $request['id'];
    $order = wc_get_order($order_id);

    if (!$order) {
        return new WP_Error('no_order', 'Order not found', ['status' => 404]);
    }

    $order->delete(true);

    return rest_ensure_response([
        'message' => 'Order deleted successfully',
        'order_id' => $order_id,
    ]);
}

function custom_wc_update_orders($request){
    $order_id=$request->get_param('id');
    //get order
    $order=wc_get_order($order_id);
    if(!$order){
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Order not found'
        ], 404);    
    }

    $params=$request->get_json_params();
     if (isset($params['billing'])) {
        $billing = $params['billing'];
        $order->set_billing_first_name($billing['first_name'] ?? '');
        $order->set_billing_last_name($billing['last_name'] ?? '');
        $order->set_billing_email($billing['email'] ?? '');
        $order->set_billing_phone($billing['phone'] ?? '');
        $order->set_billing_address_1($billing['address_1'] ?? '');
        $order->set_billing_city($billing['city'] ?? '');
        $order->set_billing_postcode($billing['postcode'] ?? '');
        $order->set_billing_country($billing['country'] ?? '');
    }

   if (isset($params['status'])) {
        $order->set_status($params['status']); 
    }

    $order->save();
    return rest_ensure_response( [
        'success' => true,
        'message' => 'order updated successfully',
        'order_id' => $order->get_id(),
        'status' => $order->get_status(),
    ] ,200);
}


function custom_wc_order_by_id($request){
    $order_id=$request->get_param('id');
    //fetch order
    $order=wc_get_order($order_id);
    if(!$order_id){
        return rest_ensure_response( [
            'success' => false,
            'message' => 'order not found'
        ],404 );
    }   
    return rest_ensure_response([
        'success' => true,
        'order' => [
            'id' => $order->get_id(),
            'customer_id' => $order->get_customer_id(),
            'billing' => [
                'first_name' => $order->get_billing_first_name(),
                'last_name' => $order->get_billing_last_name(),
                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone(),
                'address_1' => $order->get_billing_address_1(),
                'city' => $order->get_billing_city(),
                'postcode' => $order->get_billing_postcode(),
                'country' => $order->get_billing_country(),
            ],
            'total' => $order->get_total(),
            'status' => $order->get_status(),
            'line_items' => array_map(function($item) {
                return [
                    'product_id' => $item->get_product_id(),
                    'product_name' => $item->get_name(),
                    'quantity' => $item->get_quantity(),
                    'total' => $item->get_total()
                ];
            }, $order->get_items())
        ]
    ]);
}