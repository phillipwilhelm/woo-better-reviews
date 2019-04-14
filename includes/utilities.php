<?php
/**
 * Our utility functions to use across the plugin.
 *
 * @package WooBetterReviews
 */

// Declare our namespace.
namespace LiquidWeb\WooBetterReviews\Utilities;

// Set our aliases.
use LiquidWeb\WooBetterReviews as Core;
use LiquidWeb\WooBetterReviews\Queries as Queries;

/**
 * Check the constants we know about during an Ajax call.
 *
 * @return boolean
 */
function check_constants_for_process( $include_ajax = true ) {

	// Bail out if running an autosave.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return false;
	}

	// Bail out if running a cron, unless we've skipped that.
	if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
		return false;
	}

	// Bail if we are doing a REST API request.
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return false;
	}

	// Include the possible Ajax check.
	if ( ! empty( $include_ajax ) && wp_doing_ajax() ) {
		return false;
	}

	// Hit none of the checks. Proceed.
	return true;
}

/**
 * Update the aggregate scoring.
 *
 * @param  array $product_ids  The product IDs we are handling.
 *
 * @return void
 */
function update_product_review_count( $product_ids ) {

	// Bail without a product IDs.
	if ( empty( $product_ids ) ) {
		return;
	}

	// Loop my IDs and update each one.
	foreach ( (array) $product_ids as $product_id ) {

		// Fetch my approved counts.
		$maybe_approved = Queries\get_approved_reviews_for_product( $product_id, 'counts' );
		$approved_count = ! empty( $maybe_approved ) ? absint( $maybe_approved ) : 0;

		// Update the Woo postmeta key.
		update_post_meta( $product_id, '_wc_review_count', $approved_count );

		// Update our own post meta key as well.
		update_post_meta( $product_id, Core\META_PREFIX . 'review_count', $approved_count );
	}
}

/**
 * Update the aggregate scoring.
 *
 * @param  integer $product_id  The product ID we are adding.
 *
 * @return void
 */
function calculate_total_review_scoring( $product_id = 0 ) {

	// Bail without a product ID.
	if ( empty( $product_id ) ) {
		return;
	}

	// Get all the totals from approved reviews.
	$approved_totals    = Queries\get_approved_reviews_for_product( $product_id, 'total' );

	// Bail without having any reviews to calculate.
	// @@todo zero out any scoring?
	if ( empty( $approved_totals ) ) {
		return;
	}

	// And calculate the average.
	$review_avg_score   = array_sum( $approved_totals ) / count( $approved_totals );
	$review_avg_round   = round( $review_avg_score, 0 );

	// Make sure the average is not zero.
	$review_avg_confirm = absint( $review_avg_round ) < 1 ? 1 : absint( $review_avg_round );

	// Update the Woo postmeta key.
	update_post_meta( $product_id, '_wc_average_rating', absint( $review_avg_confirm ) );

	// Update our own post meta key as well.
	update_post_meta( $product_id, Core\META_PREFIX . 'average_rating', absint( $review_avg_confirm ) );
}

/**
 * Take the set of attribute scoring data from a product and get totals.
 *
 * @param  array  $attribute_set  The array of attribute data we have.
 *
 * @return array
 */
function calculate_average_attribute_scoring( $attribute_set = array() ) {

	// Bail without an attribute set.
	if ( empty( $attribute_set ) || ! is_array( $attribute_set ) ) {
		return;
	}

	// Parse out some labels.
	$label_set  = wp_list_pluck( $attribute_set[0], 'label', 'id' );

	// Set up two empty returns.
	$setup  = array();
	$build  = array();

	// Loop my scoring.
	foreach ( $attribute_set as $attribute_scoring ) {

		// Grab the score set.
		$score_set  = wp_list_pluck( $attribute_scoring, 'value', 'id' );

		// Loop again.
		foreach ( $score_set as $attribute_id => $score_value ) {

			// Determine if we have one or not.
			$setup_arg_var  = array_key_exists( $attribute_id, $setup ) ? $setup[ $attribute_id ] . ',' . $score_value : $score_value;

			// Set my new argument variable.
			$setup[ $attribute_id ] = $setup_arg_var;
		}

		// Nothing left with the sets.
	}

	// Now loop my setup and do the maths.
	foreach ( $setup as $attribute_id => $score_string ) {

		// Set my scoring array up.
		$scoring_array  = explode( ',', $score_string );

		// And calculate the average.
		$attribute_avg  = array_sum( $scoring_array ) / count( $scoring_array );

		// Get my labels if possible.
		$attribute_lbls = Queries\get_single_attribute( $attribute_id, 'labels' );

		// Add my two values.
		$build[ $attribute_id ] = array(
			'id'      => absint( $attribute_id ),
			'average' => round( $attribute_avg, 0 ),
			'total'   => count( $scoring_array ),
			'title'   => $label_set[ $attribute_id ],
			'slug'    => sanitize_title_with_dashes( $label_set[ $attribute_id ], '', 'save' ),
			'labels'  => $attribute_lbls,
		);
	}

	// Return my resulting build.
	return array_values( $build );
}

