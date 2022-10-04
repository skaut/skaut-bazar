<?php

/**
 * Plugin name: Scout bazar
 * Plugin URI: https://github.com/skaut/skaut-bazar
 * Description: Bazar for Scout troops
 * Version: 1.3.6
 * Author: Junák - český skaut
 * Author URI: https://github.com/skaut
 * Text Domain: skaut-bazar
 */

namespace SkautBazar;

/**
 * Registers a script file
 *
 * Registers a script so that it can later be enqueued by `wp_enqueue_script()`.
 *
 * @param string $handle A unique handle to identify the script with. This handle should be passed to `wp_enqueue_script()`.
 * @param string $src Path to the file, relative to the plugin directory.
 * @param array  $deps A list of dependencies of the script. These can be either system dependencies like jquery, or other registered scripts. Default [].
 */
function register_script( $handle, $src, $deps = array() ) {
	$file = plugin_dir_path( __FILE__ ) . $src;
	wp_register_script( $handle, plugin_dir_url( __FILE__ ) . $src, $deps, file_exists( $file ) ? filemtime( $file ) : false, true );
}

/**
 * Registers a style file
 *
 * Registers a style so that it can later be enqueued by `wp_enqueue_style()`.
 *
 * @param string $handle A unique handle to identify the style with. This handle should be passed to `wp_enqueue_style()`.
 * @param string $src Path to the file, relative to the plugin directory.
 * @param array  $deps A list of dependencies of the style. These can be either system dependencies or other registered styles. Default [].
 */
function register_style( $handle, $src, $deps = array() ) {
	$file = plugin_dir_path( __FILE__ ) . $src;
	wp_register_style( $handle, plugin_dir_url( __FILE__ ) . $src, $deps, file_exists( $file ) ? filemtime( $file ) : false );
}

/**
 * Enqueues a script file
 *
 * Registers and immediately enqueues a script. Note that you should **not** call this function if you've previously registered the script using `register_script()`.
 *
 * @param string $handle A unique handle to identify the script with.
 * @param string $src Path to the file, relative to the plugin directory.
 * @param array  $deps A list of dependencies of the script. These can be either system dependencies like jquery, or other registered scripts. Default [].
 */
function enqueue_script( $handle, $src, $deps = array() ) {
	register_script( $handle, $src, $deps );
	wp_enqueue_script( $handle );
}

/**
 * Enqueues a style file
 *
 * Registers and immediately enqueues a style. Note that you should **not** call this function if you've previously registered the style using `register_style()`.
 *
 * @param string $handle A unique handle to identify the style with.
 * @param string $src Path to the file, relative to the plugin directory.
 * @param array  $deps A list of dependencies of the style. These can be either system dependencies or other registered styles. Default [].
 */
function enqueue_style( $handle, $src, $deps = array() ) {
	register_style( $handle, $src, $deps );
	wp_enqueue_style( $handle );
}

class Bazar {

	public function __construct() {
		if ( is_admin() ) {
			add_action( 'wp_ajax_skautbazar_rezervace', array( $this, 'skautbazar_rezervace' ) );
			add_action( 'wp_ajax_nopriv_skautbazar_rezervace', array( $this, 'skautbazar_rezervace' ) );
		}

		register_activation_hook( __FILE__, array( $this, 'init' ) );
		register_uninstall_hook( __FILE__, array( 'SkautBazar\\Bazar', 'unregisterNewCapabilities' ) );

		// Plugin deprecation notice
		add_action( 'admin_notices', array( $this, 'deprecation_notice' ) );

		// actions
		add_action( 'init', array( &$this, 'skautbazar_cpt' ) );
		add_action( 'do_meta_boxes', array( $this, 'skautbazar_box' ), 100 );
		add_action( 'save_post', array( $this, 'skautbazar_save' ) );
		add_action( 'edit_form_after_title', array( $this, 'skautbazar_recreate_box' ) );
		add_action( 'publish_skautbazar', array( $this, 'skautbazarAutomaticallyCreateTitlePost' ), 11, 3 );
		add_action( 'manage_skautbazar_posts_custom_column', array( $this, 'skautbazar_manage_columns' ), 10, 2 );
		add_action( 'admin_menu', array( $this, 'skautbazar_option_page' ) );
		add_action( 'load-edit.php', array( $this, 'skautbazar_load_custom_filter' ) );

		// filters
		add_filter( 'manage_edit-skautbazar_columns', array( &$this, 'skautbazar_columns' ) );
		add_filter( 'views_edit-skautbazar', array( $this, 'skautbazar_views' ), 10, 1 );
		add_action( 'init', array( $this, 'custom_post_status' ) );

		// shortcodes
		add_shortcode( 'skautbazar', array( $this, 'skautbazar_shortcode' ) );
	}

	function deprecation_notice() {
		echo '<div class="notice notice-error"><p>';
		printf(
			/* translators: 1: Start of a link to the WPAdverts plugin 2: End of the link to the WPAdverts plugin 3: Start of a link to install 4: End of the installl link */
			esc_html__(
				'The Skaut bazar plugin is deprecated, will not receive any updates and will be closed at the end of 2022. We recommend using the %1$sWPAdverts%2$s plugin instead. Install it %3$shere%4$s.',
				'skaut-bazar'
			),
			'<a target="_blank" rel="noreferrer noopener" href="' . esc_url( 'https://wordpress.org/plugins/wpadverts/' ) . '">',
			'</a>',
			'<a href="' . esc_url( admin_url( 'plugin-install.php?s=WPAdverts&tab=search&type=term' ) ) . '">',
			'</a>'
		);
		echo '</p></div>';;
	}

	function custom_post_status() {
		register_post_status(
			'aggregated',
			array(
				'label'                     => _x( 'Aggregated', 'recipes' ),
				'public'                    => false,
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Aggregated <span class="count">(%s)</span>', 'Aggregated <span class="count">(%s)</span>' ),
			)
		);
	}


	public function init() {
		flush_rewrite_rules();
		if ( ! get_option( 'skautbazar_option' ) ) {
			$skautbazar_option = array(
				'status'                    => array(
					1 => 'active',
					2 => 'reserverd',
					3 => 'archive',
				),
				'default_author'            => array(
					'author_name'     => '',
					'author_lastname' => '',
					'author_email'    => '',
					'author_tel'      => '',
				),
				'required_fields'           => array(
					'author_name' => 1,
					'author_lastname' => 1,
					'image'       => 1,
					'phone'       => 0,
					'amount'      => 0,
					'size'        => 0,
				),
				'poradove_cislo'            => 1,
				'default_currency_position' => 'right',
				'default_currency'          => '',
				'allow_buyer_message'       => 0,
				'disable_author_lastname'   => 0,
				'hide_inzerat_number'       => 0,
			);
			add_option( 'skautbazar_option', $skautbazar_option );
		}

		$this->registerNewCapabilities();
	}


