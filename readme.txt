=== Tabs for WooCommerce ===
Contributors: wpbranch, ikamal
Tags: woocommerce, product tabs, custom tab, woo commerce tab
Requires at least: 4.7
Tested up to: 6.0
Stable tag: 1.0.0
license: GPLv2 or later

This plugin extends WooCommerce by allowing a custom product tab to be created with any content.

== Description ==

One of the great things about WooCommerce is that it allows you to add custom product tabs. This means that you can add additional information to your product pages, which can be really useful for your customers.
There are a few different ways to add custom product tabs, but one of the easiest is to use the WooTabs plugin. This plugin allows you to easily add custom tabs to your product pages, and you can even specify which products they should be displayed on.
If you're looking for a way to add more information to your product pages, then custom product tabs are a great option. And the WooTabs Custom Product Tabs plugin makes it easy to add them to your site.

> Requires WooCommerce 3.9.4 or newer

= Features =

 - Add a single custom tab to each product in your shop
 - Insert any desired content into custom tabs to provide product specifications, shipping info, or more
 - Custom tabs can accept shortcodes or HTML content &ndash; great for embedding a marketing video or inquiry form!

= Support Details =

We do support our free plugins and extensions, but please understand that support for premium products takes priority. We typically check the forums every few days (with a maximum delay of one week).

= WooCommerce Tab Manager =

To easily add multiple tabs, share tabs between products, and more features, please consider upgrading to the premium [WooTabs Pro](https://wpbranch.com/plugins/wootabs/).

== Installation ==

1. Upload the entire 'wootabs-lite' folder to the '/wp-content/plugins/' directory, **or** upload the zip file via Plugins &gt; Add New
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Edit a product, then click on 'Custom Tab' within the 'Product Data' panel

== Screenshots ==

1. Adding a custom tab to a product in the admin
2. The custom tab displayed on the frontend

== Frequently Asked Questions ==

= Can I add more than tab, or change the order of the tabs? =

This free version does not have that functionality, but you can with the premium [WooTabs Pro](https://wpbranch.com/plugins/wootabs/). This allows you to add multiple tabs, share tabs, edit core tabs, or re-order tab display.

= How do I hide the tab heading? =

The tab heading is shown before the tab content and is the same string as the tab title.  An easy way to hide this is to add the following to the bottom of your theme's functions.php or wherever you keep custom code:

`
add_filter( 'wootabs_lite_heading', '__return_empty_string' );
`

= My tab content isn't showing properly, how do I fix it? =

Be sure that (1) your HTML is valid -- try putting your tab content into a blog post draft and see how it renders. (2) Be sure any shortcodes will expand in your blog post draft as well; if they don't work properly there, they won't work in your custom tab.

= Can I set the same tab title for all products? =

Yep, there's the `wootabs_lite_title` that passes in the tab title for you to change. This filter also passes in the `$product` and class instance if you'd like to change this conditionally.

Here's how you can set one title for all custom tabs, regardless of what title is entered on the product page (can go in the bottom of functions.php or where you keep custom code):

`
function sv_change_custom_tab_title( $title ) {
 $title = 'Global tab title';
 return $title;
}
add_filter( 'wootabs_lite_title', 'sv_change_custom_tab_title' );
`

== Changelog ==


= 1.0.0 =
 * Initial release
