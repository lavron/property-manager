<?php 

function pm_price( $price, $args = array() ) {
	$args = apply_filters(
		'pm_price_args',
		wp_parse_args(
			$args,
			array(
				'ex_tax_label'       => false,
				'currency'           => '',
				'decimal_separator'  => pm_get_price_decimal_separator(),
				'thousand_separator' => pm_get_price_thousand_separator(),
				'decimals'           => pm_get_price_decimals(),
				'price_format'       => get_pm_price_format(),
			)
		)
	);

	$unformatted_price = $price;
	$negative          = $price < 0;
	$price             = apply_filters( 'raw_pm_price', floatval( $negative ? $price * -1 : $price ) );
	$price             = apply_filters( 'formatted_pm_price', number_format( $price, $args['decimals'], $args['decimal_separator'], $args['thousand_separator'] ), $price, $args['decimals'], $args['decimal_separator'], $args['thousand_separator'] );

	if ( apply_filters( 'pm_price_trim_zeros', false ) && $args['decimals'] > 0 ) {
		$price = pm_trim_zeros( $price );
	}

	$formatted_price = ( $negative ? '-' : '' ) . sprintf( $args['price_format'], '<span class="pm-Price-currencySymbol">' . get_pm_currency_symbol( $args['currency'] ) . '</span>', $price );
	$return          = '<span class="pm-Price-amount amount"><bdi>' . $formatted_price . '</bdi></span>';

	if ( $args['ex_tax_label'] && pm_tax_enabled() ) {
		$return .= ' <small class="pm-Price-taxLabel tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
	}

	/**
	 * Filters the string of price markup.
	 *
	 * @param string $return            Price HTML markup.
	 * @param string $price             Formatted price.
	 * @param array  $args              Pass on the args.
	 * @param float  $unformatted_price Price as float to allow plugins custom formatting. Since 3.2.0.
	 */
	return apply_filters( 'pm_price', $return, $price, $args, $unformatted_price );
}

if ( ! function_exists( 'is_catalogue' ) ) {

	function is_catalogue() {
		return ( is_post_type_archive( 'property' ) || is_page( pm_get_page_id( 'catalogue' ) ) );
	}
}
if ( ! function_exists( 'is_property_category' ) ) {

	function is_property_category( $term = '' ) {
		return is_tax( 'property_cat', $term );
	}
}

if ( ! function_exists( 'is_property_taxonomy' ) ) {

	function is_property_taxonomy() {
		return is_tax( get_object_taxonomies( 'property' ) );
	}
}

if ( ! function_exists( 'is_property' ) ) {

	function is_property() {
		return is_singular( array( 'property' ) );
	}
}

function pm_get_page_id( $page ) {

	if ( 'catalogue' === $page ) {
		$page = 45;
	}


	return $page ? absint( $page ) : -1;
}

function get_pm_currency() {
	return;
}

function is_pm() {
	return apply_filters( is_catalogue() || is_property_taxonomy() || is_property() );
}

function pm_get_permalink_structure() {

	$permalinks       = array(
		'property_base'           => _x( 'property', 'slug', 'pm' ),
		'category_base'          => _x( 'property-category', 'slug', 'pm' ),
		'deal_type_base'          => _x( 'property-deal_type', 'slug', 'pm' ),
		'type_base'               => _x( 'property-type', 'slug', 'pm' ),
		'offer_base'               => _x( 'property-offer', 'slug', 'pm' ),
		'attribute_base'         => '',
		'use_verbose_page_rules' => false,
	);

	$permalinks['property_rewrite_slug']   = untrailingslashit( $permalinks['property_base'] );
	$permalinks['category_rewrite_slug']  = untrailingslashit( $permalinks['category_base'] );
	$permalinks['deal_type_rewrite_slug']  = untrailingslashit( $permalinks['type_base'] );

	return $permalinks;
}