	function skautbazar_views( $views ) {
		global $posts;

		wp_reset_query();
		wp_reset_postdata();

		$args_active = array(
			'post_type'      => 'skautbazar',
			'order'          => 'asc',
			'posts_per_page' => -1,
			'meta_key'       => 'skautbazar_status',
			'meta_value'     => 1,
			'post_status'    => 'publish',
		);

		$args_reserved = array(
			'post_type'      => 'skautbazar',
			'order'          => 'asc',
			'posts_per_page' => -1,
			'meta_key'       => 'skautbazar_status',
			'meta_value'     => 2,
			'post_status'    => 'publish',
		);

		$args_archived = array(
			'post_type'      => 'skautbazar',
			'order'          => 'asc',
			'posts_per_page' => -1,
			'meta_key'       => 'skautbazar_status',
			'meta_value'     => 3,
			'post_status'    => 'publish',
		);

		$query_active = '';
		$query_active = new \WP_Query( $args_reserved );
		$active_count = $query_active->found_posts;

		$query_reserved = '';
		$query_reserved = new \WP_Query( $args_reserved );
		$reserved_count = $query_reserved->found_posts;

		$query_archived = '';
		$query_archived = new \WP_Query( $args_archived );
		$archived_count = $query_archived->found_posts;

		$views['active']   = '<a href="edit.php?skautbazar_status=1&post_type=skautbazar">' . __( 'Active', 'skaut-bazar' ) . '</a>';
		$views['archived'] = '<a href="edit.php?skautbazar_status=3&post_type=skautbazar">' . __( 'Archived', 'skaut-bazar' ) . '</a>';
		$views['reserved'] = '<a href="edit.php?skautbazar_status=2&post_type=skautbazar">' . __( 'Reserved', 'skaut-bazar' ) . '</a>';

		return $views;
	}



	function skautbazar_load_custom_filter() {
		global $typenow;

		if ( 'skautbazar' != $typenow ) {
			return;
		}

		add_filter( 'parse_query', array( $this, 'skautbazar_list_post_where' ) );
	}



	function skautbazar_list_post_where( $query ) {
		 global $wpdb;

		if ( isset( $_GET['skautbazar_status'] ) ) {
			$query->query_vars['meta_key']   = 'skautbazar_status';
			$query->query_vars['meta_value'] = intval( $_GET['skautbazar_status'] );
		}
	}


	public function skautbazar_cpt() {
		$labels = array(
			'name'               => __( 'Bazar', 'Post Type General Name', 'skaut-bazar' ),
			'singular_name'      => __( 'Bazar', 'Post Type Singular Name', 'skaut-bazar' ),
			'menu_name'          => __( 'Bazar', 'skaut-bazar' ),
			'name_admin_bar'     => __( 'Bazar', 'skaut-bazar' ),
			'parent_item_colon'  => __( 'Advertisements', 'skaut-bazar' ),
			'all_items'          => __( 'All advertisements', 'skaut-bazar' ),
			'add_new_item'       => __( 'Add advertisement', 'skaut-bazar' ),
			'add_new'            => __( 'New advertisement', 'skaut-bazar' ),
			'new_item'           => __( 'New advertisement', 'skaut-bazar' ),
			'edit_item'          => __( 'Edit advertisement', 'skaut-bazar' ),
			'update_item'        => __( 'Refresh advertisement', 'skaut-bazar' ),
			'view_item'          => __( 'View advertisement', 'skaut-bazar' ),
			'search_items'       => __( 'Search in advertisement', 'skaut-bazar' ),
			'not_found'          => __( 'Not found', 'skaut-bazar' ),
			'not_found_in_trash' => __( 'Not found in trash', 'skaut-bazar' ),
		);
		$args   = array(
			'label'               => __( 'skautbazar', 'skaut-bazar' ),
			'description'         => __( 'Bazar', 'skaut-bazar' ),
			'labels'              => $labels,
			'supports'            => false,
			'taxonomies'          => array( 'category', 'post_tag' ),
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => false,
			'menu_icon'           => 'dashicons-cart',
			'map_meta_cap'        => true,
		);
		register_post_type( 'skautbazar', $args );
	}

	private function registerNewCapabilities() {
		$admins = get_role( 'administrator' );
		$admins->add_cap( 'publish_bazars' );
		$admins->add_cap( 'edit_bazars' );
		$admins->add_cap( 'edit_others_bazars' );
		$admins->add_cap( 'read_private_bazars' );
		$admins->add_cap( 'edit_bazar' );
		$admins->add_cap( 'delete_bazar' );
		$admins->add_cap( 'read_bazar' );
	}

	private static function unregisterNewCapabilities() {
		global $wp_roles;
		$deleteCaps = array(
			'publish_bazars',
			'edit_bazars',
			'edit_others_bazars',
			'read_private_bazars',
			'edit_bazar',
			'delete_bazar',
			'read_bazar',
		);
		foreach ( $deleteCaps as $cap ) {
			foreach ( array_keys( $wp_roles->roles ) as $role ) {
				$wp_roles->remove_cap( $role, $cap );
			}
		}
	}

	public function skautbazar_columns( $columns ) {
		$columns = array(
			'cb'           => '<input type="checkbox" />',
			'title'        => __( 'Advertisement No.', 'skaut-bazar' ),
			'status'       => __( 'Status', 'skaut-bazar' ),
			'inzerat_name' => __( 'Advertisement title', 'skaut-bazar' ),
			'categories'   => __( 'Category', 'skaut-bazar' ),
			'tags'         => __( 'Tags', 'skaut-bazar' ),
			'date'         => __( 'Date', 'skaut-bazar' ),
		);
		return $columns;
	}


	public function skautbazar_get_status_value( $post_id ) {
		$status = get_post_meta( $post_id, 'skautbazar_status', true );

		switch ( $status ) {
			case 1:
				return __( 'Active', 'skaut-bazar' );

			case 2:
				return __( 'Reserved', 'skaut-bazar' );

			case 3:
				return __( 'Archive', 'skaut-bazar' );

			default:
				return __( 'Unknown', 'skaut-bazar' );
		}
	}


	public function skautbazar_get_inzerat_name( $post_id ) {
		$url             = get_edit_post_link( $post_id );
		$skautbazar_meta = get_post_meta( $post_id, '_skautbazar_meta', true );

		if ( ! isset( $skautbazar_meta['inzerat']['title'] ) || $skautbazar_meta['inzerat']['title'] == '' ) {
			return __( 'No title', 'skaut-bazar' );
		}

		return $skautbazar_meta['inzerat']['title'];
	}


	public function skautbazar_get_inzerat_no( $post_id ) {
		 return get_the_title( $post_id );
	}


	public function skautbazar_manage_columns( $column, $post_id ) {
		global $post;

		switch ( $column ) {
			case 'custom_title':
				echo esc_html( $this->skautbazar_get_inzerat_no( $post_id ) );
				break;
			case 'status':
				echo esc_html( $this->skautbazar_get_status_value( $post_id ) );
				break;
			case 'inzerat_name':
				echo esc_html( $this->skautbazar_get_inzerat_name( $post_id ) );
				break;
			default:
				break;
		}
	}


	public function skautbazar_box() {
		add_meta_box(
			'skautbazar_metabox',
			__( 'Advertisement', 'skaut-bazar' ),
			array( $this, 'skautbazar_box_value' ),
			'skautbazar',
			'first_place',
			'high'
		);
	}


