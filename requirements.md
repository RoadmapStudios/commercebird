# Requirements for Exact Online

## APIs

`${exact}/mapping/item`,
`${exact}/mapping/customer`,
[9:34 AM] customer mapping: send company name + email id
[9:34 AM] both endpoints need to return the item_id or account_id

```php
<?php

$request = new HttpRequest();
$request->setUrl('https://api.commercebird.com/customs/exact/item');
$request->setMethod(HTTP_METH_POST);

$request->setQueryData([
  'token' => 'b73da454-a52f-5e02-93af-07a2e6703bdc',
  'page' => '3'
]);

$request->setHeaders([
  'Accept' => '*/*',
  'zohowooagent' => 'https://dev.wooventory.com',
  'Content-Type' => 'application/json'
]);

$request->setBody('{
  "sku": "hello-manish"
}');

try {
  $response = $request->send();

  echo $response->getBody();
} catch (HttpException $ex) {
  echo $ex;
}
```

# Exact Online

## Access App Console (https://app.commercebird.com/integrations)

## Tabs needed:

Connect
Products
Customers
CostCenters/Units

## Once we have the Exact Item ID saved as product meta (key = eo_item_id) > include that meta as order line items meta

```php
add_action( 'woocommerce_checkout_update_order_meta', 'my_custom_checkout_field_update_order_meta' );

function my_custom_checkout_field_update_order_meta( $order_id ) {
$order = new WC_Order( $order_id );
// Get the user ID associated with the order
$user_id = $order->get_user_id();

    // Check if the order is associated with a user
    if ($user_id > 0) {
        // Get the user meta value based on the user ID and meta key
        $user_meta_value = get_user_meta($user_id, 'eo_account_id', true);

        // Add the user meta as order meta
        if (!empty($user_meta_value)) {
            $order->update_meta_data('eo_account_id', $user_meta_value);
            $order->save();
        }
    }

}
```

## Have updated the above code according to real functions in woocommerce plugin

```php
// Add eo_item_id as line item meta when order is created
add_action('woocommerce_new_order_item', 'update_order_item_with_product_meta', 10, 3);

function update_order_item_with_product_meta($item_id, $item, $order_id) {
// Get the product ID associated with the order item
$product_id = $item->get_product_id();

    // Check if the product is associated with a product
    if ($product_id > 0) {
        // Get the product meta value based on the product ID and meta key
        $product_meta_value = get_post_meta($product_id, 'eo_item_id', true);

        // Add the product meta as order item meta
        if (!empty($product_meta_value)) {
            wc_add_order_item_meta($product_id, 'eo_item_id', $product_meta_value);
        }
    }

}
```

## Order Sync

Not at home now
But please add the tab Orders in exact online page
And with two date fields for date range
Start Date & End Date
Those needs to be sent in request body
It will return the ID and Description (edited)
Description is the order id (edited)
Find the order and save it as eo_order_id
Manish will push the server to live tomorrow morning
Then we can test and finish it
Also add one more button called “Export Orders” and connect with a php function. I will write the php logic myself cause I need to make sure it doesn’t overload the server