/**
 * Take the potentially values and format a nice list.
 *
 * @param  mixed  $values   The values, perhaps serialized.
 * @param  string $display  How to display the values.
 *
 * @return HTML
 */
function format_array_values_display( $values, $display = 'breaks' ) {

	// Bail without values to work with.
	if ( empty( $values ) ) {
		return false;
	}

	// Set up the array to begin.
	$setup_format   = maybe_unserialize( $values );

	// Bail without formatted to work with.
	if ( empty( $setup_format ) || ! is_array( $setup_format ) ) {
		return false;
	}

	// Sanitize each one.
	$setup_values   = array_map( 'esc_attr', $setup_format );

	// Handle my different error codes.
	switch ( esc_attr( $display ) ) {

		case 'breaks' :

			// Return them, imploded with a line break.
			return implode( '<br>', $setup_values );
			break;

		case 'list' :

			// Return them, imploded in a nice list.
			return '<ul class="woo-better-reviews-admin-table-list"><li>' . implode( '</li><li>', $setup_values ) . '</li></ul>';
			break;

		case 'inline' :

			// Return them, imploded with a comma.
			return implode( ', ', $setup_values );
			break;

		// End all case breaks.
	}

	// Nothing remaining on the formatting.
}

/**
 * Take the array of labels and make save-able keys.
 *
 * @param  mixed   $labels     The value labels.
 * @param  boolean $serialize  Whether we return it serialized.
 *
 * @return mixed
 */
function format_string_values_array( $labels, $serialize = true ) {

	// Make sure we have labels.
	if ( empty( $labels ) ) {
		return false;
	}

	// Make sure it's an array.
	$label_args = ! is_array( $labels ) ? explode( ',', $labels ) : $labels;

	// Set an empty.
	$dataset    = array();

	// Now loop the labels and do some cleanup.
	foreach ( $label_args as $label ) {

		// Set the key.
		$ky = sanitize_title_with_dashes( trim( $label ), '', 'save' );

		// And make some data.
		$dataset[ $ky ] = sanitize_text_field( $label );
	}

	// Return it one way or the other.
	return ! $serialize ? $dataset : maybe_serialize( $dataset );
}

/**
 * Pull out the data inside an attribute and make it nice.
 *
 * @param  array $attribute_array  The attributes from the query.
 *
 * @return array
 */
function format_attribute_display_data( $attribute_array ) {

	// Make sure we have args.
	if ( empty( $attribute_array ) ) {
		return;
	}

	// Set the empty.
	$setup  = array();

	// Loop and check.
	foreach ( $attribute_array as $index => $attribute_args ) {

		// Now we loop each attribute.
		foreach ( $attribute_args as $attribute_key => $attribute_value ) {

			// First check for labels.
			if ( in_array( $attribute_key, array( 'min_label', 'max_label' ) ) ) {

				// A placeholder for now until I figure out how to merge them.
				$array_key  = $attribute_key;

			} else {

				// Set my new array key.
				$array_key  = str_replace( 'attribute_', '', $attribute_key );
			}

			// Now set our array.
			$setup[ $index ][ $array_key ] = $attribute_value;
		}

		// Nothing else (I think?) inside this array.
	}

	// Return the array.
	return $setup;
}

/**
 * Pull out the data inside an charstcs and make it nice.
 *
 * @param  array $charstcs_array  The attributes from the query.
 *
 * @return array
 */
function format_charstcs_display_data( $charstcs_array ) {

	// Make sure we have args.
	if ( empty( $charstcs_array ) ) {
		return;
	}

	// Set the empty.
	$setup  = array();

	// Loop and check.
	foreach ( $charstcs_array as $index => $charstcs_args ) {

		// Now we loop each attribute.
		foreach ( $charstcs_args as $charstcs_key => $charstcs_value ) {

			// Set my new array key.
			$array_key  = str_replace( 'charstcs_', '', $charstcs_key );

			// Now set our array.
			$setup[ $index ][ $array_key ] = maybe_unserialize( $charstcs_value );
		}

		// Nothing else (I think?) inside this array.
	}

	// Return the array.
	return $setup;
}

