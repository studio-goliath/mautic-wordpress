<?php
/**
 * Plugin Name: WP Mautic
 * Plugin URI: https://github.com/mautic/mautic-wordpress
 * Contributors: mautic,hideokamoto,shulard,escopecz,dbhurley
 * Description: This plugin will allow you to add Mautic (Free Open Source Marketing Automation) tracking to your site
 * Version: 2.2.1
 * Requires at least: 4.6
 * Tested up to: 4.8
 * Author: Mautic community
 * Author URI: http://mautic.org
 * Text Domain: wp-mautic
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package wp-mautic
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	echo 'This file should not be accessed directly!';
	exit; // Exit if accessed directly.
}

// Store plugin directory.
define( 'VPMAUTIC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
// Store plugin main file path.
define( 'VPMAUTIC_PLUGIN_FILE', __FILE__ );

add_action( 'admin_menu', 'wpmautic_settings' );
add_action( 'plugins_loaded', 'wpmautic_injector' );

include_once( VPMAUTIC_PLUGIN_DIR . '/shortcodes.php' );
include_once( VPMAUTIC_PLUGIN_DIR . '/includes/Mautic_Api.php' );

/**
 * Declare option page
 */
function wpmautic_settings() {
	include_once( VPMAUTIC_PLUGIN_DIR . '/options.php' );

	$wpmautic_page_sufix_hook = add_options_page(
		__( 'WP Mautic Settings', 'wp-mautic' ),
		__( 'WPMautic', 'wp-mautic' ),
		'manage_options',
		'wpmautic',
		'wpmautic_options_page'
	);

	add_action( "load-{$wpmautic_page_sufix_hook}", 'wpmautic_option_page_check_for_action'  );
}

function wpmautic_option_page_check_for_action(){

	if( isset( $_GET['code'] ) ){
		// We are getting au authenticate code back from the mautic instance

		$mautic_api = new Mautic_Api();
		$mautic_api->auth_get_token( esc_attr( $_GET['code'] ) );

	} elseif ( isset( $_GET['wpmautic-action'] ) && $_GET['wpmautic-action'] === 'api-logout' ){

		$mautic_api = new Mautic_Api();
		$mautic_api->wpmautic_delete_api_token();
		wp_redirect( wpmautic_get_api_auth_redirect_url() );
	}
}

/**
 * Settings Link in the ``Installed Plugins`` page
 *
 * @param  array  $links array of plugin action links.
 * @param  string $file  Path to the plugin file relative to the plugins directory.
 *
 * @return array
 */
function wpmautic_plugin_actions( $links, $file ) {
	if ( plugin_basename( VPMAUTIC_PLUGIN_FILE ) === $file && function_exists( 'admin_url' ) ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'options-general.php?page=wpmautic' ),
			__( 'Settings' )
		);
		// Add the settings link before other links.
		array_unshift( $links, $settings_link );
	}
	return $links;
}
add_filter( 'plugin_action_links', 'wpmautic_plugin_actions', 10, 2 );

/**
 * Retrieve one of the wpmautic options but sanitized
 *
 * @param  string $option  Option name to be retrieved (base_url, script_location).
 * @param  mixed  $default Default option value return if not exists.
 *
 * @return string
 *
 * @throws InvalidArgumentException Thrown when the option name is not given.
 */
function wpmautic_option( $option, $default = null ) {
	$options = get_option( 'wpmautic_options' );

	switch ( $option ) {
		case 'script_location':
			return ! isset( $options[ $option ] ) ? 'header' : $options[ $option ];
		case 'fallback_activated':
			return isset( $options[ $option ] ) ? (bool) $options[ $option ] : true;
		case 'track_logged_user':
			return isset( $options[ $option ] ) ? (bool) $options[ $option ] : false;
		default:
			if ( ! isset( $options[ $option ] ) ) {
				if ( isset( $default ) ) {
					return $default;
				}

				throw new InvalidArgumentException( 'You must give a valid option name !' );
			}

			return $options[ $option ];
	}
}

/**
 * Apply JS tracking to the right place depending script_location.
 *
 * @return void
 */
