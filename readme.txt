=== CommerceBird ===
Contributors: fawadinho
Author link: https://commercebird.com
Tags: shop,store,ecommerce,commerce,e-commerce
Requires at least: 6.5
Tested up to: 6.7.1
Requires PHP: 7.4
Stable tag: 2.2.15
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Elevate WooCommerce to the next level by turning it into a full ERP system.

== Description ==
https://youtu.be/avQ08fG9tPw

= The #1 ERP Platform for WooCommerce =

This plugin ensures you can take the most of the CommerceBird App such as uploading product images, using integrations like Zoho Inventory, Zoho CRM & Exact Online.
Subscribe to the 14-days Free Trial on: [commercebird.com](https://commercebird.com).

= 🗝️ Key features: =
- **Extremely Fast**: Utilizing the WooCommerce REST API and fully built in JavaScript.
- **Product Blaze Editor**: our highly praised Product Management to manage products 10x easier & faster than WP Admin.
- **Instant Purchase Orders**: Create purchase orders directly from the order or low / out-of stock items in one click!
- **Advanced Stock Management**: Instant stock update with the fastest user friendly User Interface.
- **Certified Integrations**: Connect your store 2-way to Zoho Inventory, CRM or Exact Online to sync Orders, Customers and Products.
- **POS Module**: Accept in-store payments in cash or via terminal payments (Native iOS and Android app).
- **Analytics Pro**: Generate a graph based on prompt using A.I. and get valuable tips to grow your store (soon).

Terms: [TOS](https://commercebird.com/terms-conditions)
Privacy Policy: [Privacy Policy](https://commercebird.com/privacy-policy/)

= ⏩ Use of 3rd Party Services =

To improve the user experience, CommerceBird may use the following 3rd party services if the feature is enabled:

- Zoho CRM and Inventory API to synchronize Orders, Products and Customers –  [TOS](https://www.zoho.com/terms.html) and [Privacy Policy](https://www.zoho.com/privacy.html)
- Exact Online API to synchronize Orders, Products and Customers - [TOS](https://www.exact.com/terms-and-conditions) and [Privacy Policy](https://www.exact.com/privacy-statement)

== Frequently Asked Questions ==

= Q: Do I need a paid plan to use this plugin? =
A: No, you just need to create an account on [commercebird.com](https://commercebird.com) to use this plugin.

= Q: Which product types does it support? =
A: Simple, Variable, Bundles, Simple Subscription & Variable Subscription.

= Q: Can I create Purchase Orders? =
A: Yes! you can also add the cost price on the product edit page in the app.

== Q: Which Integrations does it support? =
A: Zoho CRM, Zoho Inventory and Exact Online. The integrations require the Business Plan or higher. Missing a particular integration? Let us know!

== Q: What plan do I need for the integrations? =
A: The Business plan for the most Integration features, some features require the Premium plan. Please check the [Pricing](https://commercebird.com/pricing) for more info.

== Q: Is my WooCommerce data safe with CommerceBird? =
A: Yes, as a matter of fact, we save 0 data in our own server as the app is fully dependent on the WooCommerce API. Meaning; you own your data!

== Q: Where can I see the documentation and contact support?
A: Please visit the [Support site](https://support.commercebird.com) to read the docs and open support ticket

== Q: How can I contribute as a developer?
A: Please fork the repository first on [Github](https://github.com/RoadmapStudios/commercebird) and then create a PR.

== Installation ==

**To install CommerceBird, follow these steps:**

1. Download and activate the plugin
2. Go to CommerceBird.com and subscribe for a free account.
3. Copy the subscription ID from the dashboard
4. Go to your website wp-admin > commercebird > paste the subscription id and click save
5. Enable CORS if you are seeing a CORS error on the native app. You can download it from the Google Play store or Apple App Store.

== Screenshots ==
01. CommerceBird homepage
02. Commercebird App connect page
03. Commercebird App dashboard
04. Commercebird App sales orders
05. Commercebird App products listing
06. Commercebird App product edit


== Changelog ==
= 2.2.15 - February 6th 2025 =
* Fix: fatal error of autoload.php not found
* Tweak: subscription data will get saved for one week

= 2.2.14 - February 4th 2025 =
* Fix: webhook sync of products from zoho inventory to woocommerce
* Fix: export orders to Zoho CRM
* Tweak: price and stock will get imported from Zoho on manual sync

= 2.2.13 - January 24 2025 =
* Fix: get subscription on slow servers
* Fix: available stock via cron
* Tweak: secured product and shipping status webhook
* Tweak: duplicated categories will be removed automatically in WooCommerce
* Compatibility with WooCommerce 9.6

= 2.2.12 - Januari 13th 2025 =
* Fix: not all categories were saved for syncing from Zoho Inventory
* Fix: license key activation
* Fix: excessive api calls for group items from zoho inventory
* Tweak: improved get costunits and costcenters from Exact Online

= 2.2.11 - January 4th 2025 =
* New: activation now requires also email address in addition to subscription ID
* Fix: stock update due to Zoho API changes of stock labels
* Fix: image duplication fixed by validation improvement
* Fix: tax enabled/disabled is respected during product import from Zoho Inventory
* Fix: User Interface elements not showing properly

= 2.2.10 - December 12 2024 =
* New: support and documentation support directly via plugin
* Removal: product brands taxonomy as its now part of WC Core
* Fix: featured image import from Zoho due to strict validation
* Fix: stock update for quantity 0
* Fix: zoho customer update via my-account page

= 2.2.9 - December 1th 2024 =
* New: Group Items will be created via Webhook now
* Minor bugfixes
* Tested up to WooCommerce 9.4.2

= 2.2.8 - November 9 2024 =
* New: added support for GST India during Contact import
* Fix: pagination of Items api calls failed due to action scheduler bug
* Improvement: orphaned postmeta will be deleted automatically during import items

= 2.2.7 - November 5 2024 =
* Fix: connection saving not working for all hosting
* Fix: icons vue not showing
* Tweak: added English POT language

= 2.2.6 - October 31 2024 =
* Fix: connection saving
* Fix: getting all product brands via app

= 2.2.5 - October 30 2024 =
* Fix: update also zoho contact person phone during customer update
* Fix: ajax error on saving plugin settings

= 2.2.4 - October 28 2024 =
* Fix: update phone number during customer update to Zoho Inventory
* Tweak: added 'cmbird' prefix to all options

= 2.2.3 - October 19 2024 =
* New: Purchase Orders in WooCommerce using WC API
* Fix: Zoho Inventory Purchase Orders API - vendors
* Fix: cronjob not scheduled for new plugin users

= 2.2.2 - September 11 2024 =
* Fix: fatal error on order edit page

= 2.2.1 - September 9 2024 =
* Fix: inactive products were saved as private instead of draft
* Fix: product images upload via the app on app.commercebird.com.
* Tweak: added support for Zoho Inventory API Rate Limits for items export
* Improved security to meet WordPress Coding Standards 2024

= 2.2.0 - August 22 2024 =
* New: support for Zoho Inventory Purchase Orders via CommerceBird app
* New: support for GLAccounts on customer level for Exact Online
* Tweak: renamed Cron tab name to Categories for Zoho Inventory
* Fix: categories selection was not saving new categories
* Fix: image import class refactored - reduced code with 70%

= 2.1.26 - July 7 2024 =
* New: ability to type woo field name for custom orders fields mapping
* Fix: categories shown twice on cron tab
* Fix: prevent sync of order multiple times in same minute
* Fix: php error for getting image mime type

= 2.1.25 - July 2 2024 =
* New: webhook sync Items and Stock change for Exact Online
* Fix: manage stock disabling overwritten by cron

= 2.1.24 - June 21 2024 =
* Fix: product save was giving json output
* Tweak: secured all ajax calls with wp nonce

= 2.1.23 - June 18 2024 =
* Fix: importing group items variations
* Tweak: importing group items variations now also possible via product edit page manual sync
* Tweak: disabled review banner
* Compatibility with WooCommerce 9.0

= 2.1.22 - June 14 2024 =
* Fix: featured image sync now compatible with WordPress 6.5
* Fix: variation sync via webhook will now create new attribute option if not found in attributes

= 2.1.21 - June 5 2024 =
* Fix: simple products import via cron
* Fix: disable product sync now also applies to changes made via the app
* Tweak: improved pagination of products import
* Compatibility with WooCommerce 8.9

= 2.1.20 - May 31 2024 =
* Fix: variation import of existing parent product via cron
* Fix: zoho inventory salesorder url on orders listing is restored
* Fix: dismiss review banner
* Fix: disable product sync to zoho restored
* Fix: disable stock sync restored
* Tweak: zoho image import fail will no longer break sync of rest of category
* Tweak: zoho image name must be unique when changed for existing item

= 2.1.19 - May 29 2024 =
* Fix: warehouse stock sync
* Fix: group items import
* Fix: brand import of group items
* Tweak: order prefix will be empty if not set
* Tweak: featured image of first variation will now be featured image of parent variable product

= 2.1.18 - May 14 2024 =
* Fix: webhook sync of simple items
* Fix: manual item sync from woo to zoho - now secure with nonce
* New: added Zoho Purchase Price as Cost Price as product meta

= 2.1.17 - May 8 2024 =
* Fix: duplication of images
* Tweak: zoho api domain updated to zohoapis
* Tweak: removed standard purchase price for variations
* Revert: tax mapping (now as optional)

= 2.1.16 - April 27 2024 =
* Fix: featured image import of Zoho Inventory product
* Fix: group items import of Zoho Inventory
* Fix: order status draft for Zoho Inventory
* Fix: product description import of Zoho Inventory

= 2.1.15 - April 18 2024 =
* New: product brands taxonomy
* Fix: custom fields of products
* Reverted: shipping charge tax
* Compatible with WooCommerce 8.8

= 2.1.14 - April 9 2024 =
* Fix: address update for existing customers of Zoho Inventory
* Removed: shipping charge tax

= 2.1.13 - April 3 2024 =
* Fix: order sync
* Fix: orders with coupon sync
* Removed: tax mapping - no longer required by zoho api

= 2.1.12 - April 1 2024 =
* Removed: package sync - as its now part of Sales Order Cycle in Zoho
* Fix: remove 'deleted product' from dB cache in order to resync
* Fix: webhook changes reverted

= 2.1.11 - March 29 2024 =
* Fix: PHP 8.x improved compatibility

= 2.1.10 - March 27 2024 =
* Fix: PHP 8 error
* Fix: simple items import
* Fix: only run cronjob if zoho is connected

= 2.1.9 - March 25 2024 =
* Fix: first time stock update via inventory adjustment
* Fix: child items of bundles not added
* Fix: cronjob not updating variations
* Compatibility with WooCommerce 8.7 verified

= 2.1.8  - March 20 2024 =
* New: customer import with cron option
* New: support for Advanced Coupons plugin - customer credit
* Fix: image import
* Fix: order sync

= 2.1.7 - March 13 2024 =
* Fix: checkout fields readonly conflict
* Fix: cronjob adjustment not working
* Fix: package sync
* Fix: composite items import

= 2.1.6 - March 7th 2024 =
* New: connect Zoho Inventory to Exact Online
* Fix: toggle save options

= 2.1.5 - Februari 29 2024 =
* New: Zoho CRM tab to bulk export orders to Zoho CRM
* Improvement: bulk sync orders to Exact Online in background automatically once a day
* Fix: Enable auto-generated sales order number for Zoho Inventory
* Refactored core for wordpress.org
* Compatibility with WooCommerce 8.6

= 2.1.4 - Februari 18 2024 =
* Fix: webhook sync item status
* Fix: bulk export orders to Exact Online date range

= 2.1.3 - Februari 13 2024 =
* New: bulk map orders with Exact Online
* New: bulk export orders to Exact Online
* Fix: customer mapping on checkout for Zoho Inventory

= 2.1.2 - Februari 8 2024 =
* New: item custom fields via webhook sync
* Improvement: order with bundle item will now skip its child items
* Fix: duplication of images import
* Fix: description will be imported as short description

= 2.1.1 - Februari 2nd 2024 =
* Tweak: restored Item's custom fields in cron
* Improvement: purge action scheduler log after a week

= 2.1.0 - Februari 1 2024 =
* New: Exact Online tab for bulk mapping & import items and customers
* Tweak: improved error handling in case of bad Zoho connection
* Tweak: improved Zoho order sync by separating customer sync
* Fix: only import active group items

= 2.0.11 - January 24 2024 =
* Fix: custom fields mapping of orders
* Fix: unusable connect tab if wrong organisation id entered
* Improvement: connection with CommerceBird app
* Improvement: reduced the number of unneccessary webhook triggers to Zoho CRM & Exact Online

= 2.0.10 - January 16th 2024 =
* Fix: cron import simple items
* Fix: User Interface bugs

= 2.0.9 - January 15th 2024 =
* Fix: custom order fields sync - requires label now instead of ID
* Tweak: migration to commercebird.com preparation

= 2.0.8 - January 11th 2024 =
* Fix: product create via webhook

= 2.0.7 - January 9th 2024 =
* Fix: featured image import
* Fix: webhook item sync
* Tweak: Imagick module no longer required for image import

= 2.0.6 - January 7th 2024 =
* Tweak: orders sync now via Action Scheduler
* Fix: stock items import
* Compatible with WooCommerce 8.5

= 2.0.5 - December 27th 2023 =
* Fix: import group items

= 2.0.4 - December 17th 2023 =
* Fix: import items "invalid sku" error
* Fix: import item image

= 2.0.3 - December 6th 2023 =
* Fix: Webhooks sync
* Tweak: added warning for missing Imagick library

= 2.0.2 - November 30 2023 =
* Fixed Cronjob

= 2.0.1 - November 24th 2023 =
* New: image comparison for zoho item to avoid duplication
* Tweak: refactored frontend sync to zoho inventory
* Fix: License key activation

= 2.0.0 - November 22nd 2023 =
* New: Zoho Inventory Integration
* New: complete rebuild of plugin in Vue.js
* Tweak: improved CORS support
* Tweak: PHP 8.1 support
* Compatibility added for WooCommerce 8.3

= 1.0.4 - September 6th 2023 =
* New: timezone support for order timestamp
* Fix: date formatting on settings page

= 1.0.3 - April 18th 2023 =
* Fix: fatal error conflict with Yith plugins

= 1.0.2 - April 17th 2023 =
* Fix: conflicts with some plugins
* Tweak: saving settings will show confirmation
* Improvement: support for WordPress 6.2

= 1.0.1 - April 3rd 2023 =
* New: changelog widget
* Fix: mobile ui

= 1.0.0 - April 1st 2023 =
* initial release