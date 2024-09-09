# CommerceBird for WooCommerce

This is the assistance plugin for CommerceBird. This plugin allows you to connect your store to Zoho Inventory, Zoho CRM, Exact Online and more for syncing all your Orders, Products and Customers in realtime. Furthermore, this plugin enables the Purchase Order functionality that can be used from https://app.commercebird.com (requires business plan or higher).

This plugin is certified by Zoho and Exact Online and is also available in both the Zoho Marketplace and Exact App Store.

## How to install and test
1. run `composer dump-autoload` if classes added or changed
2. run `composer install` on root dir (first time only)
3. Go to admin/assets and run `npm i && npm run dev`
4. Go to wp-admin and activate plugin

## How to build a zip on MacOs
1. run ./build-mac.sh
2. use .zip file as production version.

## How to build a zip on Windows (requires WinRAR)
1. run ./build.sh
2. use .zip file as production version.

## Features:
- Manage Orders, Products and Customers in CommerceBird App.
- Create Purchase Orders based on low - out of stock items directly in CommerceBird.
- Sell in person with the POS CommerceBird App available for iOS and Android.
- Integrate with Zoho Inventory, CRM and Exact Online for realtime sync of all data.
- Webhook sync: updates price, name and stock level in WooCommerce when updated in Zoho or Exact Online
- Supports Product Bundles, WC Subscriptions
- Supports Tax mapping and WC EU VAT plugin
- Supports Product Details: weight, length, width, height


## Support:
Please read the documentation on https://support.commercebird.com for questions and trouble-shooting. If you are facing other issues, please get in touch with us and we will respond within 2 working days.