function wpmautic_injector() {
	$script_location = wpmautic_option( 'script_location' );
	if ( 'header' === $script_location ) {
		add_action( 'wp_head', 'wpmautic_inject_script' );
	} else {
		add_action( 'wp_footer', 'wpmautic_inject_script' );
	}

	if ( true === wpmautic_option( 'fallback_activated', false ) ) {
		add_action( 'wp_footer', 'wpmautic_inject_noscript' );
	}
}

/**
 * Writes Tracking JS to the HTML source
 *
 * @return void
 */
function wpmautic_inject_script() {
	$base_url = wpmautic_option( 'base_url', '' );
	if ( empty( $base_url ) ) {
		return;
	}

	$attrs = wpmautic_get_tracking_attributes();

	?><script type="text/javascript">
	(function(w,d,t,u,n,a,m){w['MauticTrackingObject']=n;
		w[n]=w[n]||function(){(w[n].q=w[n].q||[]).push(arguments)},a=d.createElement(t),
		m=d.getElementsByTagName(t)[0];a.async=1;a.src=u;m.parentNode.insertBefore(a,m)
	})(window,document,'script','<?php echo esc_url( $base_url ); ?>/mtc.js','mt');

	mt('send', 'pageview'<?php echo count( $attrs ) > 0?', ' . wp_json_encode( $attrs ):'' ?>);
</script>
	<?php
}

/**
 * Writes Tracking image fallback to the HTML source
 * This is a separated function because <noscript> tags are not allowed in header !
 *
 * @return void
 */
function wpmautic_inject_noscript() {
	$base_url = wpmautic_option( 'base_url', '' );
	if ( empty( $base_url ) ) {
		return;
	}

	$url_query = wpmautic_get_url_query();
	$payload = rawurlencode( base64_encode( serialize( $url_query ) ) );
	?>
	<noscript>
		<img src="<?php echo esc_url( $base_url ); ?>/mtracking.gif?d=<?php echo esc_attr( $payload ); ?>"  style="display:none;" alt="" />
	</noscript>
	<?php
}

/**
 * Builds and returns additional data for URL query
 *
 * @return array
 */
function wpmautic_get_url_query() {
	global $wp;
	$current_url = add_query_arg( $wp->query_string, '', home_url( $wp->request ) );

	$attrs = wpmautic_get_tracking_attributes();

	$attrs['language']   = get_locale();
	$attrs['page_url']   = $current_url;
	$attrs['page_title'] = function_exists( 'wp_get_document_title' )
		? wp_get_document_title()
		: wp_title( '&raquo;', false );
	$attrs['referrer']   = function_exists( 'wp_get_raw_referer' )
		? wp_get_raw_referer()
		: null;
	if ( false === $attrs['referrer'] ) {
		$attrs['referrer'] = $current_url;
	}

	return $attrs;
}

/**
 * Create custom query parameters to be injected inside tracking
 *
 * @return array
 */
function wpmautic_get_tracking_attributes() {
	$attrs = wpmautic_get_user_query();

	/**
	 * Update / add data to be send withing Mautic tracker
	 *
	 * Default data only contains the 'language' key but every added key to the
	 * array will be sent to Mautic.
	 *
	 * @since 2.1.0
	 *
	 * @param array $attrs Attributes to be filters, default ['language' => get_locale()]
	 */
	return apply_filters( 'wpmautic_tracking_attributes', $attrs );
}

/**
 * Extract logged user informations to be send within Mautic tracker
 *
 * @return array
 */
function wpmautic_get_user_query() {
	$attrs = array();

	if (
		true === wpmautic_option( 'track_logged_user', false ) &&
		is_user_logged_in()
	) {
		$current_user = wp_get_current_user();
		$attrs['email']	 = $current_user->user_email;
		$attrs['firstname']  = $current_user->user_firstname;
		$attrs['lastname']  = $current_user->user_lastname;

		// Following Mautic fields has to be created manually and the fields must match these names.
		$attrs['wp_user']  = $current_user->user_login;
		$attrs['wp_alias']  = $current_user->display_name;
		$attrs['wp_registration_date'] = date(
			'Y-m-d',
			strtotime( $current_user->user_registered )
		);
	}

	return $attrs;
}
