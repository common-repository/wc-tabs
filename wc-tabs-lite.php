<?php
/**
 * Plugin Name:  Tabs for WooCommerce
 * Plugin URI: https://www.wpbranch.com/plugins/wc-tabs
 * Description: Extends WooCommerce to add a custom product view page tab
 * Author: WPBranch
 * Author URI: https://wpbranch.com/
 * Version: 1.0.0
 * Tested up to: 6.0.2
 * Text Domain: wootabs
 * Domain Path: /languages/
 *
 * Copyright: (c) 2012-2022, WPBranch.
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @author      WPBranch
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 *
 * WC requires at least: 3.9.4
 * WC tested up to: 6.7.0
 */

defined( 'ABSPATH' ) or exit;


/**
 * Main plugin class WooTabsLite.
 *
 * @since 1.0.0
 */
final class WooTabsLite {


	/** @var bool|array tab data */
	private $tab_data = false;

	/** @var WooTabsLite single instance of this plugin */
	protected static $instance;


	/**
	 * Gets things started by adding an action to initialize this plugin once
	 * WooCommerce is known to be active and initialized
	 */
	public function __construct() {
		// Defined Constants
		$this->constants();
		
		// Installation
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			$this->install();
		}

		add_action( 'init',             array( $this, 'load_translation' ) );
		add_action( 'woocommerce_init', array( $this, 'init' ) );
	}


	/**
	 * Cloning instances is forbidden due to singleton pattern.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		/* translators: Placeholders: %s - plugin name */
		_doing_it_wrong( __FUNCTION__, sprintf( esc_html__( 'You cannot clone instances of %s.', 'wootabs' ), 'WooTabs Custom Product Tabs Lite for WooCommerce' ), '1.0.0' );
	}


	/**
	 * Unserializing instances is forbidden due to singleton pattern.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		/* translators: Placeholders: %s - plugin name */
		_doing_it_wrong( __FUNCTION__, sprintf( esc_html__( 'You cannot unserialize instances of %s.', 'wootabs' ), 'WooTabs Custom Product Tabs Lite for WooCommerce' ), '1.0.0' );
	}


	/**
	 * Load translations
	 *
	 * @since 1.0.0
	 */
	public function load_translation() {
		// localization
		load_plugin_textdomain( 'wootabs', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}


	/**
	 * Init WooCommerce Product Tabs Lite extension once we know WooCommerce is active
	 */
	public function init() {
		// Check if WooCommerce is active & at least the minimum version, and bail if it's not
		if ( ! self::is_plugin_active( 'woocommerce.php' ) || version_compare( get_option( 'woocommerce_db_version' ), WOOTABS_MIN_WOOCOMMERCE_VERSION, '<' ) ) {
			add_action( 'admin_notices', array( $this, 'render_woocommerce_requirements_notice' ) );
			return;
		}
		add_action( 'woocommerce_product_write_panel_tabs', array( $this, 'product_write_panel_tab' ) );
		add_action( 'woocommerce_product_data_panels',      array( $this, 'product_write_panel' ) );
		add_action( 'woocommerce_process_product_meta',     array( $this, 'product_save_data' ), 10, 2 );

		// frontend stuff
		add_filter( 'woocommerce_product_tabs', array( $this, 'add_custom_product_tabs' ) );

		// allow the use of shortcodes within the tab content
		add_filter( 'woocommerce_custom_product_tabs_lite_content', 'do_shortcode' );
	}

	public function constants(){
		/** plugin version number */
		$this->define('WOOTABS_VERSION', '1.0.0');
		/** required WooCommerce version number */
		define('WOOTABS_MIN_WOOCOMMERCE_VERSION', '3.9.4');
		/** plugin version name */
		$this->define('WOOTABS_VERSION_OPTION_NAME', 'wootabs_lite_db_version');
		$this->define('WOOTABS_URL', plugin_dir_url( __FILE__ ) );
		$this->define('WOOTABS_DIR', plugin_dir_path( __FILE__ ) );
		$this->define('WOOTABS_DIRNAME', dirname( plugin_basename( __FILE__ ) ) );
		$this->define('WOOTABS_BASENAME',  plugin_basename( __FILE__ ) );

	}



	public function define( $name, $value, $case_insensitive = false ) {
		if ( ! defined( $name ) ) {
			define( $name, $value, $case_insensitive );
		}
	}


	/** Frontend methods ******************************************************/


	/**
	 * Add the custom product tab
	 *
	 * $tabs structure:
	 * Array(
	 *   id => Array(
	 *     'title'    => (string) Tab title,
	 *     'priority' => (string) Tab priority,
	 *     'callback' => (mixed) callback function,
	 *   )
	 * )
	 *
	 * @since 1.0.0
	 * @param array $tabs array representing the product tabs
	 * @return array representing the product tabs
	 */
	public function add_custom_product_tabs( $tabs ) {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return $tabs;
		}

		if ( $this->product_has_custom_tabs( $product ) ) {

			foreach ( $this->tab_data as $tab ) {
				$tab_title = __( $tab['title'], 'wootabs' );
				$tabs[ $tab['id'] ] = array(
					'title'    => apply_filters( 'wootabs_lite_title', $tab_title, $product, $this ),
					'priority' => 25,
					'callback' => array( $this, 'custom_product_tabs_panel_content' ),
					'content'  => $tab['content'],  // custom field
				);
			}
		}

		return $tabs;
	}


	/**
	 * Renders the custom product tab panel content for the given $tab.
	 *
	 * @see WooTabsLite::add_custom_product_tabs() callback
	 *
	 * $tab structure:
	 * Array(
	 *   'title'    => (string) Tab title,
	 *   'priority' => (string) Tab priority,
	 *   'callback' => (mixed) callback function,
	 *   'id'       => (int) tab post identifier,
	 *   'content'  => (string) tab content,
	 * )
	 *
	 * @param string $key tab key
	 * @param array $tab tab data
	 */
	public function custom_product_tabs_panel_content( $key, $tab ) {

		// allow shortcodes to function
		$content = apply_filters( 'the_content', $tab['content'] );
		$content = str_replace( ']]>', ']]&gt;', $content );

		echo wp_kses_post( apply_filters( 'wootabs_lite_heading', '<h2>' . esc_html( $tab['title'] ) . '</h2>', $tab ) );
		echo wp_kses_post( apply_filters( 'wootabs_lite_content', $content, $tab ) );
	}


	/** Admin methods ******************************************************/


	/**
	 * Adds a new tab to the Product Data postbox in the admin product interface.
	 *
	 * @since 1.0.0
	 */
	public function product_write_panel_tab() {
		echo "<li class=\"product_tabs_lite_tab\"><a href=\"#woocommerce_product_tabs_lite\"><span>" . esc_html__( 'Custom Tab', 'wootabs' ) . "</span></a></li>";
	}


	/**
	 * Adds the panel to the Product Data postbox in the product interface
	 *
	 * @since 1.0.0
	 */
	public function product_write_panel() {
		global $post;

		$product = wc_get_product( $post );

		// pull the custom tab data out of the database
		$tab_data = $product->get_meta( 'frs_woo_product_tabs', true, 'edit' );

		if ( empty( $tab_data ) ) {

			// start with an array for PHP 7.1+
			$tab_data = array();

			$tab_data[] = array(
				'title'   => '',
				'content' => '',
			);
		}

		foreach ( $tab_data as $tab ) {
			// display the custom tab panel

			echo '<div id="woocommerce_product_tabs_lite" class="panel wc-metaboxes-wrapper woocommerce_options_panel">';
				woocommerce_wp_text_input( array(
					'id'          => '_wc_custom_product_tabs_lite_tab_title',
					'label'       => __( 'Tab Title', 'wootabs' ),
					'description' => __( 'Required for tab to be visible', 'wootabs' ),
					'value'       => $tab['title'],
				));

				woocommerce_wp_textarea_input( array(
					'id'          => '_wc_custom_product_tabs_lite_tab_content',
					'label'       => __( 'Content', 'wootabs' ),
					'placeholder' => __( 'HTML and text to display.', 'wootabs' ),
					'value'       => $tab['content'],
					'style'       => 'width:70%;height:14.5em;',
				));
			echo '</div>';
		}
	}


	/**
	 * Saves the data input into the product boxes, as post meta data
	 * identified by the name 'frs_woo_product_tabs'
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id the post (product) identifier
	 * @param stdClass $post the post (product)
	 */
	public function product_save_data( $post_id, $post ) {

		$tab_content = wp_kses_post( $_POST['_wc_custom_product_tabs_lite_tab_content'] );
		$tab_title   = sanitize_text_field( $_POST['_wc_custom_product_tabs_lite_tab_title'] );
		$product     = wc_get_product( $post_id );

		if ( empty( $tab_title ) && empty( $tab_content ) && $product->get_meta( 'frs_woo_product_tabs', true, 'edit' ) ) {

			// clean up if the custom tabs are removed
			$product->delete_meta_data( 'frs_woo_product_tabs' );
			$product->save();

		} elseif ( ! empty( $tab_title ) || ! empty( $tab_content ) ) {

			$tab_data = array();
			$tab_id   = '';

			if ( $tab_title ) {

				if ( strlen( $tab_title ) !== strlen( utf8_encode( $tab_title ) ) ) {

					// can't have titles with utf8 characters as it breaks the tab-switching javascript
					$tab_id = "tab-custom";

				} else {

					// convert the tab title into an id string
					$tab_id = strtolower( $tab_title );

					// remove non-alphas, numbers, underscores or whitespace
					$tab_id = preg_replace( "/[^\w\s]/", '', $tab_id );

					// replace all underscores with single spaces
					$tab_id = preg_replace( "/_+/", ' ', $tab_id );

					// replace all multiple spaces with single dashes
					$tab_id = preg_replace( "/\s+/", '-', $tab_id );

					// prepend with 'tab-' string
					$tab_id = 'tab-' . $tab_id;
				}
			}

			// save the serialized data to the database
			$tab_data[] = array(
				'title'   => $tab_title,
				'id'      => $tab_id,
				'content' => $tab_content,
			);

			$product->update_meta_data( 'frs_woo_product_tabs', $tab_data );
			$product->save();
		}
	}


	/** Helper methods ******************************************************/


	/**
	 * Main Custom Product Tabs Lite Instance, ensures only one instance is/can be loaded
	 *
	 * @since 1.0.0
	 * @see wc_custom_product_tabs_lite()
	 * @return WooTabsLite
	 */
	public static function instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Lazy-load the product_tabs meta data, and return true if it exists,
	 * false otherwise
	 *
	 * @param \WC_Product $product the product object
	 * @return true if there is custom tab data, false otherwise
	 */
	private function product_has_custom_tabs( $product ) {

		if ( false === $this->tab_data ) {
			$this->tab_data = maybe_unserialize( $product->get_meta( 'frs_woo_product_tabs', true, 'edit' ) );
		}

		// tab must at least have a title to exist
		return ! empty( $this->tab_data ) && ! empty( $this->tab_data[0] ) && ! empty( $this->tab_data[0]['title'] );
	}


	/**
	 * Helper function to determine whether a plugin is active.
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_name plugin name, as the plugin-filename.php
	 * @return boolean true if the named plugin is installed and active
	 */
	public static function is_plugin_active( $plugin_name ) {

		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, array_keys( get_site_option( 'active_sitewide_plugins', array() ) ) );
		}

		$plugin_filenames = array();

		foreach ( $active_plugins as $plugin ) {

			if ( false !== strpos( $plugin, '/' ) ) {

				// normal plugin name (plugin-dir/plugin-filename.php)
				list( , $filename ) = explode( '/', $plugin );

			} else {

				// no directory, just plugin file
				$filename = $plugin;
			}

			$plugin_filenames[] = $filename;
		}

		return in_array( $plugin_name, $plugin_filenames );
	}


	/**
	 * Renders a notice when WooCommerce is inactive or version is outdated.
	 *
	 * @since 1.0.0
	 */
	public static function render_woocommerce_requirements_notice() {

		$message = sprintf(
			/* translators: Placeholders: %1$s - <strong>, %2$s - </strong>, %3$s - version number, %4$s + %6$s - <a> tags, %5$s - </a> */
			esc_html__( '%1$sCustom Product Tabs Lite for WooCommerce is inactive.%2$s This plugin requires WooCommerce %3$s or newer. Please %4$sinstall WooCommerce %3$s or newer%5$s, or %6$srun the WooCommerce database upgrade%5$s.', 'wootabs' ),
			'<strong>',
			'</strong>',
			WOOTABS_MIN_WOOCOMMERCE_VERSION,
			'<a href="' . admin_url( 'plugins.php' ) . '">',
			'</a>',
			'<a href="' . admin_url( 'plugins.php?do_update_woocommerce=true' ) . '">'
		);

		printf( '<div class="error"><p>%s</p></div>', $message );
	}




	/** Lifecycle methods ******************************************************/


	/**
	 * Run every time.  Used since the activation hook is not executed when updating a plugin
	 *
	 * @since 1.0.0
	 */
	private function install() {

		$installed_version = get_option( WOOTABS_VERSION_OPTION_NAME );

		// installed version lower than plugin version?
		if ( -1 === version_compare( $installed_version, WOOTABS_VERSION ) ) {
			// new version number
			update_option( WOOTABS_VERSION_OPTION_NAME, WOOTABS_VERSION );
		}
	}


}


/**
 * Returns the One True Instance of Custom Product Tabs Lite
 *
 * @since 1.0.0
 * @return \WooTabsLite
 */
function wc_custom_product_tabs_lite() {
	return WooTabsLite::instance();
}


// fire it up!
wc_custom_product_tabs_lite();