	function skautbazar_save( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST['skautbazar_meta_box_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['skautbazar_meta_box_nonce'] ) ), 'skautbazar_meta_box' ) ) {
			return;
		}

		if ( ! isset( $_POST['skautbazar_firstname_inzerat_autor'] ) ) {
			return;
		}

		if ( ! isset( $_POST['skautbazar_email_inzerat_autor'] ) ) {
			return;
		}

		if ( ! isset( $_POST['skautbazar_type_author'] ) ) {
			return;
		}

		$skautbazar_status        = isset( $_POST['skautbazar_status'] ) ? sanitize_text_field( wp_unslash( $_POST['skautbazar_status'] ) ) : '';
		$skautbazar_title         = isset( $_POST['skautbazar_title'] ) ? sanitize_text_field( wp_unslash( $_POST['skautbazar_title'] ) ) : '';
		$skautbazar_name          = isset( $_POST['skautbazar_firstname_inzerat_autor'] ) ? sanitize_text_field( wp_unslash( $_POST['skautbazar_firstname_inzerat_autor'] ) ) : '';
		$skautbazar_lastname      = isset( $_POST['skautbazar_lastname_inzerat_autor'] ) ? sanitize_text_field( wp_unslash( $_POST['skautbazar_lastname_inzerat_autor'] ) ) : '';
		$skautbazar_email         = isset( $_POST['skautbazar_email_inzerat_autor'] ) ? sanitize_email( wp_unslash( $_POST['skautbazar_email_inzerat_autor'] ) ) : '';
		$skautbazar_telefon       = isset( $_POST['skautbazar_telefon_inzerat_autor'] ) ? sanitize_text_field( wp_unslash( $_POST['skautbazar_telefon_inzerat_autor'] ) ) : '';
		$skautbazar_type          = isset( $_POST['skautbazar_type_author'] ) ? sanitize_text_field( wp_unslash( $_POST['skautbazar_type_author'] ) ) : '';
		$skautbazar_type_price    = isset( $_POST['skautbazar_price'] ) ? sanitize_text_field( wp_unslash( $_POST['skautbazar_price'] ) ) : '';
		$skautbazar_type_exchange = isset( $_POST['skautbazar_exchange'] ) ? sanitize_text_field( wp_unslash( $_POST['skautbazar_exchange'] ) ) : '';
		$skautbazar_amount        = isset( $_POST['skautbazar_mnozstvi_inzerat_autor'] ) ? sanitize_text_field( wp_unslash( $_POST['skautbazar_mnozstvi_inzerat_autor'] ) ) : '';
		$skautbazar_size          = isset( $_POST['skautbazar_velikost_inzerat_autor'] ) ? sanitize_text_field( wp_unslash( $_POST['skautbazar_velikost_inzerat_autor'] ) ) : '';
		$skautbazar_img           = isset( $_POST['skautbazar_image_id'] ) ? sanitize_text_field( wp_unslash( $_POST['skautbazar_image_id'] ) ) : '';

		if ( $skautbazar_status == 1 ) {
			$skautbazar_buyer_email   = '';
			$skautbazar_buyer_message = '';
		} else {
			$skautbazar_buyer_email   = isset( $_POST['skautbazar_buyer_email'] ) ? sanitize_email( wp_unslash( $_POST['skautbazar_buyer_email'] ) ) : '';
			$skautbazar_buyer_message = isset( $_POST['skautbazar_buyer_message'] ) ? sanitize_text_field( wp_unslash( $_POST['skautbazar_buyer_message'] ) ) : '';
		}

		$skautbazar_item = array(
			'inzerat' => array(
				'name'        => $skautbazar_name,
				'lastname'    => $skautbazar_lastname,
				'email'       => $skautbazar_email,
				'telefon'     => $skautbazar_telefon,
				'amount'      => $skautbazar_amount,
				'size'        => $skautbazar_size,
				'type'        => $skautbazar_type,
				'price'       => $skautbazar_type_price,
				'exchange'    => $skautbazar_type_exchange,
				'img'         => $skautbazar_img,
				'status'      => $skautbazar_status,
				'buyer_email' => $skautbazar_buyer_email,
				'no'          => $skautbazar_no,
				'title'       => $skautbazar_title,
			),
		);

		update_post_meta( $post_id, '_skautbazar_meta', $skautbazar_item );
		update_post_meta( $post_id, 'skautbazar_status', $skautbazar_status );
		if ( isset( $_POST['_skautbazar_meta_description'] ) ) {
			update_post_meta( $post_id, '_skautbazar_meta_description', sanitize_meta( '_skautbazar_meta_description', wp_unslash( $_POST['_skautbazar_meta_description'] ), 'post' ) );
		}
	}


	function disable_autosave() {
		global $post;
		if ( get_post_type( $post->ID ) === 'skaut-bazar' ) {
			wp_deregister_script( 'autosave' );
		}
	}


	private function getNextAdvertisementNumber() {
		 $skautbazar_option  = get_option( 'skautbazar_option' );
		$nextAdvertisementNo = $skautbazar_option['poradove_cislo'];

		$this->skautbazar_add_no();

		return $nextAdvertisementNo;
	}


	public function skautbazar_add_no() {
		$skautbazar_option                    = get_option( 'skautbazar_option' );
		$skautbazar_option['poradove_cislo'] += 1;
		update_option( 'skautbazar_option', $skautbazar_option );
	}


	public function skautbazarAutomaticallyCreateTitlePost( $post_id, $post ) {
		if ( get_post_status() != 'auto-draft' ) {
			return;
		}

		$desired_type  = 'skautbazar';
		$next_adver_no = $this->getNextAdvertisementNumber();

		if ( $desired_type === $post->post_type ) {
			$post_title = $next_adver_no;
			$post_name  = sanitize_title( $post_title, $post_id );

			global $wpdb;
			// $wpdb->update( $wpdb->posts, array( 'post_name' => $post_name, 'post_title' => $post_title ), array( 'ID' => $post_id ) );
			$wpdb->update(
				$wpdb->posts,
				array(
					'post_name'  => $post_name,
					'post_title' => $post_title,
				),
				array( 'ID' => $post_id )
			);
		}
	}


	function skautbazar_recreate_box() {
		global $post_type, $post;
		do_meta_boxes( $post_type, 'first_place', $post );

		// do_meta_boxes( get_current_screen(), 'first_place', $post );

		// Remove the initial "advanced" meta boxes:
		// unset($wp_meta_boxes['post']['first_place']);
	}


