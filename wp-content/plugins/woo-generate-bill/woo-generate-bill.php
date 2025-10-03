<?php
/**
 * Plugin Name: WooCommerce Order Bill Generator
 * Description: Adds "Generate Bill" button in WooCommerce Orders admin to download PDF invoices.
 * Version: 1.0
 * Author: Rudra Pandit
 */

if (!defined('ABSPATH')) exit;// Exit if accessed directly

use Dompdf\Dompdf;
use Dompdf\Options;

add_action('plugins_loaded', function () {
    if (!class_exists('Dompdf\Dompdf')) { // check if dompdf is not already loaded so load otherwise skip
        $autoload_path = plugin_dir_path(__FILE__) . 'vendor/autoload.php';
        if (file_exists($autoload_path)) {
            require_once $autoload_path;
        }
    }
});

//adding column generate bill button
add_action('init', function () {
    if (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil') &&
        \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled())//get the custom order table enabled
         {
        add_filter('manage_woocommerce_page_wc-orders_columns', 'add_generate_bill_column');
        add_action('manage_woocommerce_page_wc-orders_custom_column', 'render_generate_bill_column', 10, 2);
    } else {
        add_filter('manage_edit-shop_order_columns', 'add_generate_bill_column');
         add_action('manage_shop_order_posts_custom_column', 'render_generate_bill_column', 10, 2);
    }
});

function add_generate_bill_column($columns) {
    $new_columns = [];

    foreach ($columns as $key => $column) {
        $new_columns[$key] = $column;
       // if ($key === 'order_date') {
        if($key === 'order_total'){
            $new_columns['generate_bill'] = __('Generate Bill', 'woocommerce');
        }
    }
    return $new_columns;
}

function render_generate_bill_column($column, $order_or_post_id) {
    $order_id = is_object($order_or_post_id) && method_exists($order_or_post_id, 'get_id')
        ? $order_or_post_id->get_id()
        : $order_or_post_id;
    // error_log($order_id);
    //adding button generate bill column
    if ($column === 'generate_bill') {
        //securly generate the pdf
        $url = wp_nonce_url(
            admin_url('admin-ajax.php?action=generate_bill_pdf&order_id=' . $order_id),//orderid through ajax help generate the pdf
            'generate_bill_' . $order_id
        );
        echo '<a class="button tips" href="' . esc_url($url) . '" target="_blank">Generate Bill</a>';
    }
}

