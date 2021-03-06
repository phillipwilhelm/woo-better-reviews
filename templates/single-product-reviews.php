<?php
/**
 * Display single product reviews (comments)
 *
 * This file totally overrides the template in either Woo or a theme.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Call the global product.
global $product;

// Bail if we don't have comments (reviews) open.
if ( ! comments_open() ) {
	return;
}

?>

<div id="reviews" class="woocommerce-Reviews woo-better-reviews-display-wrapper">

	<?php \LiquidWeb\WooBetterReviews\ViewOutput\display_review_template_messages( $product->get_id() ); ?>

	<div id="comments" class="woo-better-reviews-display-block woo-better-reviews-existing-block">

		<?php \LiquidWeb\WooBetterReviews\ViewOutput\display_review_template_title( $product->get_id() ); ?>

		<?php \LiquidWeb\WooBetterReviews\ViewOutput\display_review_template_visual_aggregate( $product->get_id() ); ?>

		<?php \LiquidWeb\WooBetterReviews\ViewOutput\display_review_template_sorting( $product->get_id() ); ?>

		<?php \LiquidWeb\WooBetterReviews\ViewOutput\display_existing_reviews( $product->get_id() ); ?>

	</div>

	<?php \LiquidWeb\WooBetterReviews\ViewOutput\display_new_review_form( $product->get_id() ); ?>

	<div class="clear"></div>
</div>
