<?php
/**
 * Run the review reminders.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Reminders;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Helpers as Helpers;
use LiquidWeb\WooBetterReviews\Utilities as Utilities;
use LiquidWeb\WooBetterReviews\Database as Database;

// And pull in any other namespaces.
use WP_Error;

/**
 * Start our engines.
 */
add_action( 'wc_better_reviews_trigger_after_purchase_order_line_items', __NAMESPACE__ . '\maybe_set_reminder_at_order', 10, 3 );
add_action( 'wc_better_reviews_trigger_status_change_order_completed', __NAMESPACE__ . '\maybe_set_reminder_at_completed', 10, 2 );

/**
 * Check to see if we need to set reminders for purchased products.
 *
 * @param  integer $order_id     The order ID being run.
 * @param  array   $product_ids  The product ID of each item in the order.
 * @param  array   $order_data   The entire order data.
 *
 * @return void
 */
function maybe_set_reminder_at_order( $order_id, $product_ids, $order_data ) {

	// Bail if no product IDs or order status came through.
	if ( empty( $order_id ) || empty( $product_ids ) || empty( $order_data['status'] ) ) {
		return;
	}

	// Run the main check for being enabled.
	$maybe_enabled  = Helpers\maybe_reminders_enabled();

	// Check if we have an allowed status.
	$maybe_allowed  = Helpers\maybe_allowed_status( $order_data['status'] );

	// Bail if not allowed.
	if ( ! $maybe_enabled || ! $maybe_allowed ) {

		// Purge the meta.
		Utilities\purge_order_reminder_meta( $order_id );

		// And return nothing, since it isn't an error per se.
		return;
	}

	// Get my order date and convert it.
	$start_stamp    = ! empty( $order_data['date_created'] ) ? $order_data['date_created']->date( 'U' ) : 0;

	// Set some empty variable.
	$reminder_arr   = array();

	// Now loop the product IDs and check each one.
	foreach ( $product_ids as $product_id ) {

		// Get the meta key.
		$meta_check = get_post_meta( $product_id, Core\META_PREFIX . 'send_reminder', true );

		// If we have a specific "no", then skip.
		if ( ! empty( $meta_check ) && 'no' === sanitize_text_field( $meta_check ) ) {
			continue;
		}

		// Add my product ID and the date stamp.
		$reminder_arr[] = array(
			'product_id' => absint( $product_id ),
			'timestamp'  => Utilities\calculate_relative_date( $product_id, $start_stamp ),
		);
	}

	// If all were set to 'no', then bail.
	if ( empty( $reminder_arr ) ) {

		// Purge the meta.
		Utilities\purge_order_reminder_meta( $order_id );

		// And return an empty.
		return;
	}

	// Set the array of products to set reminders to.
	update_post_meta( $order_id, Core\META_PREFIX . 'review_reminder_status', 'pending' );
	update_post_meta( $order_id, Core\META_PREFIX . 'review_reminder_data', $reminder_arr );

	// Handle an action.
	do_action( Core\HOOK_PREFIX . 'after_order_created_reminder_set', $reminder_arr, $order_id, $product_ids, $order_data );
}

/**
 * Check to see if we need to set reminders for purchased products when the status changes.
 *
 * @param  integer $order_id    The order ID being run.
 * @param  array   $order_data  The entire order data.
 *
 * @return void
 */