	public function skautbazar_box_value( $post ) {
		wp_enqueue_media();

		enqueue_style( 'skaut-bazar-admin', 'includes/css/admin.style.skautbazar.css' );

		register_script( 'skaut-bazar-admin', 'includes/js/jquery.admin.skautbazar.js', array( 'jquery' ) );
		$translation = array(
			'fill_required_field' => __( 'Please fill required field', 'skaut-bazar' ),
			'active'              => __( 'Active, save changes', 'skaut-bazar' ),
		);
		wp_localize_script( 'skaut-bazar-admin', 'translation', $translation );
		wp_enqueue_script( 'skaut-bazar-admin' );

		wp_nonce_field( 'skautbazar_meta_box', 'skautbazar_meta_box_nonce' );

		$skautbazar_inzerat = array();

		$skautbazar_inzerat     = get_post_meta( $post->ID, '_skautbazar_meta', true );
		$skautbazar_status      = get_post_meta( $post->ID, 'skautbazar_status', true );
		$skautbazar_description = get_post_meta( $post->ID, '_skautbazar_meta_description' );

		$statuses = array(
			1 => __( 'Active', 'skaut-bazar' ),
			2 => __( 'Reserved', 'skaut-bazar' ),
			3 => __( 'Archived', 'skaut-bazar' ),
		);

		$skautbazar_option = get_option( 'skautbazar_option' ); ?>

			<table class="skautbazar_table">
				<?php if ( get_the_title( $post ) ) : ?>
					<tr>
						<td><?php esc_html_e( 'Inzerat no.', 'skaut-bazar' ); ?></td>
						<td>
							<?php
								// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								echo get_the_title( $post );
							?>
						</td>
					</tr>
				<?php endif; ?>
				<tr>
					<td>*<?php esc_html_e( 'Inzerat title', 'skaut-bazar' ); ?>:</td>
					<td><input type="text" name="skautbazar_title" id="skautbazar_title" class="required" value="<?php echo isset( $skautbazar_inzerat['inzerat']['title'] ) ? esc_attr( $skautbazar_inzerat['inzerat']['title'] ) : ''; ?>"></td>
				</tr>
				<tr>
					<td class="skautbazar_table_header"><?php esc_html_e( 'Inzerat status', 'skaut-bazar' ); ?>:</td>
					<td>
						<select name="skautbazar_status" id="skautbazar_status">
							<?php foreach ( $statuses as $key => $option ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php echo isset( $skautbazar_status ) && $skautbazar_status == $key ? ' selected' : ''; ?>><?php echo esc_html( $option ); ?></option>
							<?php endforeach; ?>
						</select>					

					</td>
				</tr>
				<tr class="skautbazar_row_hidden" id="skautbazar_row_reservation_email">
					<td class="skautbazar_table_header"><?php esc_html_e( 'Reservation to e-mail', 'skaut-bazar' ); ?>:</td>
					<td><input type="email" name="skautbazar_buyer_email" id="skautbazar_buyer_email" value="<?php echo isset( $skautbazar_inzerat['inzerat']['buyer_email'] ) ? esc_attr( $skautbazar_inzerat['inzerat']['buyer_email'] ) : ''; ?>"></td>
				</tr>
				<tr class="skautbazar_row_hidden" id="skautbazar_row_reservation_message">
					<td class="skautbazar_table_header"><?php esc_html_e( 'Message from buyer', 'skaut-bazar' ); ?>:</td>
					<td><textarea name="skautbazar_buyer_message" id="skautbazar_buyer_message" disabled><?php echo isset( $skautbazar_inzerat['inzerat']['buyer_message'] ) ? esc_html( $skautbazar_inzerat['inzerat']['buyer_message'] ) : ''; ?></textarea></td>
				</tr>
				<tr>
					<td class="skautbazar_table_header"><?php echo ( $skautbazar_option['required_fields']['image'] == 1 ) ? '*' : ''; ?><?php esc_html_e( 'Picture', 'skaut-bazar' ); ?>:</td>
					<td>					
						<?php if ( isset( $skautbazar_inzerat['inzerat']['img'] ) ) : ?>
							<?php $img_id = $skautbazar_inzerat['inzerat']['img']; ?>
							<?php $img_attr = wp_get_attachment_image_src( $skautbazar_inzerat['inzerat']['img'] ); ?>
							<input type="hidden" value="<?php echo esc_attr( $skautbazar_inzerat['inzerat']['img'] ); ?>" class="regular-text process_custom_images <?php echo ( $skautbazar_option['required_fields']['image'] == 1 ) ? 'required' : ''; ?>" id="skautbazar_image_id" name="skautbazar_image_id" max="" min="1" step="1">
							<img style="width: 160px; height: auto" src="<?php echo esc_url( $img_attr[0] ); ?>" id="skautbazar_intro_image">
						<?php else : ?>
							<input type="hidden" value="" class="regular-text process_custom_images <?php echo ( $skautbazar_option['required_fields']['image'] == 1 ) ? 'required' : ''; ?>" id="skautbazar_image_id" name="skautbazar_image_id" max="" min="1" step="1">
							<img style="display: none; width: 160px; height: auto" src="#" id="skautbazar_intro_image">
						<?php endif; ?>
						<div class="skautbazar_buttons">
							<button class="skautbazar_intro_image_button button"><?php esc_html_e( 'Add picture', 'skaut-bazar' ); ?></button>
							<button class="skautbazar_intro_image_delete_button button"> <?php esc_html_e( 'Delete picture', 'skaut-bazar' ); ?> </button>
						</div>
					</td>
				</tr>

				<?php if ( $skautbazar_option['disable_author_lastname'] ) : ?>
					<tr>
						<td class="skautbazar_table_header"><?php echo ( $skautbazar_option['required_fields']['author_name'] == 1 ) ? '*' : ''; ?><?php esc_html_e( 'Name', 'skaut-bazar' ); ?>:</td>
						<td><input name="skautbazar_firstname_inzerat_autor" id="skautbazar_firstname_inzerat_autor" <?php echo ( $skautbazar_option['required_fields']['author_name'] == 1 ) ? 'class="required"' : ''; ?> value="<?php echo isset( $skautbazar_inzerat['inzerat']['name'] ) ? esc_attr( $skautbazar_inzerat['inzerat']['name'] ) : ( ( $skautbazar_option['default_author']['author_name'] ) ? esc_attr( $skautbazar_option['default_author']['author_name'] ) : esc_attr( wp_get_current_user()->display_name ) ); ?>" type="text" ></td>
					</tr>
				<?php else : ?>
					<tr>
						<td class="skautbazar_table_header"><?php echo ( $skautbazar_option['required_fields']['author_name'] == 1 ) ? '*' : ''; ?><?php esc_html_e( 'Name', 'skaut-bazar' ); ?>:</td>
						<td><input name="skautbazar_firstname_inzerat_autor" id="skautbazar_firstname_inzerat_autor" <?php echo ( $skautbazar_option['required_fields']['author_name'] == 1 ) ? 'class="required"' : ''; ?> value="<?php echo isset( $skautbazar_inzerat['inzerat']['name'] ) ? esc_attr( $skautbazar_inzerat['inzerat']['name'] ) : ( ( $skautbazar_option['default_author']['author_name'] ) ? esc_attr( $skautbazar_option['default_author']['author_name'] ) : esc_attr( wp_get_current_user()->user_firstname ) ); ?>" type="text" ></td>
					</tr>
					<tr>
						<td class="skautbazar_table_header"><?php echo ( $skautbazar_option['required_fields']['author_lastname'] == 1 ) ? '*' : ''; ?><?php esc_html_e( 'Last name', 'skaut-bazar' ); ?>:</td>
						<td><input name="skautbazar_lastname_inzerat_autor" id="skautbazar_lastname_inzerat_autor" <?php echo ( $skautbazar_option['required_fields']['author_lastname'] == 1 ) ? 'class="required"' : ''; ?> type="text" value="<?php echo isset( $skautbazar_inzerat['inzerat']['lastname'] ) ? esc_attr( $skautbazar_inzerat['inzerat']['lastname'] ) : ( ( $skautbazar_option['default_author']['author_lastname'] ) ? esc_attr( $skautbazar_option['default_author']['author_lastname'] ) : esc_attr( wp_get_current_user()->user_lastname ) ); ?>"></td>
					</tr>
				<?php endif; ?>

				<tr>
					<td class="skautbazar_table_header">*<?php esc_html_e( 'E-mail', 'skaut-bazar' ); ?>:</td>
					<td><input name="skautbazar_email_inzerat_autor" id="skautbazar_email_inzerat_autor" class="required" type="email" value="<?php echo isset( $skautbazar_inzerat['inzerat']['email'] ) ? esc_attr( $skautbazar_inzerat['inzerat']['email'] ) : ( ( $skautbazar_option['default_author']['author_email'] ) ? esc_attr( $skautbazar_option['default_author']['author_email'] ) : esc_attr( wp_get_current_user()->user_email ) ); ?>"></td>
				</tr>
				<tr>
					<td class="skautbazar_table_header"><?php echo ( $skautbazar_option['required_fields']['phone'] == 1 ) ? '*' : ''; ?><?php esc_html_e( 'Telephone', 'skaut-bazar' ); ?>:</td>
					<td><input name="skautbazar_telefon_inzerat_autor" id="skautbazar_telefon_inzerat_autor" <?php echo ( $skautbazar_option['required_fields']['phone'] == 1 ) ? 'class="required"' : ''; ?> type="tel" value="<?php echo isset( $skautbazar_inzerat['inzerat']['telefon'] ) ? esc_attr( $skautbazar_inzerat['inzerat']['telefon'] ) : esc_attr( $skautbazar_option['default_author']['author_tel'] ); ?>"></td>
				</tr>

				<?php

					$skautbazar_type = array(
						''         => __( 'Sell item as', 'skaut-bazar' ),
						'price'    => __( 'Price', 'skaut-bazar' ),
						'exchange' => __( 'Exchange', 'skaut-bazar' ),
						'gift'     => __( 'Gift', 'skaut-bazar' ),
					);
					?>

				<tr>
					<td class="skautbazar_table_header">*<?php esc_html_e( 'Sell as', 'skaut-bazar' ); ?>:</td>
					<td>
						<select name="skautbazar_type_author" id="skautbazar_type_author" class="skautbazar_type required">
							<?php foreach ( $skautbazar_type as $key => $typ ) : ?>
								<option <?php echo isset( $skautbazar_inzerat['inzerat']['type'] ) && $skautbazar_inzerat['inzerat']['type'] == $key ? 'selected="selected"' : ''; ?>  value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $typ ); ?></option>
							<?php endforeach; ?>

						</select>
					</td>				
				</tr>
				<tr class="skautbazar_row_hidden" id="skautbazar_row_price">
					<td class="skautbazar_table_header">*<?php esc_html_e( 'Price for item', 'skaut-bazar' ); ?>:</td>
					<td>
						<?php
						if ( isset( $skautbazar_option['default_currency_position'] ) && $skautbazar_option['default_currency_position'] == 'left' ) {
							echo esc_html( $skautbazar_option['default_currency'] ) . ' ';}
						?>
						<input type="text" name="skautbazar_price" id="skautbazar_price" value="<?php echo isset( $skautbazar_inzerat['inzerat']['price'] ) ? esc_attr( $skautbazar_inzerat['inzerat']['price'] ) : ''; ?>">
						<?php
						if ( isset( $skautbazar_option['default_currency_position'] ) && $skautbazar_option['default_currency_position'] == 'right' ) {
							echo ' ' . esc_html( $skautbazar_option['default_currency'] );}
						?>
					</td>
				</tr>
				<tr class="skautbazar_row_hidden" id="skautbazar_row_exchange">
					<td class="skautbazar_table_header">*<?php esc_html_e( 'Description for exchange', 'skaut-bazar' ); ?>:</td>
					<td><input type="text" name="skautbazar_exchange" id="skautbazar_exchange" value="<?php echo isset( $skautbazar_inzerat['inzerat']['exchange'] ) ? esc_attr( $skautbazar_inzerat['inzerat']['exchange'] ) : ''; ?>"></td>
				</tr>
				<tr>
					<td class="skautbazar_table_header"><?php echo ( $skautbazar_option['required_fields']['amount'] == 1 ) ? '*' : ''; ?><?php esc_html_e( 'Amount', 'skaut-bazar' ); ?>:</td>
					<td><input name="skautbazar_mnozstvi_inzerat_autor" id="skautbazar_mnozstvi_inzerat_autor" <?php echo ( $skautbazar_option['required_fields']['amount'] == 1 ) ? 'class="required"' : ''; ?> type="tel" value="<?php echo isset( $skautbazar_inzerat['inzerat']['amount'] ) ? esc_attr( $skautbazar_inzerat['inzerat']['amount'] ) : '1'; ?>"></td>
				</tr>
				<tr>
					<td class="skautbazar_table_header"><?php echo ( $skautbazar_option['required_fields']['size'] == 1 ) ? '*' : ''; ?><?php esc_html_e( 'Size', 'skaut-bazar' ); ?>:</td>
					<td><input name="skautbazar_velikost_inzerat_autor" id="skautbazar_velikost_inzerat_autor" <?php echo ( $skautbazar_option['required_fields']['size'] == 1 ) ? 'class="required"' : ''; ?> type="tel" value="<?php echo isset( $skautbazar_inzerat['inzerat']['size'] ) ? esc_attr( $skautbazar_inzerat['inzerat']['size'] ) : ''; ?>"></td>
				</tr>
				<tr>
					<td class="skautbazar_table_header"><?php esc_html_e( 'Description', 'skaut-bazar' ); ?>:</td>
					<td>
						<?php
						if ( isset( $skautbazar_description[0] ) ) {
							wp_editor( $skautbazar_description[0], '_skautbazar_meta_description' );
						} else {
							wp_editor( '', '_skautbazar_meta_description' );
						}
						?>
					</td>
				</tr>
			</table>
			
		<?php
	}