/**
 * Get the various options for a textarea.
 *
 * @param  array $field_args  The arguments for single attributes.
 *
 * @return array
 */
function format_review_textarea_data( $field_args = array() ) {

	// Make sure we have args.
	if ( empty( $field_args ) ) {
		return;
	}

	// Set our initial array.
	$field_options  = array( 'spellcheck="true"' );

	// Check for required.
	if ( ! empty( $field_args['required'] ) ) {
		$field_options[] = 'required="required"';
	}

	// Check for minimum length.
	if ( ! empty( $field_args['min-count'] ) ) {
		$field_options[] = 'minlength="' . absint( $field_args['min-count'] ) . '"';
	}

	// Check for maximum length.
	if ( ! empty( $field_args['max-count'] ) ) {
		$field_options[] = 'maxlength="' . absint( $field_args['max-count'] ) . '"';
	}

	// And return it.
	return $field_options;
}

/**
 * Pull out the content data and make it nice.
 *
 * @param  array $review  The review from the query.
 *
 * @return array
 */
function format_review_content_data( $review ) {

	// Bail without a review.
	if ( empty( $review ) ) {
		return;
	}

	// Set the empty.
	$setup  = array();

	// Set the array of what to check.
	$checks = array(
		'review_date',
		'review_slug',
		'review_title',
		'review_content',
		'review_status',
	);

	// Loop and check.
	foreach ( $checks as $check ) {

		// Skip if not there.
		if ( ! isset( $review[ $check ] ) ) {
			continue;
		}

		// Make my array key.
		$array_key  = 'review_content' !== $check ? str_replace( 'review_', '', $check ) : 'review';

		// Add the item.
		$setup[ $array_key ] = $review[ $check ];

		// And unset the review parts.
		unset( $review[ $check ] );
	}

	// Return the array.
	return wp_parse_args( $setup, $review );
}

/**
 * Pull out the scoring data and make it nice.
 *
 * @param  array   $review   The review from the query.
 * @param  boolean $discard  Option to discard the rest of the data.
 *
 * @return array
 */
function format_review_scoring_data( $review, $discard = false ) {

	// Bail without a review.
	if ( empty( $review ) ) {
		return;
	}

	// Set the empty for scoring.
	$setup  = array();

	// Check and modify the overall total.
	if ( isset( $review['rating_total_score'] ) ) {

		// Add the item.
		$setup['total_score'] = $review['rating_total_score'];

		// And unset the old.
		unset( $review['rating_total_score'] );
	}

	// Check for the attributes kept.
	if ( isset( $review['rating_attributes'] ) ) {

		// Pull out the attributes.
		$attributes = maybe_unserialize( $review['rating_attributes'] );

		// Our scoring data has 3 pieces.
		foreach ( $attributes as $attribute_id => $attribute_score ) {

			// Pull my attribute data.
			$attribute_data = Queries\get_single_attribute( $attribute_id );

			// Now set the array accordingly.
			$setup['rating_attributes'][] = array(
				'id'    => $attribute_id,
				'label' => $attribute_data['attribute_name'],
				'value' => $attribute_score,
			);

			// Nothing left with each attribute.
		}

		// And unset the old.
		unset( $review['rating_attributes'] );
	}

	// Return the array.
	return false !== $discard ? $setup : wp_parse_args( $setup, $review );
}

/**
 * Pull out the author data and make it nice.
 *
 * @param  array $review  The review from the query.
 *
 * @return array
 */
function format_review_author_charstcs( $review ) {

	// Bail without a review.
	if ( empty( $review ) || empty( $review['author_charstcs'] ) ) {
		return;
	}

	// Set the empty.
	$setup  = array();

	// Pull out the charstcs.
	$charstcs   = maybe_unserialize( $review['author_charstcs'] );

	// Our scoring data has 3 pieces.
	foreach ( $charstcs as $charstcs_id => $charstcs_slug ) {

		// Skip a missing slug.
		if ( empty( $charstcs_slug ) ) {
			continue;
		}

		// Pull my charstcs data.
		$charstcs_data  = Queries\get_single_charstcs( $charstcs_id );
		$charstcs_vals  = maybe_unserialize( $charstcs_data['charstcs_values'] );

		// Now set the array accordingly.
		$setup['author_charstcs'][] = array(
			'id'    => $charstcs_id,
			'label' => $charstcs_data['charstcs_name'],
			'value' => $charstcs_vals[ $charstcs_slug ],
		);

		// Nothing left with each attribute.
	}

	// And unset the old.
	unset( $review['author_charstcs'] );

	// Return the array.
	return wp_parse_args( $setup, $review );
}

