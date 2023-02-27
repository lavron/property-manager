<?php
/**
 * Shortcodes
 *
 * @package WooCommerce\Classes
 * @version 3.2.0
 */

defined( 'ABSPATH' ) || exit;

class PM_Shortcodes {

	/**
	 * Init shortcodes.
	 */
	public static function init() {
		$shortcodes = array(
			'property'                    => __CLASS__ . '::property',
			'property_page'               => __CLASS__ . '::property_page',
			'properties'                   => __CLASS__ . '::properties',
			'recent_properties'            => __CLASS__ . '::recent_properties',
			'featured_properties'          => __CLASS__ . '::featured_properties',
			'property_attribute'          => __CLASS__ . '::property_attribute',
		);

		foreach ( $shortcodes as $shortcode => $function ) {
			add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), $function );
		}

	}

	/**
	 * Shortcode Wrapper.
	 *
	 * @param string[] $function Callback function.
	 * @param array    $atts     Attributes. Default to empty array.
	 * @param array    $wrapper  Customer wrapper data.
	 *
	 * @return string
	 */
	public static function shortcode_wrapper(
		$function,
		$atts = array(),
		$wrapper = array(
			'class'  => 'pm',
			'before' => null,
			'after'  => null,
		)
	) {
		ob_start();

		// @codingStandardsIgnoreStart
		echo empty( $wrapper['before'] ) ? '<div class="' . esc_attr( $wrapper['class'] ) . '">' : $wrapper['before'];
		call_user_func( $function, $atts );
		echo empty( $wrapper['after'] ) ? '</div>' : $wrapper['after'];
		// @codingStandardsIgnoreEnd

		return ob_get_clean();
	}


	/**
	 * List properties in a category shortcode.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function property_category( $atts ) {
		if ( empty( $atts['category'] ) ) {
			return '';
		}

		$atts = array_merge(
			array(
				'limit'        => '12',
				'columns'      => '4',
				'orderby'      => 'menu_order title',
				'order'        => 'ASC',
				'category'     => '',
				'cat_operator' => 'IN',
			),
			(array) $atts
		);

		$shortcode = new PM_Shortcode_Products( $atts, 'property_category' );

		return $shortcode->get_content();
	}

	/**
	 * List all (or limited) property categories.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function property_categories( $atts ) {
		if ( isset( $atts['number'] ) ) {
			$atts['limit'] = $atts['number'];
		}

		$atts = shortcode_atts(
			array(
				'limit'      => '-1',
				'orderby'    => 'name',
				'order'      => 'ASC',
				'columns'    => '4',
				'hide_empty' => 1,
				'parent'     => '',
				'ids'        => '',
			),
			$atts,
			'property_categories'
		);

		$ids        = array_filter( array_map( 'trim', explode( ',', $atts['ids'] ) ) );
		$hide_empty = ( true === $atts['hide_empty'] || 'true' === $atts['hide_empty'] || 1 === $atts['hide_empty'] || '1' === $atts['hide_empty'] ) ? 1 : 0;

		// Get terms and workaround WP bug with parents/pad counts.
		$args = array(
			'orderby'    => $atts['orderby'],
			'order'      => $atts['order'],
			'hide_empty' => $hide_empty,
			'include'    => $ids,
			'pad_counts' => true,
			'child_of'   => $atts['parent'],
		);

		$property_categories = get_terms( 'property_cat', $args );

		if ( '' !== $atts['parent'] ) {
			$property_categories = wp_list_filter(
				$property_categories,
				array(
					'parent' => $atts['parent'],
				)
			);
		}

		if ( $hide_empty ) {
			foreach ( $property_categories as $key => $category ) {
				if ( 0 === $category->count ) {
					unset( $property_categories[ $key ] );
				}
			}
		}

		$atts['limit'] = '-1' === $atts['limit'] ? null : intval( $atts['limit'] );
		if ( $atts['limit'] ) {
			$property_categories = array_slice( $property_categories, 0, $atts['limit'] );
		}

		$columns = absint( $atts['columns'] );

		wc_set_loop_prop( 'columns', $columns );
		wc_set_loop_prop( 'is_shortcode', true );

		ob_start();

		if ( $property_categories ) {
			pm_property_loop_start();

			foreach ( $property_categories as $category ) {
				wc_get_template(
					'content-property_cat.php',
					array(
						'category' => $category,
					)
				);
			}

			pm_property_loop_end();
		}

		pm_reset_loop();

		return '<div class="pm columns-' . $columns . '">' . ob_get_clean() . '</div>';
	}

	/**
	 * Recent Products shortcode.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function recent_properties( $atts ) {
		$atts = array_merge(
			array(
				'limit'        => '12',
				'columns'      => '4',
				'orderby'      => 'date',
				'order'        => 'DESC',
				'category'     => '',
				'cat_operator' => 'IN',
			),
			(array) $atts
		);

		$shortcode = new PM_Shortcode_Products( $atts, 'recent_properties' );

		return $shortcode->get_content();
	}

	/**
	 * List multiple properties shortcode.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function properties( $atts ) {
		$atts = (array) $atts;
		$type = 'properties';

		// Allow list property based on specific cases.
		if ( isset( $atts['on_sale'] ) && wc_string_to_bool( $atts['on_sale'] ) ) {
			$type = 'sale_properties';
		} elseif ( isset( $atts['best_selling'] ) && wc_string_to_bool( $atts['best_selling'] ) ) {
			$type = 'best_selling_properties';
		} elseif ( isset( $atts['top_rated'] ) && wc_string_to_bool( $atts['top_rated'] ) ) {
			$type = 'top_rated_properties';
		}

		$shortcode = new PM_Shortcode_Products( $atts, $type );

		return $shortcode->get_content();
	}

	/**
	 * Display a single property.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function property( $atts ) {
		if ( empty( $atts ) ) {
			return '';
		}

		$atts['skus']  = isset( $atts['sku'] ) ? $atts['sku'] : '';
		$atts['ids']   = isset( $atts['id'] ) ? $atts['id'] : '';
		$atts['limit'] = '1';
		$shortcode     = new PM_Shortcode_Products( (array) $atts, 'property' );

		return $shortcode->get_content();
	}

	/**
	 * Display a single property price + cart button.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function property_add_to_cart( $atts ) {
		global $post;

		if ( empty( $atts ) ) {
			return '';
		}

		$atts = shortcode_atts(
			array(
				'id'         => '',
				'class'      => '',
				'quantity'   => '1',
				'sku'        => '',
				'style'      => 'border:4px solid #ccc; padding: 12px;',
				'show_price' => 'true',
			),
			$atts,
			'property_add_to_cart'
		);

		if ( ! empty( $atts['id'] ) ) {
			$property_data = get_post( $atts['id'] );
		} elseif ( ! empty( $atts['sku'] ) ) {
			$property_id   = wc_get_property_id_by_sku( $atts['sku'] );
			$property_data = get_post( $property_id );
		} else {
			return '';
		}

		$property = is_object( $property_data ) && in_array( $property_data->post_type, array( 'property', 'property_variation' ), true ) ? wc_setup_property_data( $property_data ) : false;

		if ( ! $property ) {
			return '';
		}

		ob_start();

		echo '<p class="property pm add_to_cart_inline ' . esc_attr( $atts['class'] ) . '" style="' . ( empty( $atts['style'] ) ? '' : esc_attr( $atts['style'] ) ) . '">';

		if ( wc_string_to_bool( $atts['show_price'] ) ) {
			// @codingStandardsIgnoreStart
			echo $property->get_price_html();
			// @codingStandardsIgnoreEnd
		}

		pm_template_loop_add_to_cart(
			array(
				'quantity' => $atts['quantity'],
			)
		);

		echo '</p>';

		// Restore Product global in case this is shown inside a property post.
		wc_setup_property_data( $post );

		return ob_get_clean();
	}

	/**
	 * Get the add to cart URL for a property.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function property_add_to_cart_url( $atts ) {
		if ( empty( $atts ) ) {
			return '';
		}

		if ( isset( $atts['id'] ) ) {
			$property_data = get_post( $atts['id'] );
		} elseif ( isset( $atts['sku'] ) ) {
			$property_id   = wc_get_property_id_by_sku( $atts['sku'] );
			$property_data = get_post( $property_id );
		} else {
			return '';
		}

		$property = is_object( $property_data ) && in_array( $property_data->post_type, array( 'property', 'property_variation' ), true ) ? wc_setup_property_data( $property_data ) : false;

		if ( ! $property ) {
			return '';
		}

		$_property = wc_get_property( $property_data );

		return esc_url( $_property->add_to_cart_url() );
	}

	/**
	 * List all properties on sale.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function sale_properties( $atts ) {
		$atts = array_merge(
			array(
				'limit'        => '12',
				'columns'      => '4',
				'orderby'      => 'title',
				'order'        => 'ASC',
				'category'     => '',
				'cat_operator' => 'IN',
			),
			(array) $atts
		);

		$shortcode = new PM_Shortcode_Products( $atts, 'sale_properties' );

		return $shortcode->get_content();
	}

	/**
	 * List best selling properties on sale.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function best_selling_properties( $atts ) {
		$atts = array_merge(
			array(
				'limit'        => '12',
				'columns'      => '4',
				'category'     => '',
				'cat_operator' => 'IN',
			),
			(array) $atts
		);

		$shortcode = new PM_Shortcode_Products( $atts, 'best_selling_properties' );

		return $shortcode->get_content();
	}

	/**
	 * List top rated properties on sale.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function top_rated_properties( $atts ) {
		$atts = array_merge(
			array(
				'limit'        => '12',
				'columns'      => '4',
				'orderby'      => 'title',
				'order'        => 'ASC',
				'category'     => '',
				'cat_operator' => 'IN',
			),
			(array) $atts
		);

		$shortcode = new PM_Shortcode_Products( $atts, 'top_rated_properties' );

		return $shortcode->get_content();
	}

	/**
	 * Output featured properties.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function featured_properties( $atts ) {
		$atts = array_merge(
			array(
				'limit'        => '12',
				'columns'      => '4',
				'orderby'      => 'date',
				'order'        => 'DESC',
				'category'     => '',
				'cat_operator' => 'IN',
			),
			(array) $atts
		);

		$atts['visibility'] = 'featured';

		$shortcode = new PM_Shortcode_Products( $atts, 'featured_properties' );

		return $shortcode->get_content();
	}

	/**
	 * Show a single property page.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function property_page( $atts ) {
		if ( empty( $atts ) ) {
			return '';
		}

		if ( ! isset( $atts['id'] ) && ! isset( $atts['sku'] ) ) {
			return '';
		}

		$args = array(
			'posts_per_page'      => 1,
			'post_type'           => 'property',
			'post_status'         => ( ! empty( $atts['status'] ) ) ? $atts['status'] : 'publish',
			'ignore_sticky_posts' => 1,
			'no_found_rows'       => 1,
		);

		if ( isset( $atts['sku'] ) ) {
			$args['meta_query'][] = array(
				'key'     => '_sku',
				'value'   => sanitize_text_field( $atts['sku'] ),
				'compare' => '=',
			);

			$args['post_type'] = array( 'property', 'property_variation' );
		}

		if ( isset( $atts['id'] ) ) {
			$args['p'] = absint( $atts['id'] );
		}

		// Don't render titles if desired.
		if ( isset( $atts['show_title'] ) && ! $atts['show_title'] ) {
			remove_action( 'pm_single_property_summary', 'pm_template_single_title', 5 );
		}

		// Change form action to avoid redirect.
		add_filter( 'pm_add_to_cart_form_action', '__return_empty_string' );

		$single_property = new WP_Query( $args );

		$preselected_id = '0';

		// Check if sku is a variation.
		if ( isset( $atts['sku'] ) && $single_property->have_posts() && 'property_variation' === $single_property->post->post_type ) {

			$variation  = wc_get_property_object( 'variation', $single_property->post->ID );
			$attributes = $variation->get_attributes();

			// Set preselected id to be used by JS to provide context.
			$preselected_id = $single_property->post->ID;

			// Get the parent property object.
			$args = array(
				'posts_per_page'      => 1,
				'post_type'           => 'property',
				'post_status'         => 'publish',
				'ignore_sticky_posts' => 1,
				'no_found_rows'       => 1,
				'p'                   => $single_property->post->post_parent,
			);

			$single_property = new WP_Query( $args );
			?>
			<script type="text/javascript">
				jQuery( document ).ready( function( $ ) {
					var $variations_form = $( '[data-property-page-preselected-id="<?php echo esc_attr( $preselected_id ); ?>"]' ).find( 'form.variations_form' );

					<?php foreach ( $attributes as $attr => $value ) { ?>
						$variations_form.find( 'select[name="<?php echo esc_attr( $attr ); ?>"]' ).val( '<?php echo esc_js( $value ); ?>' );
					<?php } ?>
				});
			</script>
			<?php
		}

		// For "is_single" to always make load comments_template() for reviews.
		$single_property->is_single = true;

		ob_start();

		global $wp_query;

		// Backup query object so following loops think this is a property page.
		$previous_wp_query = $wp_query;
		// @codingStandardsIgnoreStart
		$wp_query          = $single_property;
		// @codingStandardsIgnoreEnd

		wp_enqueue_script( 'wc-single-property' );

		while ( $single_property->have_posts() ) {
			$single_property->the_post()
			?>
			<div class="single-property" data-property-page-preselected-id="<?php echo esc_attr( $preselected_id ); ?>">
				<?php wc_get_template_part( 'content', 'single-property' ); ?>
			</div>
			<?php
		}

		// Restore $previous_wp_query and reset post data.
		// @codingStandardsIgnoreStart
		$wp_query = $previous_wp_query;
		// @codingStandardsIgnoreEnd
		wp_reset_postdata();

		// Re-enable titles if they were removed.
		if ( isset( $atts['show_title'] ) && ! $atts['show_title'] ) {
			add_action( 'pm_single_property_summary', 'pm_template_single_title', 5 );
		}

		remove_filter( 'pm_add_to_cart_form_action', '__return_empty_string' );

		return '<div class="pm">' . ob_get_clean() . '</div>';
	}

	/**
	 * Show messages.
	 *
	 * @return string
	 */
	public static function shop_messages() {
		if ( ! function_exists( 'wc_print_notices' ) ) {
			return '';
		}
		return '<div class="pm">' . wc_print_notices( true ) . '</div>';
	}

	/**
	 * Order by rating.
	 *
	 * @deprecated 3.2.0 Use PM_Shortcode_Products::order_by_rating_post_clauses().
	 * @param      array $args Query args.
	 * @return     array
	 */
	public static function order_by_rating_post_clauses( $args ) {
		return PM_Shortcode_Products::order_by_rating_post_clauses( $args );
	}

	/**
	 * List properties with an attribute shortcode.
	 * Example [property_attribute attribute="color" filter="black"].
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function property_attribute( $atts ) {
		$atts = array_merge(
			array(
				'limit'     => '12',
				'columns'   => '4',
				'orderby'   => 'title',
				'order'     => 'ASC',
				'attribute' => '',
				'terms'     => '',
			),
			(array) $atts
		);

		if ( empty( $atts['attribute'] ) ) {
			return '';
		}

		$shortcode = new PM_Shortcode_Products( $atts, 'property_attribute' );

		return $shortcode->get_content();
	}

	/**
	 * List related properties.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function related_properties( $atts ) {
		if ( isset( $atts['per_page'] ) ) {
			$atts['limit'] = $atts['per_page'];
		}

		// @codingStandardsIgnoreStart
		$atts = shortcode_atts( array(
			'limit'    => '4',
			'columns'  => '4',
			'orderby'  => 'rand',
		), $atts, 'related_properties' );
		// @codingStandardsIgnoreEnd

		ob_start();

		// Rename arg.
		$atts['posts_per_page'] = absint( $atts['limit'] );

		pm_related_properties( $atts );

		return ob_get_clean();
	}
}
