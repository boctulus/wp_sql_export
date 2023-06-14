<?php

/*
	@author boctulus
*/

namespace boctulus\SW\core\libs;

use boctulus\SW\core\libs\Strings;
use boctulus\SW\core\libs\Debug;
use boctulus\SW\core\libs\Products;


/*
    Ver también

    https://wpdavies.dev/how-to-get-woocommerce-order-details-beginners-guide/
*/
class Orders 
{
    /*
        Ej. de params:

        $products = [
            [
                'pid' => 1178,
                'qty' => 3
            ],
            [
                'pid' => 1176,
                'qty' => 2
            ]
        ];
        
        $billing_address = array(
            'first_name' => 'Joe',
            'last_name'  => 'Conlin',
            'company'    => 'Speed Society',
            'email'      => 'joe@testing.com',
            'phone'      => '760-555-1212',
            'address_1'  => '123 Main st.',
            'address_2'  => '104',
            'city'       => 'San Diego',
            'state'      => 'Ca',
            'postcode'   => '92121',
            'country'    => 'US'
        );

        Atributos. Ej:

        [
            '_customer_user'        => $userid,
            // ...
            '_payment_method'       => 'ideal',
            '_payment_method_title' => 'iDeal'
        ]

        https://stackoverflow.com/a/50384706/980631
        
        En caso de querer crear la orden programaticamente, procesar un pago y redirigir ->
        
        https://stackoverflow.com/a/31987151/980631
    */
    static function create(Array $products, Array $billing_address = null, Array $shipping_address = null, $attributes = [])
    {   
        // Now we create the order
        $order = wc_create_order();
        
        foreach ($products as $product){
            $p   = Products::getProduct($product['pid']);
            $qty = $product['qty'];

            // The add_product() function below is located in 
            // plugins/woocommerce/includes/abstracts/abstract_wc_order.php
            $order->add_product($p, $qty); 
        }
        
        if (!empty($billing_address)){
            $order->set_address( $billing_address, 'billing' );
        }    

        if (!empty($shipping_address)){
            $order->set_address( $shipping_address, 'shipping' );
        }

        //
        $order->calculate_totals();

        if (!empty($attributes)){
            foreach ($attributes as $att_name => $att_value){
                update_post_meta($order->id, $att_name, $att_value);
            }
        }
        
        return $order;
    }

    /*
        Create a bunch of random orders
    */
    static function createRandom(int $qty_orders = 10, Array $product_ids = null, Array $user_ids = null){
        $order_ids = [];

        for ($i=0; $i< $qty_orders; $i++){
            if (empty($product_ids)){    
                $product_ids = Products::getRandomProductIds(rand(1,4));
            }

            $products = [];
            foreach ($product_ids as $pid){
                $products[] = [
                    'pid' => $pid,
                    'qty' => rand(1,5)
                ];
            }

            if (empty($user_ids)){
                $user_ids = Users::getUserIDList();
            }

            $user_id = $user_ids[array_rand($user_ids,1)];

            $order = Orders::create($products, null, null, [
                '_customer_user' => $user_id,
            ]);
        
            $order_ids[] = $order->get_id();
        }

        return $order_ids;
    }

    static function createRandomByRoles(int $qty_orders = 10, Array $user_roles = ['customer']){
        $order_ids = [];

        $user_ids = Users::getUsersByRole($user_roles);       

        for ($i=0; $i< $qty_orders; $i++){
            $pids = Products::getRandomProductIds(rand(1,4));
        
            $products = [];
            foreach ($pids as $pid){
                $products[] = [
                    'pid' => $pid,
                    'qty' => rand(1,5)
                ];
            }

            $user_id = $user_ids[array_rand($user_ids,1)];
        
            $order = Orders::create($products, null, null, [
                '_customer_user' => $user_id,
            ]);
        
            $order_ids[] = $order->get_id();
        }

        return $order_ids;
    }

    static function setOrderStatus($order, $new_status, $note = null){
        if (!empty($new_status)){
            $order->update_status($new_status, $note);
        }
    }

    static function setLastOrderStatus($status){
        static::setOrderStatus(static::getLastOrder(), $status);
    }

    // setea status pero ademas agrega nota
    static function addNote($order, string $new_status, string $note){
        $order->update_status($new_status, $note);
    }

    static function setCustomerId($order, $user_id){
        $order->set_customer_id($user_id);
    }

    static function getOrderId(\WC_Order $order){
        return trim(str_replace('#', '', $order->get_order_number()));
    }

    static function getOrderById($order_id) : \WC_Order {
        // Get an instance of the WC_Order object (same as before)
         return wc_get_order($order_id);
    }

    static function orderExists($order_id) : bool {
        return wc_get_order($order_id) !== false;
    }