	// Options page
	public function skautbazar_option_page() {
		add_options_page( 'Skaut bazar', 'Skaut bazar', 'manage_options', 'skautbazar_option', array( $this, 'skautbazar_option_callback' ) );
	}


	// Skautbazar settings
	public function skautbazar_option_callback() {
		$skautbazar_option = get_option( 'skautbazar_option' );

		if ( isset( $_POST['action'] ) && $_POST['action'] == 'save' ) {
			if ( ! isset( $_POST['skautbazar_options_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['skautbazar_options_nonce'] ) ), 'skautbazar_options' ) ) {
				return;
			}
			$skautbazar_option['default_author']['author_name']     = isset( $_POST['author_name'] ) ? sanitize_text_field( wp_unslash( $_POST['author_name'] ) ) : '';
			$skautbazar_option['default_author']['author_lastname'] = isset( $_POST['author_lastname'] ) ? sanitize_text_field( wp_unslash( $_POST['author_lastname'] ) ) : '';
			$skautbazar_option['default_author']['author_email']    = isset( $_POST['author_email'] ) ? sanitize_email( wp_unslash( $_POST['author_email'] ) ) : '';
			$skautbazar_option['default_author']['author_tel']      = isset( $_POST['author_tel'] ) ? sanitize_text_field( wp_unslash( $_POST['author_tel'] ) ) : '';
			$skautbazar_option['default_currency']                  = isset( $_POST['currency'] ) ? sanitize_text_field( wp_unslash( $_POST['currency'] ) ) : '';
			$skautbazar_option['default_currency_position']         = isset( $_POST['default_currency_position'] ) ? sanitize_text_field( wp_unslash( $_POST['default_currency_position'] ) ) : '';
			$skautbazar_option['poradove_cislo']                    = isset( $_POST['poradove_cislo'] ) ? intval( $_POST['poradove_cislo'] ) : 0;

			$skautbazar_option['allow_buyer_message']     = isset( $_POST['allow_buyer_message'] ) ? sanitize_text_field( wp_unslash( $_POST['allow_buyer_message'] ) ) : '';
			$skautbazar_option['disable_author_lastname'] = isset( $_POST['disable_author_lastname'] ) ? sanitize_text_field( wp_unslash( $_POST['disable_author_lastname'] ) ) : '';
			$skautbazar_option['hide_inzerat_number']     = isset( $_POST['hide_inzerat_number'] ) ? sanitize_text_field( wp_unslash( $_POST['hide_inzerat_number'] ) ) : '';

			$skautbazar_option['required_fields']['author_name']     = isset( $_POST['req_author_name'] ) ? sanitize_text_field( wp_unslash( $_POST['req_author_name'] ) ) : '';
			$skautbazar_option['required_fields']['author_lastname'] = isset( $_POST['req_author_lastname'] ) ? sanitize_text_field( wp_unslash( $_POST['req_author_lastname'] ) ) : '';
			$skautbazar_option['required_fields']['image']           = isset( $_POST['req_image'] ) ? sanitize_text_field( wp_unslash( $_POST['req_image'] ) ) : '';
			$skautbazar_option['required_fields']['phone']           = isset( $_POST['req_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['req_phone'] ) ) : '';
			$skautbazar_option['required_fields']['amount']          = isset( $_POST['req_amount'] ) ? sanitize_text_field( wp_unslash( $_POST['req_amount'] ) ) : '';
			$skautbazar_option['required_fields']['size']            = isset( $_POST['req_size'] ) ? sanitize_text_field( wp_unslash( $_POST['req_size'] ) ) : '';

			update_option( 'skautbazar_option', $skautbazar_option );
		}

		?>

		<div class="wrap">
			<h2><?php esc_html_e( 'Skaut bazar settings', 'skaut-bazar' ); ?></h2>
			<form method="post" action="<?php echo isset( $_SERVER['PHP_SELF'] ) ? esc_url( sanitize_file_name( wp_unslash( $_SERVER['PHP_SELF'] ) ) ) : ''; ?>?page=skautbazar_option">
				<?php
					wp_nonce_field( 'skautbazar_options', 'skautbazar_options_nonce' );
				?>
				<h3> <?php esc_html_e( 'Plugin settings', 'skaut-bazar' ); ?> </h3>
				<table class="widefat fixed" cellspacing="0">
					<tr>
						<td style="width: 300px;"><?php esc_html_e( 'Allow message from buyer to the seller', 'skaut-bazar' ); ?></td>
						<td><input type='checkbox' id='allow_buyer_message' name='allow_buyer_message' value='1' <?php checked( 1 == $skautbazar_option['allow_buyer_message'] ); ?> /></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Disable author lastname field', 'skaut-bazar' ); ?></td>
						<td><input type='checkbox' id='disable_author_lastname' name='disable_author_lastname' value='1' <?php checked( 1 == $skautbazar_option['disable_author_lastname'] ); ?> /></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Hide inzerat number', 'skaut-bazar' ); ?></td>
						<td><input type='checkbox' id='hide_inzerat_number' name='hide_inzerat_number' value='1' <?php checked( 1 == $skautbazar_option['hide_inzerat_number'] ); ?> /></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Last inzerat no.', 'skaut-bazar' ); ?></td>
						<td><input type="text" id="poradove_cislo" name="poradove_cislo" value="<?php echo esc_attr( $skautbazar_option['poradove_cislo'] ); ?>"></td>
					</tr>
				</table>

				<h3> <?php esc_html_e( 'Required fields', 'skaut-bazar' ); ?> </h3>
				<table class="widefat fixed" cellspacing="0">
					<tr>
						<td style="width: 200px;"><?php esc_html_e( 'Name', 'skaut-bazar' ); ?></td>
						<td><input type='checkbox' id='req_author_name' name='req_author_name' value='1' <?php checked( 1 == $skautbazar_option['required_fields']['author_name'] ); ?> /></td>
					</tr>
					<?php if ( ! $skautbazar_option['disable_author_lastname'] ) : ?>
						<tr>
							<td><?php esc_html_e( 'Last name', 'skaut-bazar' ); ?></td>
							<td><input type='checkbox' id='req_author_lastname' name='req_author_lastname' value='1' <?php checked( 1 == $skautbazar_option['required_fields']['author_lastname'] ); ?> /></td>
						</tr>
					<?php endif; ?>
					<tr>
						<td><?php esc_html_e( 'Picture', 'skaut-bazar' ); ?></td>
						<td><input type='checkbox' id='req_image' name='req_image' value='1' <?php checked( 1 == $skautbazar_option['required_fields']['image'] ); ?> /></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Telephone', 'skaut-bazar' ); ?></td>
						<td><input type='checkbox' id='req_phone' name='req_phone' value='1' <?php checked( 1 == $skautbazar_option['required_fields']['phone'] ); ?> /></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Amount', 'skaut-bazar' ); ?></td>
						<td><input type='checkbox' id='req_amount' name='req_amount' value='1' <?php checked( 1 == $skautbazar_option['required_fields']['amount'] ); ?> /></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Size', 'skaut-bazar' ); ?></td>
						<td><input type='checkbox' id='req_size' name='req_size' value='1' <?php checked( 1 == $skautbazar_option['required_fields']['size'] ); ?> /></td>
					</tr>
				</table>

				<h3> <?php esc_html_e( 'Default values', 'skaut-bazar' ); ?> </h3>
				<table class="widefat fixed" cellspacing="0">
					<tr>
						<td style="width: 200px;"><?php esc_html_e( 'Default name', 'skaut-bazar' ); ?></td>
						<td><input type="text" id="author_name" name="author_name" value="<?php echo esc_attr( $skautbazar_option['default_author']['author_name'] ); ?>"></td>
					</tr>
					<?php if ( ! $skautbazar_option['disable_author_lastname'] ) : ?>
						<tr>
							<td><?php esc_html_e( 'Default last name', 'skaut-bazar' ); ?></td>
							<td><input type="text" id="author_lastname" name="author_lastname" value="<?php echo esc_attr( $skautbazar_option['default_author']['author_lastname'] ); ?>"></td>
						</tr>
					<?php endif; ?>
					<tr>
						<td><?php esc_html_e( 'Default e-mail', 'skaut-bazar' ); ?></td>
						<td><input type="text" id="author_email" name="author_email" value="<?php echo esc_attr( $skautbazar_option['default_author']['author_email'] ); ?>"></td>
					</tr>
					<tr>
						<td><?php esc_html_e( 'Default telephone', 'skaut-bazar' ); ?></td>
						<td><input type="text" id="author_tel" name="author_tel" value="<?php echo esc_attr( $skautbazar_option['default_author']['author_tel'] ); ?>"></td>
					</tr>
				</table>

				<h3> <?php esc_html_e( 'Currency', 'skaut-bazar' ); ?> </h3>
				<table class="widefat fixed" cellspacing="0">
					<tr>
						<td style="width: 200px"><?php esc_html_e( 'Show currency', 'skaut-bazar' ); ?></td>
						<td>
							<select name="default_currency_position" id="default_currency_position">
								<option value="left" <?php echo $skautbazar_option['default_currency_position'] == 'left' ? 'selected' : ''; ?> ><?php esc_html_e( 'Left', 'skaut-bazar' ); ?></option>
								<option value="right" <?php echo $skautbazar_option['default_currency_position'] == 'right' ? 'selected' : ''; ?> ><?php esc_html_e( 'Right', 'skaut-bazar' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td style="width: 200px"><?php esc_html_e( 'Currency', 'skaut-bazar' ); ?></td>
						<td><input type="text" id="currency" name="currency" value="<?php echo isset( $skautbazar_option['default_currency'] ) ? esc_attr( $skautbazar_option['default_currency'] ) : ''; ?>"></td>
					</tr>
				</table>

				<p><input type="submit" class="button-primary" name="Submit" value="<?php esc_attr_e( 'Save', 'skaut-bazar' ); ?>" /></p>
				<input type="hidden" name="action" id="action" value="save">
			</form>
		</div>

		<?php
	}


	// Short code
	function skautbazar_shortcode( $atts ) {
		$args = shortcode_atts(
			array(
				'skautbazar_pocetprispevku' => 10,
			),
			$atts
		);

		$skautbazar_pocetprispevku = (int) $args['skautbazar_pocetprispevku'];

		return $this->skautbazar_shortcode_output( $skautbazar_pocetprispevku );
	}


	function skautbazar_shortcode_output( $posts_number ) {
		enqueue_style( 'skaut-bazar', 'includes/css/style.skautbazar.css' );

		enqueue_script( 'skaut-bazar', 'includes/js/jquery.skautbazar.js' );

		$ajax_nonce = wp_create_nonce( 'skautbazar-email-registering' );

		$translation = array(
			'ajax_url'                 => admin_url( 'admin-ajax.php' ),
			'ajax_nonce'               => $ajax_nonce,
			'email_not_valid'          => __( 'E-mail address is not valid', 'skaut-bazar' ),
			'reserved'                 => __( 'Reserved', 'skaut-bazar' ),
			'email_reserved'           => __( 'Item was reserved for your e-mail address', 'skaut-bazar' ),
			'error_during_reservation' => __( 'Error during reservation, please try it later', 'skaut-bazar' ),
		);

		wp_localize_script( 'skaut-bazar', 'ajax_object', $translation );

		$skautbazar_paged = isset( $_GET['skautbazar_paged'] ) ? intval( $_GET['skautbazar_paged'] ) : 1;

		$args = array(
			'post_type'     => 'skautbazar',
			'orderby'       => 'date',
			'order'         => 'DESC',
			'post_per_page' => -1,
			'meta_query'    => array(
				'relation' => 'OR',
				array(
					'key'   => 'skautbazar_status',
					'value' => 1,
				),
				array(
					'key'   => 'skautbazar_status',
					'value' => 2,
				),
			),
			'paged'         => $skautbazar_paged,
		);

		if ( isset( $_GET['skautbazar-cat'] ) ) {
			$args['cat'] = sanitize_text_field( wp_unslash( $_GET['skautbazar-cat'] ) );
			// echo '<h2>'. _e( 'Category', 'skaut-bazar') .': </h2>';
		}

		if ( isset( $_GET['skautbazar-tag'] ) ) {
			$args['tag'] = sanitize_text_field( wp_unslash( $_GET['skautbazar-tag'] ) );
			// echo '<h2>'. _e( 'Tag', 'skaut-bazar') .': </h2>';
		}

		global $post;
		$current_id = $post->ID;

		$query  = new \WP_Query( $args );
		$output = '';

		$total = $query->max_num_pages;

		while ( $query->have_posts() ) :
			$query->the_post();

			$postid                 = get_the_ID();
			$skautbazar_inzerat     = array();
			$skautbazar_inzerat     = get_post_meta( $postid, '_skautbazar_meta', true );
			$skautbazar_status      = get_post_meta( $postid, 'skautbazar_status', true );
			$skautbazar_description = get_post_meta( $postid, '_skautbazar_meta_description', true );
			$skautbazar_option      = get_option( 'skautbazar_option' );

			// price for item
			switch ( $skautbazar_inzerat['inzerat']['type'] ) {
				case 'price':
					$typ = __( 'Price', 'skaut-bazar' ) . ': ' . $skautbazar_inzerat['inzerat']['price'];
					break;

				case 'exchange':
					$typ = __( 'Exchange for', 'skaut-bazar' ) . ': ' . $skautbazar_inzerat['inzerat']['exchange'];
					break;

				case 'gift':
					$typ = __( 'Gift', 'skaut-bazar' );
					break;

				default:
					$typ = '';
					break;
			}

			if ( isset( $skautbazar_inzerat['inzerat']['img'] ) ) {
				$img_attr = wp_get_attachment_image_src( $skautbazar_inzerat['inzerat']['img'], 'thumbnail' );
			}

			$category = get_the_category( $postid );
			$posttags = get_the_tags( $postid );

			$customClass = '';

			$output .= '<div class="skautbazar_post">';

			if ( isset( $skautbazar_inzerat['inzerat']['img'] ) && $skautbazar_inzerat['inzerat']['img'] != '' ) :
				$hasImage    = true;
				$output     .= '<div class="skautbazar_post_img">';
					$output .= '<p class="skautbazar_prev_img">' . wp_get_attachment_link( $skautbazar_inzerat['inzerat']['img'], 'medium', false, true ) . '</p>';
				$output     .= '</div>';
				else :
					$hasImage    = false;
					$customClass = ' skautbazar_post_box_full';
				endif;

				$output     .= '<div class="skautbazar_post_box' . $customClass . '">';
					$output .= '<h2 class="skautbazar_post_heading">' . $skautbazar_inzerat['inzerat']['title'] . '</h2>';
					$output .= '<div class="skautbazar_post_info">';
				if ( ! $skautbazar_option['hide_inzerat_number'] ) {
					$output .= '<p>' . __( 'Inzerat no. ', 'skaut-bazar' ) . ' ' . get_the_title() . '</p>';
				}

						$output .= '<p><strong>';
						$output .= ( $skautbazar_option['default_currency_position'] == 'left' ) && $skautbazar_inzerat['inzerat']['type'] == 'price' ? $skautbazar_option['default_currency'] . ' ' : '';
						$output .= $typ;
						$output .= ( $skautbazar_option['default_currency_position'] == 'right' ) && $skautbazar_inzerat['inzerat']['type'] == 'price' ? ' ' . $skautbazar_option['default_currency'] : '';
						$output .= '</strong></p>';

				if ( isset( $skautbazar_inzerat['inzerat']['size'] ) && $skautbazar_inzerat['inzerat']['size'] != '' ) {
					$output .= '<p>' . __( 'Size', 'skaut-bazar' ) . ': ' . $skautbazar_inzerat['inzerat']['size'] . '</p>';
				}
				if ( isset( $skautbazar_inzerat['inzerat']['amount'] ) && $skautbazar_inzerat['inzerat']['amount'] != '' && $skautbazar_inzerat['inzerat']['amount'] != 1 ) {
					$output .= '<p>' . __( 'Amount', 'skaut-bazar' ) . ': ' . $skautbazar_inzerat['inzerat']['amount'] . '</p>';
				}

						$output .= '<p>' . __( 'Contact', 'skaut-bazar' ) . ': ' . $skautbazar_inzerat['inzerat']['name'] . ' ' . $skautbazar_inzerat['inzerat']['lastname'] . '</p>';
						$output .= '<p>' . __( 'E-mail', 'skaut-bazar' ) . ': ' . $skautbazar_inzerat['inzerat']['email'] . '</p>';

				if ( isset( $skautbazar_inzerat['inzerat']['telefon'] ) && $skautbazar_inzerat['inzerat']['telefon'] != '' ) {
					$output .= '<p>' . __( 'Telephone', 'skaut-bazar' ) . ': ' . $skautbazar_inzerat['inzerat']['telefon'] . '</p>';
				}
					$output .= '</div>';
				$output     .= '</div>';

					$output .= '<div class="skautbazar_clear"></div>';
					$output .= apply_filters( 'the_content', $skautbazar_description );
					$output .= '<div class="skautbazar_clear"></div>';

					$output .= '<div class="skautbazar_post_category">';
				if ( $category ) {
					$end     = end( $category );
					$output .= '<p class="skautbazar_infotext">' . __( 'Category', 'skaut-bazar' ) . ': ';
					foreach ( $category as $c ) {
						if ( $end->cat_name == $c->cat_name ) {
							// $output .= '<a href="http://'. $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] . '?skautbazar-cat='. $c->cat_ID .'&skautbazar-tag=">' . $c->cat_name . '</a>';
							$output .= '<a href="' . get_permalink( $current_id ) . '?skautbazar-cat=' . $c->cat_ID . '">' . $c->cat_name . '</a>';
						} else {
							// $output .= '<a href="http://'. $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] . '?skautbazar-cat='. $c->cat_ID .'&skautbazar-tag=">' . $c->cat_name . '</a>,';
							$output .= '<a href="' . get_permalink( $current_id ) . '?skautbazar-cat=' . $c->cat_ID . '">' . $c->cat_name . '</a>, ';
						}
					}
							$output .= '</p>';
				}

				if ( $posttags ) {
					$end     = end( $posttags );
					$output .= '<p class="skautbazar_infotext">' . __( 'Tags', 'skaut-bazar' ) . ': ';
					foreach ( $posttags as $tag ) {
						if ( $end->name == $tag->name ) {
							// $output .= '<a href="http://'. $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] .'?skautbazar-tag='. $tag->slug .'&skautbazar-cat=">' . $tag->name . '</a>';
							$output .= '<a href="' . get_permalink( $current_id ) . '?skautbazar-tag=' . $tag->slug . '">' . $tag->name . '</a>';
						} else {
							// $output .= '<a href="http://'. $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] .'?skautbazar-tag='. $tag->slug .'&skautbazar-cat=">' . $tag->name . '</a>,';
							$output .= '<a href="' . get_permalink( $current_id ) . '?skautbazar-tag=' . $tag->slug . '">' . $tag->name . '</a>, ';
						}
					}
							$output .= '</p>';
				}

				if ( $skautbazar_status == 1 ) {
					$output .= '<p class="skautbazar_rezervace skautbazar_rezervace' . $postid . '"><a href="' . $postid . '">' . __( 'Interested', 'skaut-bazar' ) . '</a></p>';
				} else {
					$output .= '<p class="skautbazar_rezervace"><span class="skautbazar_rezervovano">' . __( 'Reserved', 'skaut-bazar' ) . '</span></p>';
				}

					$output .= '</div>';
				$output     .= '<div class="skautbazar_clear"></div>';
				$output     .= '</div>';
		endwhile;

		$output .= wp_reset_query();

		$output .= $this->skautbazar_pagination( $total, $skautbazar_paged );

		$output         .= '<div class="skautbazar_emailbox_bg">';
			$output     .= '<div class="skautbazar_emailbox">';
				$output .= '<p>' . __( 'Enter your e-mail address to complete reservation', 'skaut-bazar' ) . '</p>';
				$output .= '<p><input type="email" id="skautbazar_email_customer" name="skautbazar_email_customer" value="' . wp_get_current_user()->user_email . '"></p>';
				$output .= '<input type="hidden" id="skautbazar_item_id" name="skautbazar_item_id" value="">';
		if ( $skautbazar_option['allow_buyer_message'] ) {
			$output .= '<p>' . __( 'Message to seller', 'skaut-bazar' ) . '</p>';
			$output .= '<p><textarea id="skautbazar_message_customer" name="skautbazar_message_customer"></textarea></p>';
		}
				$output .= '<p class="skautbazar_email_submit_p"><button class="skautbazar_email_submit">' . __( 'Make reservation', 'skaut-bazar' ) . '</button><a class="skautbazar_email_close" href="#">' . __( 'Close', 'skaut-bazar' ) . '</a></p>';
				$output .= '<p class="skautbazar_message"></p>';
			$output     .= '</div>';
		$output         .= '</div>';

		return $output;
	}


	function skautbazar_rezervace() {
		global $wpdb;

		check_ajax_referer( 'skautbazar-email-registering' );

		if ( ! isset( $_POST['bazar_item_email'] ) || ! is_email( wp_unslash( $_POST['bazar_item_email'] ) ) ) {
			echo false;
			wp_die();
		}

		$id = isset( $_POST['bazar_item_id'] ) ? intval( $_POST['bazar_item_id'] ) : 0;

		if ( ! isset( $id ) ) {
			echo false;
			wp_die();
		}

		$skautbazar_option = get_option( 'skautbazar_option' );

		$skautbazar_inzerat = get_post_meta( $id, '_skautbazar_meta', true );
		$skautbazar_status  = get_post_meta( $id, 'skautbazar_status', true );

		$skautbazar_status                            = 2;
		$skautbazar_inzerat['inzerat']['buyer_email'] = isset( $_POST['bazar_item_email'] ) ? sanitize_email( wp_unslash( $_POST['bazar_item_email'] ) ) : '';

		if ( $skautbazar_option['allow_buyer_message'] ) {
			$skautbazar_inzerat['inzerat']['buyer_message'] = isset( $_POST['bazar_item_message'] ) ? sanitize_text_field( wp_unslash( $_POST['bazar_item_message'] ) ) : '';
		}

		update_post_meta( $id, '_skautbazar_meta', $skautbazar_inzerat );
		update_post_meta( $id, 'skautbazar_status', $skautbazar_status );

		$rezervace_no = get_the_title( $id );

		$email_recipients = array(
			$skautbazar_inzerat['inzerat']['email'],
		);
		$subject          = __( 'New item reservation', 'skaut-bazar' );
		$headers          = 'From: <' . $skautbazar_inzerat['inzerat']['buyer_email'] . '>';

		$message  = '';
		$message .= __( 'Hy', 'skaut-bazar' ) . "\n";
		$message .= __( 'Someone showed interest for item no.', 'skaut-bazar' ) . ': ' . get_the_title( $id ) . ' - ' . $skautbazar_inzerat['inzerat']['title'] . ' ';
		$message .= __( 'in your bazar', 'skaut-bazar' ) . "\n";
		$message .= __( 'Reserved to e-mail', 'skaut-bazar' ) . ': ' . $skautbazar_inzerat['inzerat']['buyer_email'] . "\n\n";
		if ( $skautbazar_option['allow_buyer_message'] ) {
			$message .= __( 'Message from buyer', 'skaut-bazar' ) . ': ' . $skautbazar_inzerat['inzerat']['buyer_message'] . "\n\n";
		}
		$message .= __( 'Please reply him as soon as possible.', 'skaut-bazar' ) . "\n";

		wp_mail( $email_recipients, $subject, $message, $headers );

		echo true;
		wp_die();
	}


	function skautbazar_send_email_notification( $post_id ) {
		$skautbazar_inzerat = get_post_meta( $post_id, '_skautbazar_meta', true );
		$headers            = 'From: <' . $skautbazar_inzerat['inzerat']['buyer_email'] . '>';

		$message = $skautbazar_inzerat['inzerat']['title'];

		$email_recipients = array(
			$skautbazar_inzerat['inzerat']['email'],
			$skautbazar_inzerat['inzerat']['buyer_email'],
		);

		wp_mail( $email_recipients, 'nova rez', $message, $headers );
	}


	function skautbazar_pagination( $total, $skautbazar_paged ) {
		if ( ! $skautbazar_paged > 1 ) {
			return;
		}

		$o = '';

		if ( $total > 1 ) {
			$o .= '<ul class="skautbazar_pagination">';

			for ( $i = 1; $i <= $total; $i++ ) {
				if ( $skautbazar_paged == $i ) {
					$o .= '<li><span>' . $i . '</span></li>';
				} else {
					$o .= '<li><a href="' . get_permalink() . '?skautbazar_paged=' . $i . '">' . $i . '</a></li>';
				}
			}

			$o .= '</ul>';
		}

		return $o;
	}
}
$skautbazar = new Bazar();
