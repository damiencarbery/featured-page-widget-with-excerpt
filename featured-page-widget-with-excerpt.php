<?php
/*
Plugin Name: Genesis Featured Page Widget (with Excerpt)
Plugin URI: https://www.damiencarbery.com/2017/07/genesis-featured-page-widget-with-excerpt-support/
Description: Extension of Genesis Featured Page widget, with option to use excerpt instead of character count. Requires Genesis framework.
Author: Damien Carbery
Author URI: https://www.damiencarbery.com
Version: $Revision: 4772 $
Genesis tested up to: 3.1.1
$Id: featured-page-widget-with-excerpt.php 4772 2019-09-01 20:04:08Z damien $
*/


// Enable excerpts in pages.
add_action( 'init', 'wpcodex_add_excerpt_support_for_pages' );
function wpcodex_add_excerpt_support_for_pages() {
	add_post_type_support( 'page', 'excerpt' );
}


/**
 * Featured Page widget with excerpt class - a slightly modified Genesis Featured Post widget class - adds optional use of excerpt.
 */
class Genesis_Featured_Page_Excerpt extends WP_Widget {

	/**
	 * Holds widget settings defaults, populated in constructor.
	 *
	 * @var array
	 */
	protected $defaults;

	/**
	 * Constructor. Set the default widget options and create widget.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->defaults = [
			'title'           => '',
			'page_id'         => '',
			'show_image'      => 0,
			'image_alignment' => '',
			'image_size'      => '',
			'show_title'      => 0,
			'content_type'    => 'none',
			'show_content'    => 0,
			'content_limit'   => '',
			'more_text'       => '',
		];

		$widget_ops = [
			'classname'   => 'featured-content featuredpage',
			'description' => __( 'Displays featured page (with excerpt or content) with thumbnails', 'genesis' ),
		];

		$control_ops = [
			'id_base' => 'featured-page-excerpt',
			'width'   => 200,
			'height'  => 250,
		];

		parent::__construct( 'featured-page-excerpt', __( 'Genesis - Featured Page (w/excerpt)', 'genesis' ), $widget_ops, $control_ops );

	}

	/**
	 * Echo the widget content.
	 *
	 * @since 1.0.0
	 *
	 * @global WP_Query $wp_query Query object.
	 * @global int      $more
	 *
	 * @param array $args     Display arguments including `before_title`, `after_title`,
	 *                        `before_widget`, and `after_widget`.
	 * @param array $instance The settings for the particular instance of the widget.
	 */
	public function widget( $args, $instance ) {

		global $wp_query;

		// Merge with defaults.
		$instance = wp_parse_args( (array) $instance, $this->defaults );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $args['before_widget'];

		// Set up the author bio.
		if ( ! empty( $instance['title'] ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base ) . $args['after_title'];
		}

		$wp_query = new WP_Query( // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Reset later.
			[
				'page_id' => $instance['page_id'],
			]
		);

		if ( have_posts() ) {

			while ( have_posts() ) {

				the_post();

				genesis_markup(
					[
					'open'    => '<article %s>',
					'context' => 'entry',
					'params'  => [
						'is_widget' => true,
						],
					]
				);

				$image = genesis_get_image(
					[
					'format'  => 'html',
					'size'    => $instance['image_size'],
					'context' => 'featured-page-widget',
					'attr'    => genesis_parse_attr( 'entry-image-widget', [] ),
					]
				);

				if ( $image && $instance['show_image'] ) {
					$role = empty( $instance['show_title'] ) ? '' : ' aria-hidden="true" tabindex="-1"';
					printf(
						'<a href="%s" class="%s"%s>%s</a>',
						esc_url( get_permalink() ),
						esc_attr( $instance['image_alignment'] ),
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaping breaks output here
						$role,
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaping breaks output here
						wp_make_content_images_responsive( $image )
					);
				}

				if ( ! empty( $instance['show_title'] ) ) {

					$title = get_the_title() ?: __( '(no title)', 'genesis' );

					/**
					 * Filter the featured page widget title.
					 *
					 * @since  2.2.0
					 *
					 * @param string $title    Featured page title.
					 * @param array  $instance {
					 *     Widget settings for this instance.
					 *
					 *     @type string $title           Widget title.
					 *     @type int    $page_id         ID of the featured page.
					 *     @type bool   $show_image      True if featured image should be shown, false
					 *                                   otherwise.
					 *     @type string $image_alignment Image alignment: `alignnone`, `alignleft`,
					 *                                   `aligncenter` or `alignright`.
					 *     @type string $image_size      Name of the image size.
					 *     @type bool   $show_title      True if featured page title should be shown,
					 *                                   false otherwise.
					 *     @type bool   $show_content    True if featured page content should be shown,
					 *                                   false otherwise.
					 *     @type int    $content_limit   Amount of content to show, in characters.
					 *     @type int    $more_text       Text to use for More link.
					 * }
					 * @param array  $args     {
					 *     Widget display arguments.
					 *
					 *     @type string $before_widget Markup or content to display before the widget.
					 *     @type string $before_title  Markup or content to display before the widget title.
					 *     @type string $after_title   Markup or content to display after the widget title.
					 *     @type string $after_widget  Markup or content to display after the widget.
					 * }
					 */
					$title = apply_filters( 'genesis_featured_page_title', $title, $instance, $args );
					$heading = genesis_a11y( 'headings' ) ? 'h4' : 'h2';

					$entry_title = genesis_markup( 
						[
						'open'    => "<{$heading} %s>",
						'close'   => "</{$heading}>",
						'context' => 'entry-title',
						'content' => sprintf( '<a href="%s">%s</a>', get_permalink(), $title ),
						'params'  => [
							'is_widget' => true,
							'wrap'      => $heading,
						],
						'echo'    => false,
						]
					);

					genesis_markup(
						[
						'open'    => '<header %s>',
						'close'   => '</header>',
						'context' => 'entry-header',
						'content' => $entry_title,
						'params'  => [
							'is_widget' => true,
						],
						]
					);

				}

				if ( ! empty( $instance['content_type'] ) && 'none' != $instance['content_type'] ) {

					genesis_markup(
						[
						'open'    => '<div %s>',
						'context' => 'entry-content',
						'params'  => [
							'is_widget' => true,
						],
						]
					);
					
					if ( 'excerpt' == $instance['content_type'] ) {
						the_excerpt();
						if ( ! empty( $instance['more_text'] ) ) {
							printf( '<a href="%s" class="more-link">%s</a>', get_permalink(), genesis_a11y_more_link( $instance['more_text'] ) );
						}
					}
					else {
						if ( empty( $instance['content_limit'] ) ) {

							global $more;

							$orig_more = $more;
							$more      = 0; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Temporary change.

							the_content( genesis_a11y_more_link( $instance['more_text'] ) );

							$more = $orig_more; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Global is being restored.

						} else {
							the_content_limit( (int) $instance['content_limit'], genesis_a11y_more_link( esc_html( $instance['more_text'] ) ) );
						}
					}

					genesis_markup(
						[
						'close'   => '</div>',
						'context' => 'entry-content',
						'params'  => [
							'is_widget' => true,
						],
					]
				);

				}

				genesis_markup(
					[
					'close'   => '</article>',
					'context' => 'entry',
					'params'  => [
						'is_widget' => true,
						],
					]
				);

			}
		}

		// Restore original query.
		wp_reset_query(); // phpcs:ignore WordPress.WP.DiscouragedFunctions.wp_reset_query_wp_reset_query -- Making sure the query is really reset.

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $args['after_widget'];

	}

