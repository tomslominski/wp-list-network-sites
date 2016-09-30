<?php

require_once( 'class-list-network-sites.php' );

if( !function_exists( 'ls_load_styles' ) ) {

	/**
	 * Load CSS styles which the theme depends on
	 *
	 * @since 1.0
	 */
	function ls_load_styles() {
		wp_enqueue_style( 'ls_style', get_stylesheet_uri() );
	}

	add_action( 'wp_enqueue_scripts', 'ls_load_styles' );

}

if( !function_exists( 'ls_load_scripts' ) ) {

	/**
	 * Load JS scripts which the theme depends on
	 *
	 * @since 1.0
	 */
	function ls_load_scripts() {

		wp_register_script( 'lns_utils', get_template_directory_uri() . '/assets/js/utils.js', array( 'jquery' ), false, true );

		wp_register_script( 'lns_app', get_template_directory_uri() . '/assets/js/app.js', array( 'jquery', 'lns_utils' ), false, true );
		$data = array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'siteUrl' => trailingslashit( site_url() )
		);
		wp_localize_script( 'lns_app', 'lnsi18n', $data );
		wp_enqueue_script( 'lns_app' );

	}

	add_action( 'wp_enqueue_scripts', 'ls_load_scripts' );

}

if( !function_exists( 'ls_init' ) ) {

	/**
	 * Initialise other parts of the theme - textdomain etc.
	 *
	 * @since 1.0
	 */
	function ls_init() {

		load_theme_textdomain( 'list-network-sites', get_template_directory() . '/languages' );

	}

	add_action( 'after_setup_theme', 'ls_init' );

}

if( !function_exists( 'ls_customizer' ) ) {

	/**
	 * Register settings to be displayed in the WP customiser
	 *
	 * @since 1.1
	 * @param \WP_Customize_Manager $wp_customize Instance of WP Customizer
	 */
	function ls_customizer( $wp_customize ) {

		// Add section for settings
		$wp_customize->add_section( 'ls_main' , array(
		    'title'      => __( 'List Network Sites', 'list-network-sites' ),
		) );

		// Add settings
		$wp_customize->add_setting( 'ls_sorting_method' , array(
			'default'     => 'alphabetical'
		) );

		$wp_customize->add_setting( 'ls_sorting_order' , array(
			'default'     => 'ascending'
		) );

		$wp_customize->add_setting( 'ls_sites_per_page' , array(
			'default'     => 10
		) );

		// Add controls
		$wp_customize->add_control( 'ls_sorting_method_control',
			array(
				'label'    => __( 'Sorting method', 'list-network-sites' ),
				'section'  => 'ls_main',
				'settings' => 'ls_sorting_method',
				'type'     => 'select',
				'choices'  => array(
					'alphabetical'  => __( 'Alphabetical', 'list-network-sites' ),
					'date_registered' => __( 'Date registered', 'list-network-sites' ),
					'date_updated' => __( 'Date updated', 'list-network-sites' ),
					'post_count' => __( 'Post count', 'list-network-sites' ),
					'id' => __( 'ID', 'list-network-sites' ),
				),
			)
		);

		$wp_customize->add_control( 'ls_sorting_order_control',
			array(
				'label'    => __( 'Sorting order', 'list-network-sites' ),
				'section'  => 'ls_main',
				'settings' => 'ls_sorting_order',
				'type'     => 'radio',
				'choices'  => array(
					'ascending'  => __( 'Ascending', 'list-network-sites' ),
					'descending' => __( 'Descending', 'list-network-sites' ),
				),
			)
		);

		$wp_customize->add_control( 'ls_sites_per_page_control',
			array(
				'label'    => __( 'Sites per page', 'list-network-sites' ),
				'section'  => 'ls_main',
				'settings' => 'ls_sites_per_page',
				'type'     => 'number',
			)
		);

	}

	add_action( 'customize_register', 'ls_customizer' );

}

if( !function_exists( 'lns_filter_query_vars' ) ) {

	/**
	 * Filter query variables to enable pagination.
	 *
	 * @param array $vars Array of existing query variables.
	 * @since 1.1
	 */
	function lns_filter_query_vars( $vars ) {
		$vars[] = 'sites_page';
		$vars[] = 'sites_sorting_method';
		$vars[] = 'sites_sorting_order';
		$vars[] = 'sites_search';
		return $vars;
	}

	add_filter( 'query_vars', 'lns_filter_query_vars' );

}

if( !function_exists( 'lns_populate_query_vars' ) ) {

	/**
	 * Set up pretty permalinks for the sites pagination.
	 *
	 * @since 1.1
	 */
	function lns_populate_query_vars() {
		add_rewrite_rule( 'sites\/(alphabetical|date_registered|date_updated|post_count|id)\/(ascending|descending)\/([0-9]{1,})\/([^\/]+)', 'index.php?&sites_sorting_method=$matches[1]&sites_sorting_order=$matches[2]&sites_page=$matches[3]&sites_search=$matches[4]', 'top' );
		add_rewrite_rule( 'sites\/(alphabetical|date_registered|date_updated|post_count|id)\/(ascending|descending)\/([0-9]{1,})', 'index.php?&sites_sorting_method=$matches[1]&sites_sorting_order=$matches[2]&sites_page=$matches[3]', 'top' );
		add_rewrite_rule( 'sites\/(alphabetical|date_registered|date_updated|post_count|id)\/(ascending|descending)', 'index.php?&sites_sorting_method=$matches[1]&sites_sorting_order=$matches[2]', 'top' );
		add_rewrite_rule( 'sites\/(alphabetical|date_registered|date_updated|post_count|id)', 'index.php?&sites_sorting_method=$matches[1]', 'top' );
	}

	add_filter( 'init', 'lns_populate_query_vars' );

}

add_action( 'wp_ajax_lns_get_sites', 'js_get_sites' );
add_action( 'wp_ajax_nopriv_lns_get_sites', 'js_get_sites' );

function js_get_sites() {

	$site_query = new List_Network_Sites( array(
		'sorting' => !empty( $_POST['sorting'] ) ? $_POST['sorting'] : 'id',
		'order' => !empty( $_POST['order'] ) ? $_POST['order'] : 'ascending',
		'page' => !empty( $_POST['page'] ) ? $_POST['page'] : 1,
		'search' => $_POST['search_value'],
	) );

	echo $site_query->get_html();

	wp_die();

}

?>
