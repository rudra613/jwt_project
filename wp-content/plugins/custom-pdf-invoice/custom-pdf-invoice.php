    <?php
    /**
     * Plugin Name: WooCommerce Bill Generator
     * Description: Adds a "Generate Bill" button on the Thank You page to download PDF invoice.
     * Version: 1.0
     * Author: Rudra Pandit
     */

    use Dompdf\Dompdf;
    use Dompdf\Options;

    require_once __DIR__ . '/vendor/autoload.php';

    //create button Generate Bill
    add_action('woocommerce_thankyou', 'wc_custom_generate_bill_button', 20);
    function wc_custom_generate_bill_button($order_id) {
        echo '<p><a class="button" target="_blank" href="' . esc_url(add_query_arg([
            'generate_bill_pdf' => 'yes',
            'order_id' => $order_id
        ])) . '">Generate Bill (PDF)</a></p>';
    }

    // generate pdf
    add_action('init', 'wc_handle_pdf_generation');
    function wc_handle_pdf_generation() {
        if (isset($_GET['generate_bill_pdf']) && $_GET['generate_bill_pdf'] === 'yes' && isset($_GET['order_id'])) {
            $order_id = intval($_GET['order_id']);
            $order = wc_get_order($order_id);

            if (!$order) {
                wp_die('Invalid order.');
            }

            ob_start();
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
                                        Payment Method: <?php echo $order->get_payment_method_title(); ?>
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
                            $product = $item->get_product();
                            $qty = $item->get_quantity();
                            $regular_price = $product ? $product->get_regular_price() : 0;
                            $sale_price = $product ? ($product->get_sale_price() ?: $regular_price) : 0;
                            $discount = ($regular_price - $sale_price) * $qty;
                            $line_total = $item->get_total();
                        ?>
                            <tr align='center'>
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
                if (!empty($order_taxes)) {
                    echo '<h3>Tax Breakdown</h3>';
                    echo '<table class="tax-table"><tr><th>Tax Name</th><th>Rate</th><th>Amount</th></tr>';
                    foreach ($order_taxes as $tax_item) {
                        $tax_data = $tax_item->get_data();
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
                    <tr>
                        <td><strong>Subtotal:</strong></td>
                        <td><?php echo wc_price($order->get_subtotal()); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Tax:</strong></td>
                        <td><?php echo wc_price($order->get_total_tax()); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Shipping:</strong></td>
                        <td><?php echo wc_price($order->get_shipping_total()); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Total:</strong></td>
                        <td><?php echo wc_price($order->get_total()); ?></td>
                    </tr>
                </table>

                <h3>Total Amount: <?php echo $order->get_formatted_order_total(); ?></h3>

                <br><br>
                <table style="width: 100%; margin-top: 4
                0px;">
                    <tr>
                        <td style="text-align: right;">
                            <p>Authorized Signature</p>
                            <img src="<?php echo plugin_dir_url(__FILE__) . 'signature.png'; ?>" alt="Signature" height="20">
                        </td>
                    </tr>
                </table>
            </div>
            <?php
            $html = ob_get_clean();

            $style = '
            <style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
            .invoice-box { max-width: 800px; margin: auto; padding: 20px; border: 1px solid #eee; }
            h2, h3 { margin: 10px 0; }
            table { width: 100%; line-height: inherit; text-align: left; border-collapse: collapse; margin-top: 10px; }
            table th, table td { border: 1px solid #ddd; padding: 8px; }
            .top table td { padding-bottom: 20px; }
            .title h2 { font-size: 30px; }
            .information table td { padding-bottom: 20px; }
            .item-table th { background: #f0f0f0; }
            .tax-table th { background: #f9f9f9; }
            </style>';

            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml('<html><head><meta charset="UTF-8">' . $style . '</head><body>' . $html . '</body></html>');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $dompdf->stream("invoice-order-{$order_id}.pdf", ["Attachment" => 0]);
            exit;
        }
    }