function maybe_set_reminder_at_completed( $order_id, $order_data ) {

	// Bail if no data.
	if ( empty( $order_id ) || empty( $order_data ) ) {
		return;
	}

	// Bail if no line items.
	if ( empty( $order_data['line_items'] ) ) {
		return;
	}

	// Run the main check for being enabled.
	$maybe_enabled  = Helpers\maybe_reminders_enabled();

	// Bail if not allowed.
	if ( ! $maybe_enabled ) {

		// Purge the meta.
		Utilities\purge_order_reminder_meta( $order_id );

		// And bail.
		return;
	}

	// Check for a meta flag.
	$maybe_pending  = get_post_meta( $order_id, Core\META_PREFIX . 'review_reminder_status', true );

	// If we already set the pending flag, bail.
	if ( ! empty( $maybe_pending ) && 'pending' === sanitize_text_field( $maybe_pending ) ) {
		return;
	}

	// Get my order date and convert it.
	$start_stamp    = ! empty( $order_data['date_created'] ) ? $order_data['date_created']->date( 'U' ) : 0;

	// Get the array of product IDs in the order items.
	$product_ids    = array_keys( $order_data['line_items'] );

	// Set some empty variable.
	$reminder_arr   = array();

	// Now loop the product IDs and check each one.
	foreach ( $product_ids as $product_id ) {

		// Get the meta key.
		$meta_check = get_post_meta( $product_id, Core\META_PREFIX . 'send_reminder', true );

		// If we have a specific "no", then skip.
		if ( ! empty( $meta_check ) && 'no' === sanitize_text_field( $meta_check ) ) {
			continue;
		}

		// Add my product ID and the date stamp.
		$reminder_arr[] = array(
			'product_id' => absint( $product_id ),
			'timestamp'  => Utilities\calculate_relative_date( $product_id, $start_stamp ),
		);
	}

	// If all were set to 'no', then bail.
	if ( empty( $reminder_arr ) ) {

		// Purge the meta.
		Utilities\purge_order_reminder_meta( $order_id );

		// And bail.
		return;
	}

	// Set the array of products to set reminders to.
	update_post_meta( $order_id, Core\META_PREFIX . 'review_reminder_status', 'pending' );
	update_post_meta( $order_id, Core\META_PREFIX . 'review_reminder_data', $reminder_arr );

	// Handle an action.
	do_action( Core\HOOK_PREFIX . 'after_status_completed_reminder_set', $reminder_arr, $order_id, $product_ids, $order_data );
}

/**
 * Remove the product ID from the array of pending items.
 *
 * @param  integer $order_id      The order ID we just sent the email from.
 * @param  array   $product_list  The product IDs contained in the email.
 *
 * @return mixed
 */
function remove_completed_reminders( $order_id = 0, $product_list = array() ) {

	// Bail if no data.
	if ( empty( $order_id ) || empty( $product_list ) ) {
		return;
	}

	// Pull out the entire meta group.
	$reminder_meta  = get_post_meta( $order_id, Core\META_PREFIX . 'review_reminder_data', true );

	// If we don't have any (odd) then changed the other key and bail.
	if ( empty( $reminder_meta ) ) {

		// Purge ALL the meta.
		Utilities\purge_order_reminder_meta( $order_id );

		// And return.
		return;
	}

	// Pull out the product list, with our timestamps.
	$product_group  = wp_list_pluck( $reminder_meta, 'timestamp', 'product_id' );

	// Compare the lists. If none remain, then we are done here.
	$list_compare   = array_diff( $product_list, array_keys( $product_group ) );

	// None left! Clean plate club!
	if ( empty( $list_compare ) ) {

		// Change the status.
		update_post_meta( $order_id, Core\META_PREFIX . 'review_reminder_status', 'completed' );

		// Delete the dataset key all together.
		delete_post_meta( $order_id, Core\META_PREFIX . 'review_reminder_data' );

		// And return.
		return;
	}

	// Now set an empty for our remaining array.
	$update_meta    = array();

	// Loop the array of IDs and
	foreach ( $product_group as $product_id => $timestamp ) {

		// If we have a match, add to our array.
		if ( in_array( $product_id, $list_compare ) ) {

			// Add my product ID and the date stamp.
			$update_meta[]  = array(
				'product_id' => absint( $product_id ),
				'timestamp'  => absint( $timestamp ),
			);
		}

		// Nothing left inside this loop.
	}

	// None left! Clean plate club!
	if ( empty( $update_meta ) ) {

		// Change the status.
		update_post_meta( $order_id, Core\META_PREFIX . 'review_reminder_status', 'completed' );

		// Delete the dataset key all together.
		delete_post_meta( $order_id, Core\META_PREFIX . 'review_reminder_data' );

		// And return.
		return;
	}

	// Make sure it's still set to pending
	update_post_meta( $order_id, Core\META_PREFIX . 'review_reminder_status', 'pending' );

	// Update the meta with what we have left.
	update_post_meta( $order_id, Core\META_PREFIX . 'review_reminder_data', $update_meta );

	// And be done.
	return;
}