    // https://stackoverflow.com/a/46690009/980631
    static function getLastOrderId(){
        global $wpdb;
        $statuses = array_keys(wc_get_order_statuses());
        $statuses = implode( "','", $statuses );
    
        // Getting last Order ID (max value)
        $results = $wpdb->get_col( "
            SELECT MAX(ID) FROM {$wpdb->prefix}posts
            WHERE post_type = 'shop_order'
            AND post_status IN ('$statuses')
        " );
        return reset($results);
    }

    static function getLastOrderById(){
        $id = static::getLastOrderId();

        if (empty($id)){
            return;
        }

        return static::getOrderById($id);
    }

    static function getLastOrder(){
        return \wc_get_order(
            static::getLastOrderId()
        );
    }

    /*
        Utilizar para obtener la cantidad de unidades vendidas a precio Plus de cierto producto 
    */
    static function getRecentOrders($days = 30, $user_id = null){
        $args = array(            
            'date_created' => '>' . ( time() - (DAY_IN_SECONDS * $days)),
            'limit' => '-1'
        );

        if ($user_id !== null){
            $args['customer_id'] = $user_id;
        }
        
        $orders = wc_get_orders( $args );

        return $orders;
    }

    static function getOrderItems(\Automattic\WooCommerce\Admin\Overrides\Order $order_object){
        return $order_object->get_items();
    }

    static function getOrderData(\Automattic\WooCommerce\Admin\Overrides\Order $order_object){
        $order_data = $order_object->get_data(); // The Order data

        $order_status   = $order_data['status'];
        $order_currency = $order_data['currency'];
        $order_payment_method = $order_data['payment_method'];
        $order_payment_method_title = $order_data['payment_method_title'];

        return [
            'status' => $order_status,
            'currency' => $order_currency,
            'payment_method' => $order_payment_method,
            'payment_method_title' => $order_payment_method_title
        ];
    }

    static function getShippingCosts(\Automattic\WooCommerce\Admin\Overrides\Order $order){
        $shipping_total = $order->get_shipping_total();
        $shipping_tax   = $order->get_shipping_tax();

        return [
            'total' => $shipping_total,
            'tax'   => $shipping_tax
        ];
    }

    static function getCustomerNotes(\Automattic\WooCommerce\Admin\Overrides\Order $order){
        return $order->get_customer_order_notes();
    }

    /*
        https://stackoverflow.com/a/43464103/980631
    */
    static function getOrderNotes($order, $author = null){
        if (is_numeric($order)){
            $order_id = $order;
        } else {
            $order_id = Orders::getLastOrderId();
        }

        $and_author = ($author !== null) ? "AND `comment_author` = '$author'" : '';
        
        global $wpdb;
    
        $table_perfixed = $wpdb->prefix . 'comments';

        $sql = "
            SELECT *
            FROM $table_perfixed
            WHERE  `comment_post_ID` = $order_id
            AND  `comment_type` LIKE  'order_note' 
            $and_author
        ";

        $results = $wpdb->get_results($sql);
    
        foreach($results as $note){
            $order_note[]  = array(
                'note_id'      => $note->comment_ID,
                'note_date'    => $note->comment_date,
                'note_author'  => $note->comment_author,
                'note_content' => $note->comment_content,
            );
        }

        return $order_note;
    }

    static function getLastOrderNoteMessage($order, $author = null){
        if (is_numeric($order)){
            $order_id = $order;
        } else {
            $order_id = Orders::getLastOrderId();
        }

        $and_author = ($author !== null) ? "AND `comment_author` = '$author'" : '';
        
        global $wpdb;
    
        $table_perfixed = $wpdb->prefix . 'comments';

        $sql = "
            SELECT *
            FROM $table_perfixed
            WHERE  `comment_post_ID` = $order_id
            AND  `comment_type` LIKE  'order_note' 
            $and_author
            ORDER BY `comment_date` DESC
            LIMIT 1
        ";
        
        // selecciono 'comment_content' (posicion 8)
        $val = $wpdb->get_var($sql, 8);

        return $val;
    }

    /*
        https://www.hardworkingnerd.com/woocommerce-how-to-get-a-customer-details-from-an-order/
    */
    static function getCustomerData(\Automattic\WooCommerce\Admin\Overrides\Order $order){
        // Get the customer or user id from the order object
        $customer_id = $order->get_customer_id();

        //this should return exactly the same number as the code above
        $user_id = $order->get_user_id();

        // Get the WP_User Object instance object
        $user = $order->get_user();

        /*
            Billing
        */
        $billing_first_name = $order->get_billing_first_name();
        $billing_last_name  = $order->get_billing_last_name();
        $billing_company    = $order->get_billing_company();
        $billing_address_1  = $order->get_billing_address_1();
        $billing_address_2  = $order->get_billing_address_2();
        $billing_city       = $order->get_billing_city();
        $billing_state      = $order->get_billing_state();
        $billing_postcode   = $order->get_billing_postcode();
        $billing_country    = $order->get_billing_country();

        //note that by default WooCommerce does not collect email and phone number for the shipping address
        //so these fields are only available on the billing address
        $billing_email  = $order->get_billing_email();
        $billing_phone  = $order->get_billing_phone();

        $billing_display_data = Array(
            "First Name" => $billing_first_name,
            "Last Name" => $billing_last_name,
            "Company" => $billing_company,
            "Address Line 1" => $billing_address_1,
            "Address Line 2" => $billing_address_2,
            "City" => $billing_city,
            "State" => $billing_state,
            "Post Code" => $billing_postcode,
            "Country" => $billing_country,
            "Email" => $billing_email,
            "Phone" => $billing_phone
        );

        /*
            Shipping
        */

        // Customer shipping information details
        $shipping_first_name = $order->get_shipping_first_name();
        $shipping_last_name  = $order->get_shipping_last_name();
        $shipping_company    = $order->get_shipping_company();
        $shipping_address_1  = $order->get_shipping_address_1();
        $shipping_address_2  = $order->get_shipping_address_2();
        $shipping_city       = $order->get_shipping_city();
        $shipping_state      = $order->get_shipping_state();
        $shipping_postcode   = $order->get_shipping_postcode();
        $shipping_country    = $order->get_shipping_country();

        $shipping_display_data = Array(
            "First Name" => $shipping_first_name,
            "Last Name" => $shipping_last_name,
            "Company" => $shipping_company,
            "Address Line 1" => $shipping_address_1,
            "Address Line 2" => $shipping_address_2,
            "City" => $shipping_city,
            "State" => $shipping_state,
            "Post Code" => $shipping_postcode,
            "Country" => $shipping_country,
            "Note" => $order->get_customer_note()
        );

        return [
            'customer_id' => $customer_id,
            'user_id'     => $user_id,
            'billing'  => $billing_display_data,
            'shipping' => $shipping_display_data
        ];

    }

    static function orderItemId(\WC_Order_Item_Product $item) {
        if ($item === null){
            throw new \InvalidArgumentException("Se espera objeto de tipo WC_Order_Item_Product. Recibido NULL");
        }

        if (!is_object($item)){
            throw new \InvalidArgumentException("Se espera objeto de tipo WC_Order_Item_Product");
        }

        //Get the product ID
        return $item->get_product_id();
    }

    /*
        Recibe instancia de WC_Order_Item_Product y devuelve array

        https://stackoverflow.com/a/45706318/980631
    */
    static function orderItemToArray($item) {
        if ($item === null){
            throw new \InvalidArgumentException("Se espera objeto de tipo WC_Order_Item_Product. Recibido NULL");
        }

        if (!is_object($item)){
            dd($item);
            throw new \InvalidArgumentException("Se espera objeto de tipo WC_Order_Item_Product");
        }

        //Get the product ID
        $product_id   = $item->get_product_id();

        if (empty($product_id)){
            $order_id = $item->get_order_id();

            throw new \Exception("Inesperado. Esta vacio product_id para un producto en order_id = '$order_id'");
        }

        //Get the variation ID
        $variation_id = $item->get_variation_id();

        //Get the WC_Product object
        $product = $item->get_product();

        if (empty($product)){
            dd($item, 'ITEM');
            dd($product_id, 'PRODUCT ID');
            throw new \Exception("producto no encontrado");
        }

        // The quantity
        $quantity = $item->get_quantity();

        // The product name
        $product_name = $item->get_name(); // … OR: $product->get_name();

        //Get the product SKU (using WC_Product method)
        $sku = $product->get_sku();

        // Get line item totals (non discounted)
        $total_non_discounted     = $item->get_subtotal(); // Total without tax (non discounted)
    
        // Get line item totals (discounted when a coupon is applied)
        $total_discounted     = $item->get_total(); // Total without tax (discounted)

        /*
            Impuestos
        */

        $total_tax_non_discounted = $item->get_subtotal_tax(); // Total tax (non discounted)
        $total_tax_discounted = $item->get_total_tax(); // Total tax (discounted)

        return [
            'product_id'   => $product_id,
            'product_type' => $variation_id > 0 ? 'variable' : 'simple',

            // $item['variation_id']
            'variation_id' => $variation_id ?? null,
            'qty'          => $quantity,
            'product_name' => $product_name,
            'sku'          => $sku,
            'weight'       => $product->get_weight(),
            'price'        => $product->get_price(),  /// <-- *
            'regular_price' => $product->get_regular_price(),   
            'sale_price'   => $product->get_sale_price(), 

            'total_non_discounted' => $total_non_discounted,
            'total_discounted' => $total_discounted,

            'total_tax_non_discounted'  => $total_tax_non_discounted,
            'total_tax_discounted' => $total_tax_discounted
        ];
    }

    /*
        Atajo para obtener items a partie del objeto de la orden
    */
    static function getOrderItemArray(object $order){
        $order_items = Orders::getOrderItems($order);

        $items = [];
        foreach ( $order_items as $item_id => $item ) 
        {
            $items[] = Orders::orderItemToArray($item);
        }

        return $items;
    }
    
}