	/**
	 * Update a particular instance.
	 *
	 * This function should check that `$new_instance` is set correctly.
	 * The newly calculated value of $instance should be returned.
	 * If "false" is returned, the instance won't be saved/updated.
	 *
	 * @since 1.0.0
	 *
	 * @param array $new_instance New settings for this instance as input by the user via `form()`.
	 * @param array $old_instance Old settings for this instance.
	 * @return array Settings to save or bool false to cancel saving.
	 */
	public function update( $new_instance, $old_instance ) {

		$new_instance['title']     = wp_strip_all_tags( $new_instance['title'] );
		$new_instance['more_text'] = wp_strip_all_tags( $new_instance['more_text'] );
		return $new_instance;

	}

	/**
	 * Echo the settings update form.
	 *
	 * @since 1.0.0
	 *
	 * @param array $instance Current settings.
	 * @return void
	 */
	public function form( $instance ) {

		// Merge with defaults.
		$instance = wp_parse_args( (array) $instance, $this->defaults );

		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', 'genesis' ); ?>:</label>
			<input type="text" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" value="<?php echo esc_attr( $instance['title'] ); ?>" class="widefat" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'page_id' ); ?>"><?php _e( 'Page', 'genesis' ); ?>:</label>
			<?php
			wp_dropdown_pages(
				[
					// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- False positive.
					'name'     => $this->get_field_name( 'page_id' ),
					'id'       => $this->get_field_id( 'page_id' ),
					'exclude'  => get_option( 'page_for_posts' ),
					'selected' => $instance['page_id'],
					// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
				]
			);
			?>
		</p>

		<hr class="div" />

		<p>
			<input id="<?php echo esc_attr( $this->get_field_id( 'show_image' ) ); ?>" type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'show_image' ) ); ?>" value="1"<?php checked( $instance['show_image'] ); ?> />
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_image' ) ); ?>"><?php esc_html_e( 'Show Featured Image', 'genesis' ); ?></label>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'image_size' ) ); ?>"><?php esc_html_e( 'Image Size', 'genesis' ); ?>:</label>
			<select id="<?php echo esc_attr( $this->get_field_id( 'image_size' ) ); ?>" class="genesis-image-size-selector" name="<?php echo esc_attr( $this->get_field_name( 'image_size' ) ); ?>">
				<?php
				$sizes = genesis_get_image_sizes();
				foreach ( $sizes as $name => $size ) {
					echo '<option value="' . esc_attr( $name ) . '" ' . selected( $name, $instance['image_size'], false ) . '>' . esc_html( $name ) . ' (' . absint( $size['width'] ) . 'x' . absint( $size['height'] ) . ')</option>';
				}
				?>
			</select>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'image_alignment' ) ); ?>"><?php esc_html_e( 'Image Alignment', 'genesis' ); ?>:</label>
			<select id="<?php echo esc_attr( $this->get_field_id( 'image_alignment' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'image_alignment' ) ); ?>">
				<option value="alignnone">- <?php esc_html_e( 'None', 'genesis' ); ?> -</option>
				<option value="alignleft" <?php selected( 'alignleft', $instance['image_alignment'] ); ?>><?php esc_html_e( 'Left', 'genesis' ); ?></option>
				<option value="alignright" <?php selected( 'alignright', $instance['image_alignment'] ); ?>><?php esc_html_e( 'Right', 'genesis' ); ?></option>
				<option value="aligncenter" <?php selected( 'aligncenter', $instance['image_alignment'] ); ?>><?php esc_html_e( 'Center', 'genesis' ); ?></option>
			</select>
		</p>

		<hr class="div" />

		<p>
			<input id="<?php echo esc_attr( $this->get_field_id( 'show_title' ) ); ?>" type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'show_title' ) ); ?>" value="1"<?php checked( $instance['show_title'] ); ?> />
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_title' ) ); ?>"><?php esc_html_e( 'Show Page Title', 'genesis' ); ?></label>
		</p>

        <p>
            Show content:<br />
            <label><input type="radio" name="<?php echo esc_attr( $this->get_field_name( 'content_type' ) ); ?>" value="none" <?php checked( $instance['content_type'], 'none' ); ?> />No content</label><br />
            <label><input type="radio" name="<?php echo esc_attr( $this->get_field_name( 'content_type' ) ); ?>" value="excerpt" <?php checked( $instance['content_type'], 'excerpt' ); ?> />Show Page Excerpt</label><br />
            <label><input type="radio" name="<?php echo esc_attr( $this->get_field_name( 'content_type' ) ); ?>" value="content" <?php checked( $instance['content_type'], 'content' ); ?> />Show Page Content (set optional limit below)</label><br />
        </p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'content_limit' ) ); ?>"><?php esc_html_e( 'Content Character Limit', 'genesis' ); ?>:</label>
			<input type="text" id="<?php echo esc_attr( $this->get_field_id( 'content_limit' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'content_limit' ) ); ?>" value="<?php echo esc_attr( $instance['content_limit'] ); ?>" size="3" />
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'more_text' ) ); ?>"><?php esc_html_e( 'More Text', 'genesis' ); ?>:</label>
			<input type="text" id="<?php echo esc_attr( $this->get_field_id( 'more_text' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'more_text' ) ); ?>" value="<?php echo esc_attr( $instance['more_text'] ); ?>" />
		</p>
		<?php

	}

}


// Load this widget.
add_action( 'widgets_init', 'fpwe_load_widgets' );
function fpwe_load_widgets() {
    register_widget( 'Genesis_Featured_Page_Excerpt' );
}