// when called admin_ajax.php file called so create the pdf
add_action('wp_ajax_generate_bill_pdf', 'handle_generate_bill_pdf');
function handle_generate_bill_pdf() {
    $order_id = intval($_GET['order_id'] ?? 0);
    if (!wp_verify_nonce($_GET['_wpnonce'], 'generate_bill_' . $order_id)) {
        wp_die('Nonce verification failed');
    }
    $order = wc_get_order($order_id);
    // error_log(print_r($order, true ));
    if (!$order) {
        wp_die('Order not found');
    }

    ob_start();// start output buffering
    ?>
    <div class="invoice-box">
        <table cellpadding="0" cellspacing="0">
            <tr class="top">
                <td colspan="3">
                    <table>
                        <tr>
                            <td class="title">
                                <h2>Invoice</h2>
                            </td>
                            <td>
                                Order #: <?php echo $order->get_order_number(); ?><br>
                                Date: <?php echo wc_format_datetime($order->get_date_created()); ?><br>
                                Payment: <?php echo $order->get_payment_method_title(); ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr class="information">
                <td colspan="3">
                    <table>
                        <tr>
                            <td><strong>Customer Name:</strong></td>
                            <td><?php echo esc_html($order->get_formatted_billing_full_name()); ?></td>
                        </tr>
                        <tr>
                            <td>
                                <strong>Billing Address:</strong><br>
                                <?php echo $order->get_formatted_billing_address(); ?><br>
                                <?php echo $order->get_billing_email(); ?><br>
                                <?php echo $order->get_billing_phone(); ?>
                            </td>
                            <td>
                                <strong>Shipping Address:</strong><br>
                                <?php echo $order->get_formatted_shipping_address() ?: 'Same as billing'; ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <h3>Order Items</h3>
        <table class="item-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Regular Price</th>
                    <th>Sale Price</th>
                    <th>Discount</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order->get_items() as $item): 
                $itemdata=$order->get_items();
                // echo '<pre>';
                // print_r($itemdata);
                // echo '</pre>';
                    $product = $item->get_product();
                    $qty = $item->get_quantity();
                    $regular_price = $product ? $product->get_regular_price() : 0;
                    $sale_price = $product ? ($product->get_sale_price() ?: $regular_price) : 0;
                    $discount = ($regular_price - $sale_price) * $qty;
                    $line_total = $item->get_total();
                ?>
                    <tr align="center">
                        <td><?php echo esc_html($item->get_name()); ?></td>
                        <td><?php echo $qty; ?></td>
                        <td><?php echo wc_price($regular_price); ?></td>
                        <td><?php echo wc_price($sale_price); ?></td>
                        <td><?php echo wc_price($discount); ?></td>
                        <td><?php echo wc_price($line_total); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php
        $order_taxes = $order->get_items('tax');
        // echo "<pre>";
        // print_r($order_taxes);
        // echo "</pre>";
        if (!empty($order_taxes)) {
            echo '<h3>Tax Breakdown</h3>';
            echo '<table class="tax-table"><tr><th>Tax Name</th><th>Rate</th><th>Amount</th></tr>';
            foreach ($order_taxes as $tax_item) {
                $tax_data = $tax_item->get_data();
                // echo "<pre>";
                // print_r($tax_data);     
                // echo "</pre>";
                $rate_id = $tax_data['rate_id'];
                $rate_label = $tax_data['label'] ?: WC_Tax::get_rate_label($rate_id);
                $tax_amount = $tax_data['tax_total'];
                $rate_percent = WC_Tax::get_rate_percent($rate_id);
                echo '<tr align="center"><td>' . esc_html($rate_label) . '</td><td>' . esc_html($rate_percent) . '</td><td>' . wc_price($tax_amount) . '</td></tr>';
            }
            echo '</table>';
        }
        ?>

        <table class="totals-table">
            <tr><td><strong>Subtotal:</strong></td><td><?php echo wc_price($order->get_subtotal()); ?></td></tr>
            <tr><td><strong>Tax:</strong></td><td><?php echo wc_price($order->get_total_tax()); ?></td></tr>
            <tr><td><strong>Shipping:</strong></td><td><?php echo wc_price($order->get_shipping_total()); ?></td></tr>
            <tr><td><strong>Total:</strong></td><td><?php echo wc_price($order->get_total()); ?></td></tr>
        </table>

        <h3>Total Amount: <?php echo $order->get_formatted_order_total(); ?></h3>
        
        <br><br>
        <table style="width: 100%; margin-top: 40px;">
            <tr>
                <td style="text-align: right;">
                    <p>Authorized Signature</p>
                    <img src="<?php echo plugin_dir_url(__FILE__) . 'signature.png'; ?>" alt="Signature" height="20">
                </td>
            </tr>
        </table>
    </div>
    <?php
    $html = ob_get_clean();//output buffer clean

    $style = '
    <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
    .invoice-box { max-width: 800px; margin: auto; padding: 20px; border: 1px solid #eee; }
    h2, h3 { margin: 10px 0; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    table th, table td { border: 1px solid #ddd; padding: 8px; }
    .top table td { padding-bottom: 20px; }
    .title h2 { font-size: 30px; }
    .information table td { padding-bottom: 20px; }
    .item-table th { background: #f0f0f0; }
    .tax-table th { background: #f9f9f9; }
    </style>';

    $options = new Options();
    $options->set('defaultFont', 'DejaVu Sans');//set default font of pdf

    $dompdf = new Dompdf($options);//dompdf instance
    $dompdf->loadHtml('<html><head><meta charset="UTF-8">' . $style . '</head><body>' . $html . '</body></html>');//load the html content
    $dompdf->setPaper('A4', 'portrait');//paper size and orientation
    $dompdf->render();//render the pdf
    $dompdf->stream("invoice-order-{$order_id}.pdf", ["Attachment" => false]);//open in browser not download

    exit;
}
