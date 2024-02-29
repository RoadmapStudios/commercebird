=== CommerceBird ===
Contributors: fawadinho
Author link: https://commercebird.com
Tags: shop,store,ecommerce,commerce,e-commerce
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 2.1.5
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

== Description ==
This plugin ensures you can take the most of the CommerceBird App such as uploading product images, using integrations like Zoho Inventory, Zoho CRM & Exact Online.

== Frequently Asked Questions ==

= Q: Do I need a paid plan to use this plugin? =
A: No, you just need to create an account on https://app.commercebird.com to use this plugin.

= Q: Which product types does it support? =
A: Simple, Variable, Bundles, Simple Subscription, Variable Subscription.

= Q: Can I create Purchase Orders? =
A: Yes! you can also create purchase costs on the product edit page in the app.

== Q: Which Integrations does it support? =
A: Zoho CRM, Zoho Inventory and Exact Online. More will be added.

== Q: Where can I see the documentation and contact support?
A: Please visit https://support.commercebird.com to read the docs and open support ticket

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