<?php
/**
 * Structured data's handler and generator using JSON-LD format.
 *
 * @package WooCommerce\Classes
 * @since   3.0.0
 * @version 3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Structured data class.
 */
class PM_Structured_Data {

	/**
	 * Stores the structured data.
	 *
	 * @var array $_data Array of structured data.
	 */
	private $_data = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Generate structured data.
		add_action( 'pm_before_main_content', array( $this, 'generate_website_data' ), 30 );
		add_action( 'pm_breadcrumb', array( $this, 'generate_breadcrumblist_data' ), 10 );
		add_action( 'pm_single_property_summary', array( $this, 'generate_property_data' ), 60 );

		// Output structured data.
		add_action( 'wp_footer', array( $this, 'output_structured_data' ), 10 );
	}

	/**
	 * Sets data.
	 *
	 * @param  array $data  Structured data.
	 * @param  bool  $reset Unset data (default: false).
	 * @return bool
	 */
	public function set_data( $data, $reset = false ) {
		if ( ! isset( $data['@type'] ) || ! preg_match( '|^[a-zA-Z]{1,20}$|', $data['@type'] ) ) {
			return false;
		}

		if ( $reset && isset( $this->_data ) ) {
			unset( $this->_data );
		}

		$this->_data[] = $data;

		return true;
	}

	/**
	 * Gets data.
	 *
	 * @return array
	 */
	public function get_data() {
		return $this->_data;
	}

	/**
	 * Structures and returns data.
	 *
	 * List of types available by default for specific request:
	 *
	 * 'property',
	 * 'review',
	 * 'breadcrumblist',
	 * 'website',
	 * 'order',
	 *
	 * @param  array $types Structured data types.
	 * @return array
	 */
	public function get_structured_data( $types ) {
		$data = array();

		// Put together the values of same type of structured data.
		foreach ( $this->get_data() as $value ) {
			$data[ strtolower( $value['@type'] ) ][] = $value;
		}

		// Wrap the multiple values of each type inside a graph... Then add context to each type.
		foreach ( $data as $type => $value ) {
			$data[ $type ] = count( $value ) > 1 ? array( '@graph' => $value ) : $value[0];
			$data[ $type ] = apply_filters( 'pm_structured_data_context', array( '@context' => 'https://schema.org/' ), $data, $type, $value ) + $data[ $type ];
		}

		// If requested types, pick them up... Finally change the associative array to an indexed one.
		$data = $types ? array_values( array_intersect_key( $data, array_flip( $types ) ) ) : array_values( $data );

		if ( ! empty( $data ) ) {
			if ( 1 < count( $data ) ) {
				$data = apply_filters( 'pm_structured_data_context', array( '@context' => 'https://schema.org/' ), $data, '', '' ) + array( '@graph' => $data );
			} else {
				$data = $data[0];
			}
		}

		return $data;
	}

	/**
	 * Get data types for pages.
	 *
	 * @return array
	 */
	protected function get_data_type_for_page() {
		$types   = array();
		$types[] = is_catalogue() || is_property_category() || is_property() ? 'property' : '';
		// $types[] = is_shop() && is_front_page() ? 'website' : '';
		// $types[] = is_property() ? 'review' : '';
		$types[] = 'breadcrumblist';
		// $types[] = 'order';

		return array_filter( $types );
	}


	/**
	 * Sanitizes, encodes and outputs structured data.
	 *
	 * Hooked into `wp_footer` action hook.
	 * Hooked into `pm_email_order_details` action hook.
	 */
	public function output_structured_data() {
		$types = $this->get_data_type_for_page();
		$data  = $this->get_structured_data( $types );

		if ( $data ) {
			echo '<script type="application/ld+json">' . wc_esc_json( wp_json_encode( $data ), true ) . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Generators
	|--------------------------------------------------------------------------
	|
	| Methods for generating specific structured data types:
	|
	| - Product
	| - Review
	| - BreadcrumbList
	| - WebSite
	| - Order
	|
	| The generated data is stored into `$this->_data`.
	| See the methods above for handling `$this->_data`.
	|
	*/

	/**
	 * Generates Product structured data.
	 *
	 * Hooked into `pm_single_property_summary` action hook.
	 *
	 * @param PM_property $property Product data (default: null).
	 */
	public function generate_property_data( $property = null ) {
		if ( ! is_object( $property ) ) {
			global $property;
		}

		if ( ! is_a( $property, 'PM_property' ) ) {
			return;
		}

		$shop_name = get_bloginfo( 'name' );
		$shop_url  = home_url();
		$currency  = get_pm_currency();
		$permalink = get_permalink( $property->get_id() );
		$image     = wp_get_attachment_url( $property->get_image_id() );

		$markup = array(
			'@type'       => 'RealEstateListing',
			'@id'         => $permalink . '#property', // Append '#property' to differentiate between this @id and the @id generated for the Breadcrumblist.
			'name'        => $property->get_name(),
			'url'         => $permalink,
			'description' => wp_strip_all_tags( do_shortcode( $property->get_short_description() ? $property->get_short_description() : $property->get_description() ) ),
		);

		if ( $image ) {
			$markup['image'] = $image;
		}

		// Declare SKU or fallback to ID.
		// if ( $property->get_sku() ) {
		// 	$markup['sku'] = $property->get_sku();
		// } else {
		// 	$markup['sku'] = $property->get_id();
		// }

		if ( '' !== $property->get_price() ) {
			// Assume prices will be valid until the end of next year, unless on sale and there is an end date.
			$price_valid_until = gmdate( 'Y-12-31', time() + YEAR_IN_SECONDS );

			if ( $property->is_type( 'variable' ) ) {
				$lowest  = $property->get_variation_price( 'min', false );
				$highest = $property->get_variation_price( 'max', false );

				if ( $lowest === $highest ) {
					$markup_offer = array(
						'@type'              => 'Offer',
						'price'              => wc_format_decimal( $lowest, wc_get_price_decimals() ),
						'priceValidUntil'    => $price_valid_until,
						'priceSpecification' => array(
							'price'                 => wc_format_decimal( $lowest, wc_get_price_decimals() ),
							'priceCurrency'         => $currency,
							'valueAddedTaxIncluded' => wc_prices_include_tax() ? 'true' : 'false',
						),
					);
				} else {
					$markup_offer = array(
						'@type'      => 'AggregateOffer',
						'lowPrice'   => wc_format_decimal( $lowest, wc_get_price_decimals() ),
						'highPrice'  => wc_format_decimal( $highest, wc_get_price_decimals() ),
						'offerCount' => count( $property->get_children() ),
					);
				}
			} else {
				if ( $property->is_on_sale() && $property->get_date_on_sale_to() ) {
					$price_valid_until = gmdate( 'Y-m-d', $property->get_date_on_sale_to()->getTimestamp() );
				}
				$markup_offer = array(
					'@type'              => 'Offer',
					'price'              => wc_format_decimal( $property->get_price(), wc_get_price_decimals() ),
					'priceValidUntil'    => $price_valid_until,
					'priceSpecification' => array(
						'price'                 => wc_format_decimal( $property->get_price(), wc_get_price_decimals() ),
						'priceCurrency'         => $currency,
						'valueAddedTaxIncluded' => wc_prices_include_tax() ? 'true' : 'false',
					),
				);
			}

			$markup_offer += array(
				'priceCurrency' => $currency,
				'availability'  => 'http://schema.org/' . ( $property->is_in_stock() ? 'InStock' : 'OutOfStock' ),
				'url'           => $permalink,
				'seller'        => array(
					'@type' => 'Organization',
					'name'  => $shop_name,
					'url'   => $shop_url,
				),
			);

			$markup['offers'] = array( apply_filters( 'pm_structured_data_property_offer', $markup_offer, $property ) );
		}

		// Check we have required data.
		if ( empty( $markup['aggregateRating'] ) && empty( $markup['offers'] ) && empty( $markup['review'] ) ) {
			return;
		}

		$this->set_data( $markup );
	}


	/**
	 * Generates BreadcrumbList structured data.
	 *
	 * Hooked into `pm_breadcrumb` action hook.
	 *
	 * @param PM_Breadcrumb $breadcrumbs Breadcrumb data.
	 */
	public function generate_breadcrumblist_data( $breadcrumbs ) {
		$crumbs = $breadcrumbs->get_breadcrumb();

		if ( empty( $crumbs ) || ! is_array( $crumbs ) ) {
			return;
		}

		$markup                    = array();
		$markup['@type']           = 'BreadcrumbList';
		$markup['itemListElement'] = array();

		foreach ( $crumbs as $key => $crumb ) {
			$markup['itemListElement'][ $key ] = array(
				'@type'    => 'ListItem',
				'position' => $key + 1,
				'item'     => array(
					'name' => $crumb[0],
				),
			);

			if ( ! empty( $crumb[1] ) ) {
				$markup['itemListElement'][ $key ]['item'] += array( '@id' => $crumb[1] );
			} elseif ( isset( $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'] ) ) {
				$current_url = set_url_scheme( 'http://' . wp_unslash( $_SERVER['HTTP_HOST'] ) . wp_unslash( $_SERVER['REQUEST_URI'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

				$markup['itemListElement'][ $key ]['item'] += array( '@id' => $current_url );
			}
		}

		$this->set_data( apply_filters( 'pm_structured_data_breadcrumblist', $markup, $breadcrumbs ) );
	}

}