/**
 * Get and format the aggregate schema data.
 *
 * @param  integer $product_id   The product ID we are looking for.
 * @param  boolean $include_tag  Whether or not to include the tag wrapper.
 *
 * @return JSON
 */
function format_aggregate_review_schema( $product_id = 0, $include_tag = true ) {

	// Bail without a product ID.
	if ( empty( $product_id ) ) {
		return false;
	}

	// Query the schema data.
	$schema_query   = Queries\get_schema_data_for_product( $product_id );

	// Bail without data to structure.
	if ( empty( $schema_query ) ) {
		return false;
	}

	// Filter the arguments before encoding.
	$filtered_args  = apply_filters( Core\HOOK_PREFIX . 'aggregate_review_schema_data', $schema_query, $product_id );

	// Encode my data.
	$schema_encoded = json_encode( $filtered_args, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

	// Return it tagged or not.
	return false !== $include_tag ? '<script type="application/ld+json">' . "\n" . $schema_encoded . "\n" . '</script>' . "\n" : $schema_encoded;
}

/**
 * Get and format the single review schema data.
 *
 * @param  array   $review_array  The review array data.
 * @param  boolean $include_tag   Whether or not to include the tag wrapper.
 *
 * @return JSON
 */
function format_single_review_schema( $review_array = array(), $include_tag = true ) {

	// Bail without a review data.
	if ( empty( $review_array ) ) {
		return false;
	}

	// Get my product name.
	$product_name   = get_the_title( absint( $review_array['product_id'] ) );

	// Set up the schema arguments.
	$schema_args    = array(
		'@context'        => 'http://schema.org/',
		'@type'           => 'Review',
		'itemReviewed'    => array(
			'@type' => 'Thing',
			'name'  => esc_attr( $product_name ),
		),
		'author'    => array(
			'@type' => 'Person',
			'name'  => esc_attr( $review_array['author_name'] ),
		),
		'datePublished' => date( 'Y-m-d ', strtotime( $review_array['date'] ) ),
		'reviewRating'  => array(
			'@type'       => 'Rating',
			'description' => '',
			'ratingValue' => absint( $review_array['total_score'] ),
			'bestRating'  => '7',
			'worstRating' => '1',
		),
	);

	/*
	<script type="application/ld+json">
	{
		"@context": "http://schema.org/",
		"@type": "Review",
		"itemReviewed": {
			"@type": "Thing",
			"name": "The Product"
		},
		"author": {
			"@type": "Person",
			"name": "The Author"
		},
		"datePublished": "YYYY-MM-DD",
		"reviewRating": {
			"@type": "Rating",
			"description": "This was the review description.",
			"ratingValue": "7"
			'bestRating'  => '7',
			'worstRating' => '1',
		}
	}
	</script>
	 */

	// Filter the arguments before encoding.
	$filtered_args  = apply_filters( Core\HOOK_PREFIX . 'single_review_schema_data', $schema_args, $review_array );

	// Encode my data.
	$schema_encoded = json_encode( $filtered_args, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

	// Return it tagged or not.
	return false !== $include_tag ? '<script type="application/ld+json">' . "\n" . $schema_encoded . "\n" . '</script>' . "\n" : $schema_encoded;
}

/**
 * Set up the class to create a single bar chart.
 *
 * @param integer $rating_count  The count of how many ratings this has.
 * @param integer $review_count  The total count of reviews.
 */
function set_single_bar_graph_class( $rating_count = 0, $review_count = 0 ) {

	// Set the base class for the bar chart.
	$bar_class  = 'woo-better-reviews-list-breakdown-bar';

	// Bail without a review count since we can't do the maths.
	if ( empty( $review_count ) ) {
		return $bar_class;
	}

	// If we have no rating, add the zero class and go about it.
	if ( empty( $rating_count ) ) {
		$bar_class .= ' woo-better-reviews-list-breakdown-bar-zero';
	}

	// If we have a rating, do some maths.
	if ( ! empty( $rating_count ) ) {

		// Calculate the average.
		$rating_avg = absint( $rating_count ) / absint( $review_count );

		// Get the formatted number string.
		$rating_str = number_format( $rating_avg * 100, 0 );

		// And add it.
		$bar_class .= ' woo-better-reviews-list-breakdown-bar-fill woo-better-reviews-list-breakdown-bar-fill-' . esc_attr( $rating_str );
	}

	// Return the entire string.
	return $bar_class;
}

/**
 * Set a div class for each of our displayed reviews.
 *
 * @param  array   $review  The data tied to the review.
 * @param  integer $index   What index order (count) we are in the list.
 *
 * @return string
 */
function set_single_review_div_class( $review = array(), $index = 0 ) {

	// Bail without a review.
	if ( empty( $review ) ) {
		return;
	}

	// Set our base class, which is also the prefix for all the others.
	$class_prefix   = 'woo-better-reviews-single-review';

	// Return the default if no review object exists.
	if ( empty( $review ) ) {
		return $class_prefix;
	}

	// Start by setting our default class and classes based on static items in the object.
	$classes    = array(
		$class_prefix,
		$class_prefix . '-display-block',
		$class_prefix . '-author-' . absint( $review['author_id'] ),
		$class_prefix . '-product-' . absint( $review['product_id'] ),
		$class_prefix . '-rating-' . absint( $review['total_score'] ),
		$class_prefix . '-status-' . esc_attr( $review['status'] ),
	);

	// Check for verified.
	if ( ! empty( $review['verified'] ) ) {
		$classes[]  = 'woo-better-reviews-single-review-verified';
	}

	// Check the index for even / odd.
	$classes[]  = absint( $index ) & 1 ? $class_prefix . '-odd' : $class_prefix . '-even';

	// Now pass them through a filter before we implode.
	$array_args = apply_filters( Core\HOOK_PREFIX . 'single_review_div_classes', $classes, $review, $index );

	// If they are an idiot and blanked it out, return the original.
	if ( empty( $array_args ) ) {
		return $class_prefix;
	}

	// Now sanitize each piece.
	$array_args = array_map( 'sanitize_html_class', $array_args );

	// Return, imploded.
	return implode( ' ', $array_args );
}

/**
 * Set a buffered editor output.
 *
 * @param string  $editor_id       The ID of the editor form.
 * @param string  $editor_name     The field name for the editor.
 * @param string  $editor_class    Optional class to include.
 * @param mixed   $editor_content  Optional class to include.
 * @param array   $custom_args     Any other custom args.
 *
 * @return HTML
 */
function set_review_form_editor( $editor_id = '', $editor_name = '', $editor_class = '', $editor_content = '', $custom_args = array() ) {

	// Bail if we're missing anything.
	if ( empty( $editor_id ) || empty( $editor_name ) ) {
		return;
	}

	// Set the editor args.
	$setup_args = array(
		'wpautop'          => false,
		'media_buttons'    => false,
		'tinymce'          => false,
		'teeny'            => true,
		'textarea_rows'    => 10,
		'textarea_name'    => $editor_name,
		'editor_class'     => $editor_class,
		'drag_drop_upload' => false,
		'quicktags'        => array(
			'buttons' => 'strong,em,ul,ol,li'
		),
	);

	// Pass our custom args if we have them.
	$setup_args = ! empty( $custom_args ) ? wp_parse_args( $custom_args, $setup_args ) : $setup_args;

	// We have to buffer the editor output.
	ob_start();

	// Now handle the editor getting buffered.
	wp_editor( $editor_content, $editor_id, $setup_args );

	// Now just return it.
	return ob_get_clean();
}

/**
 * Review the related items tied to a review.
 *
 * @param  integer $review_id  The review ID we are checking.
 *
 * @return void
 */
function delete_related_review_data( $review_id = 0 ) {

	// Bail if we don't have a review ID.
	if ( empty( $review_id ) ) {
		return;
	}

	// Call the global DB.
	global $wpdb;

	// Run my delete functions.
	$wpdb->delete( $wpdb->wc_better_rvs_ratings, array( 'review_id' => absint( $review_id ) ), array( '%d' ) );
	$wpdb->delete( $wpdb->wc_better_rvs_authormeta, array( 'review_id' => absint( $review_id ) ), array( '%d' ) );
}

/**
 * Purge one or many transients based on what's happening.
 *
 * @param  string $key     A single transient key.
 * @param  string $group   A group of transients.
 * @param  array  $custom  Any custom args tied to a group.
 *
 * @return void
 */
function purge_transients( $key = '', $group = '', $custom = array() ) {

	// Allow others to pop in before.
	do_action( Core\HOOK_PREFIX . 'before_transient_purge', $key, $group, $custom );

	// If we have a single key, handle it.
	if ( ! empty( $key ) ) {
		delete_transient( $key );
	}

	// Handle groups.
	if ( ! empty( $group ) ) {

		// Now switch between my return types.
		switch ( sanitize_text_field( $group ) ) {

			// Handle the non-unique review items.
			case 'reviews' :

				// Start deleting.
				delete_transient( Core\HOOK_PREFIX . 'all_reviews' );
				delete_transient( Core\HOOK_PREFIX . 'admin_reviews' );
				delete_transient( Core\HOOK_PREFIX . 'verifed_reviews' );
				delete_transient( Core\HOOK_PREFIX . 'legacy_review_counts' );

			// Handle attributes and characteristics.
			case 'taxonomies' :

				// Start deleting.
				delete_transient( Core\HOOK_PREFIX . 'all_attributes' );
				delete_transient( Core\HOOK_PREFIX . 'all_charstcs' );

				// And done.
				break;

			// Handle products.
			case 'products' :

				// Loop what we have.
				if ( ! empty( $custom['ids'] ) ) {

					// Make sure we have good data.
					$all_id = array_map( 'absint', $custom['ids'] );

					// Loop and delete.
					foreach ( $all_id as $id ) {
						delete_transient( Core\HOOK_PREFIX . 'reviews_for_product_' . $id );
						delete_transient( Core\HOOK_PREFIX . 'approved_reviews_for_product_' . $id );
						delete_transient( Core\HOOK_PREFIX . 'review_count_product' . $id );
						delete_transient( Core\HOOK_PREFIX . 'attributes_product' . $id );
					}
				}

				// And done.
				break;

			// Handle authors.
			case 'authors' :

				// Loop what we have.
				if ( ! empty( $custom['ids'] ) ) {

					// Make sure we have good data.
					$all_id = array_map( 'absint', $custom['ids'] );

					// Loop and delete.
					foreach ( $all_id as $id ) {
						delete_transient( Core\HOOK_PREFIX . 'reviews_for_author_' . $id );
						delete_transient( Core\HOOK_PREFIX . 'charstcs_author' . $id );
					}
				}

				// And done.
				break;

			// No more case breaks, no more return types.
		}

		// Do an action after the group break.
		do_action( Core\HOOK_PREFIX . 'transient_purge_group', $group, $custom );
	}

	// Allow others to pop in after.
	do_action( Core\HOOK_PREFIX . 'after_transient_purge', $key, $group, $custom );
}

/**
 * Inserts a new key/value before the key in the array.
 *
 * @param  string $key        The key to insert before.
 * @param  array  $array      An array to insert in to.
 * @param  mixed  $new_key    The key to insert.
 * @param  mixed  $new_value  An value to insert.
 *
 * @return array
 */
function array_insert_before( $key, $array, $new_key, $new_value ) {

	// Check the requirements.
	if ( empty( $key ) || empty( $array ) || ! array_key_exists( $key, $array ) ) {
		return false;
	}

	// Set up the new array.
	$updated_array  = array();

	// Now loop the array we have.
	foreach ( (array) $array as $k => $value ) {

		// If our key matches, add it inside.
		if ( $k === $key ) {
			$updated_array[ $new_key ] = $new_value;
		}

		// Continue adding the array.
		$updated_array[ $k ] = $value;
	}

	// Return the resulting array.
	return $updated_array;
}

/**
 * Inserts a new key/value after the key in the array.
 *
 * @param  string $key        The key to insert before.
 * @param  array  $array      An array to insert in to.
 * @param  mixed  $new_key    The key to insert.
 * @param  mixed  $new_value  An value to insert.
 *
 * @return array
 */
function array_insert_after( $key, $array, $new_key, $new_value ) {

	// Check the requirements.
	if ( empty( $key ) || empty( $array ) || ! array_key_exists( $key, $array ) ) {
		return false;
	}

	// Set up the new array.
	$updated_array  = array();

	// Now loop the array we have.
	foreach ( (array) $array as $k => $value ) {

		// Continue adding the array.
		$updated_array[ $k ] = $value;

		// If our key matches, add it inside.
		if ( $k === $key ) {
			$updated_array[ $new_key ] = $new_value;
		}
	}

	// Return the resulting array.
	return $updated_array;
}
