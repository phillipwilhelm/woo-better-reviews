# Better Reviews For WooCommerce #
**Contributors:** [liquidweb](https://profiles.wordpress.org/liquidweb), [norcross](https://profiles.wordpress.org/norcross)  
**Tags:** woocommerce, reviews  
**Requires at least:** 5.0  
**Tested up to:** 5.1.1  
**Requires PHP:** 5.6.0  
**Stable tag:** 0.3.0-dev  
**License:** MIT  
**License URI:** https://opensource.org/licenses/MIT  

You own a WooCommerce store, so you know that reviews are a key component of a successful online store.

Being able to know from customers who have already purchased that your products are high quality and tested by people just like them is important to build trust in your brand.

## Description ##

Better Reviews for WordPress is a complete replacement for the existing reviews system used by WooCommerce.

Make your reviews more trustworthy and increase conversions with:

* A fixed 7 point rating for stars
* Allow you to define individual product attributes
* Reviewers can note attributes about themselves, and review viewers can sort through reviews by individual product attribute
* Includes review author characteristics tied to the review to add more context
* Calculate averages to display on product page for both for product rating and product attributes
* Verified reviews for WooCommerce - if the user purchased the product, the review gets flagged as verified
* Display WooCommerce review data for customers - a visual aggregate of the review data is shown above the list, including rating breakdowns, averages, etc.

Care for the back-end of your site by making your reviews more reliable:

* Better Reviews for WooCommerce replaces the existing WooCommerce comment system, both front and back end
* Stores data in custom tables instead of comments
* Set WooCommerce reviews to pending - all reviews are set `pending` on submission

[youtube https://www.youtube.com/watch?v=IdBtuIPrpkU]

## Installation ##

This section describes how to install the plugin and get it working.

1. Upload `woo-better-reviews` to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Confirm the settings under the WooCommerce "Product" tab.

## Frequently Asked Questions ##

### What is this for? ###

Better Reviews For WooCommerce is a full-feature replacement for the default WooCommerce review system.

### How do I set it up? ###

1. Open the WooCommerce settings and select the "Product" tab
1. Scroll down to the bottom and make sure reviews are enabled.
1. Create some product attributes and author characteristics to include in the review form.

### Can I change how a feature works / looks? ###

Likely, yes! The markup generated by the plugin has unique classes for applying custom CSS. In addition, there are numerous actions and filters that are available to make changes. Documentation and examples for the CSS styling and action / filter reference will be coming soon.

### Is Better Reviews for WooCommerce compatible with Gutenberg? ###

Yes.


## Screenshots ##

1. **Product Attributes** - Store owners can define product attributes for feedback
1. **Reviewer Characteristics** - Store owners can define reviewer characteristics
1. **Reviews and Filters** - Customers can filter reviews based on reviewer characteristics
1. **WooCommerce Settings** - Set the options for reviews

## Changelog ##

### 0.3.0 ###
* something at some point

### 0.2.0 ###
* adding CLI command for converting existing reviews.
* including review data in WooCommerce structured data.
* removed duplicate queries, general cleanup.

### 0.1.0 ###
* Initial release


## Upgrade Notice ##

### 0.1.0 ###
* Initial release
