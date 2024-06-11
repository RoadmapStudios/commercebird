# WooCommerce Zoho Inventory Connect

This is the realtime API plugin between WooCommerce and Zoho Inventory. Easily sync customers, products and orders to your Zoho Inventory account.

This plugin is certified by Zoho and is also available in the Zoho Marketplace.

## How to install and test
1. run `composer dump-autoload` if classes added or changed
2. run `composer install` on root dir (first time only)
3. Go to admin/assets and run `npm i && npm run dev`
4. Go to wp-admin and activate plugin

## How to build a zip
1. run ./build-mac.sh
2. use .zip file as production version.

## Features:
- Sync customers as contacts to Zoho
- Sync products as items to Zoho
- Sync orders as Sales Orders to Zoho on checkout or manually
- Bi-directional Sync: Updates product price, name and stock level in WooCommerce when updated in Zoho Inventory and vice versa
- Historical Data Sync: all items and contacts
- Supports Tax mapping
- Supports Product Details: weight, length, width, height


## Support:
Please read the documentation on our website for questions and trouble-shooting. If you are facing other issues, please get in touch with us and we will respond within 2 working days.


