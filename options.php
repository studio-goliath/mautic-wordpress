<?php
/**
 * Option page definition
 *
 * @package wp-mautic
 */

// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	echo 'This file should not be accessed directly!';
	exit; // Exit if accessed directly.
}

/**
 * HTML for the Mautic option page
 */
function wpmautic_options_page() {
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'WP Mautic', 'wp-mautic' ); ?></h1>
		<p><?php esc_html_e( 'Add Mautic tracking capabilities to your website.','wp-mautic' ); ?></p>
		<form action="options.php" method="post">
			<?php settings_fields( 'wpmautic' ); ?>
			<?php do_settings_sections( 'wpmautic' ); ?>
			<?php submit_button(); ?>
		</form>
		<h2><?php esc_html_e( 'Shortcode Examples:', 'wp-mautic' ); ?></h2>
		<ul>
			<li><?php esc_html_e( 'Mautic Form Embed:', 'wp-mautic' ); ?> <code>[mautic type="form" id="1"]</code></li>
			<li><?php esc_html_e( 'Mautic Dynamic Content:', 'wp-mautic' ); ?> <code>[mautic type="content" slot="slot_name"]<?php esc_html_e( 'Default Text', 'wp-mautic' ); ?>[/mautic]</code></li>
		</ul>
		<h2><?php esc_html_e( 'Quick Links', 'wp-mautic' ); ?></h2>
		<ul>
			<li>
				<a href="https://github.com/mautic/mautic-wordpress#mautic-wordpress-plugin" target="_blank"><?php esc_html_e( 'Plugin docs', 'wp-mautic' ); ?></a>
			</li>
			<li>
				<a href="https://github.com/mautic/mautic-wordpress/issues" target="_blank"><?php esc_html_e( 'Plugin support', 'wp-mautic' ); ?></a>
			</li>
			<li>
				<a href="https://mautic.org" target="_blank"><?php esc_html_e( 'Mautic project', 'wp-mautic' ); ?></a>
			</li>
			<li>
				<a href="http://docs.mautic.org/" target="_blank"><?php esc_html_e( 'Mautic docs', 'wp-mautic' ); ?></a>
			</li>
			<li>
				<a href="https://www.mautic.org/community/" target="_blank"><?php esc_html_e( 'Mautic forum', 'wp-mautic' ); ?></a>
			</li>
		</ul>
	</div>
	<?php
}

/**
 * Define admin_init hook logic
 */
function wpmautic_admin_init() {
	register_setting( 'wpmautic', 'wpmautic_options', 'wpmautic_options_validate' );
	register_setting( 'wpmautic', 'wpmautic_api_public_key',  array(
		'type'              => 'string',
		'description'       => 'Mautic Api public key',
		'sanitize_callback' => 'sanitize_text_field',
		'show_in_rest'      => false,
	));
	register_setting( 'wpmautic', 'wpmautic_api_secret_key', array(
		'type'              => 'string',
		'description'       => 'Mautic Api secret key',
		'sanitize_callback' => 'sanitize_text_field',
		'show_in_rest'      => false,
	));

	add_settings_section(
		'wpmautic_main',
		__( 'Main Settings', 'wp-mautic' ),
		'wpmautic_section_text',
		'wpmautic'
	);

	add_settings_field(
		'wpmautic_base_url',
		__( 'Mautic URL', 'wp-mautic' ),
		'wpmautic_base_url',
		'wpmautic',
		'wpmautic_main'
	);
	add_settings_field(
		'wpmautic_script_location',
		__( 'Tracking script location', 'wp-mautic' ),
		'wpmautic_script_location',
		'wpmautic',
		'wpmautic_main'
	);
	add_settings_field(
		'wpmautic_fallback_activated',
		__( 'Tracking image', 'wp-mautic' ),
		'wpmautic_fallback_activated',
		'wpmautic',
		'wpmautic_main'
	);
	add_settings_field(
		'wpmautic_track_logged_user',
		__( 'Logged user', 'wp-mautic' ),
		'wpmautic_track_logged_user',
		'wpmautic',
		'wpmautic_main'
	);

	add_settings_section(
		'wpmautic_api_authentication',
		__( 'API Authentication', 'wp-mautic' ),
		'wpmautic_api_authentication_description',
		'wpmautic'
	);

	add_settings_field(
		'wpmautic_api_public_key',
		__( 'Public Key', 'wp-mautic' ),
		'wpmautic_option_page_text_field',
		'wpmautic',
		'wpmautic_api_authentication',
		array(
			'label_for'     => 'wpmautic_api_public_key',
			'option'        => 'wpmautic_api_public_key',
			'placeholder'   => ''
		)
	);

	add_settings_field(
		'wpmautic_api_secret_key',
		__( 'Secret Key', 'wp-mautic' ),
		'wpmautic_option_page_text_field',
		'wpmautic',
		'wpmautic_api_authentication',
		array(
			'label_for'     => 'wpmautic_api_secret_key',
			'option'        => 'wpmautic_api_secret_key',
			'placeholder'   => ''
		)
	);

	add_settings_field(
		'wpmautic_api_auth',
		__( 'API Authentication', 'wp-mautic' ),
		'wpmautic_api_auth_field',
		'wpmautic',
		'wpmautic_api_authentication'
	);
}
add_action( 'admin_init', 'wpmautic_admin_init' );

/**
 * Section text
 */
function wpmautic_section_text() {
}

/**
 * Define the input field for Mautic base URL
 */
function wpmautic_base_url() {
	$url = wpmautic_option( 'base_url', '' );

	?>
	<input
		id="wpmautic_base_url"
		name="wpmautic_options[base_url]"
		size="40"
		type="text"
		placeholder="http://..."
		value="<?php echo esc_url_raw( $url, array( 'http', 'https' ) ); ?>"
	/>
	<?php
}

/**
 * Define the input field for Mautic script location
 */
function wpmautic_script_location() {
	$position = wpmautic_option( 'script_location', '' );

	?>
	<fieldset id="wpmautic_script_location">
		<label>
			<input
				type="radio"
				name="wpmautic_options[script_location]"
				value="header"
				<?php if ( 'footer' !== $position ) : ?>checked<?php endif; ?>
			/>
			<?php esc_html_e( 'Embedded within the `wp_head` action.', 'wp-mautic' ); ?>
		</label>
		<br/>
		<label>
			<input
				type="radio"
				name="wpmautic_options[script_location]"
				value="footer"
				<?php if ( 'footer' === $position ) : ?>checked<?php endif; ?>
			/>
			<?php esc_html_e( 'Embedded within the `wp_footer` action.', 'wp-mautic' ); ?>
		</label>
	</fieldset>
	<?php
}

/**
 * Define the input field for Mautic fallback flag
 */
function wpmautic_fallback_activated() {
	$flag = wpmautic_option( 'fallback_activated', false );

	?>
	<input
		id="wpmautic_fallback_activated"
		name="wpmautic_options[fallback_activated]"
		type="checkbox"
		value="1"
		<?php if ( true === $flag ) : ?>checked<?php endif; ?>
	/>
	<label for="wpmautic_fallback_activated">
		<?php esc_html_e( 'Activate it when JavaScript is disabled ?', 'wp-mautic' ); ?>
	</label>
	<?php
}

/**
 * Define the input field for Mautic logged user tracking flag
 */
function wpmautic_track_logged_user() {
	$flag = wpmautic_option( 'track_logged_user', false );

	?>
	<input
		id="wpmautic_track_logged_user"
		name="wpmautic_options[track_logged_user]"
		type="checkbox"
		value="1"
		<?php if ( true === $flag ) : ?>checked<?php endif; ?>
	/>
	<label for="wpmautic_track_logged_user">
		<?php esc_html_e( 'Track user information when logged ?', 'wp-mautic' ); ?>
	</label>
	<?php
}

/**
 * @param array    $args {
 *     @type string $label_for      The id of your field
 *     @type string $option         The name of your option
 *     @type string $placeholder    The place holder you want to display
 * }
 */
function wpmautic_option_page_text_field( $args ){
	$option = get_option( $args['option'], '' );

	?>
	<input
		id="<?php echo $args['label_for']; ?>"
		name="<?php echo $args['option']; ?>"
		class="widefat"
		type="text"
		placeholder="<?php echo $args['placeholder']; ?>"
		value="<?php echo esc_attr( $option ); ?>"
	/>
	<?php
}

/**
 * Explication text for the API Authenticate process
 */
function wpmautic_api_authentication_description() {
	$redirect_url = wpmautic_get_api_auth_redirect_url();

	// code ODFhNjEzYzZkMGU3NWEzODViMjg4YTU0OTE4ZjZiOWVhMjY2NzE4MzUxYTZiZmFjNGIzYmUxZDllNzhiN2IxNQ

	?>
	<p>
		<?php _e('Steps to follow to get your website connected to your Mautic instance', 'wp-mautic'); ?>
	</p>
	<ol>
		<li><?php _e('You must activate the API on your Mautic instance', 'wp-mautic') ?></li>
		<li>
			<?php _e('Create a API Credentials', 'wp-mautic'); ?>
			<ul class="ul-disc">
				<li><?php _e('Authorization Protocol', 'wp-mautic'); echo ' : OAuth 2'; ?></li>
				<li><?php printf( __('Name : What you want ( maybe : "%s") ', 'wp-mautic' ), get_bloginfo( 'name' ) ); ?></li>
				<li><?php _e('Redirect URI', 'wp-mautic' ); echo " : <code>{$redirect_url}</code>"; ?></li>
			</ul>
		</li>
		<li><?php _e('Copy past your Public Key and Secret Key below', 'wp-mautic');?></li>
		<li><?php _e('Click the button bellow to Authenticate your website to your Mautic instance', 'wp-mautic');?></li>
	</ol>
	<?php

}

function wpmautic_api_auth_field(){

	$url = wpmautic_option( 'base_url', '' );
	$public_key = get_option( 'wpmautic_api_public_key', '' );
	$secret_key = get_option( 'wpmautic_api_secret_key', '' );

	if( $url && $public_key && $secret_key ){

		$redirect_url = wpmautic_get_api_auth_redirect_url();


		// Check if we are already authenticate
		$access_token = get_option( '_wpmautic_access_token' );

		if( $access_token ){

			$mautic_api = new Mautic_Api();

			$user = $mautic_api->call( 'users/self' );

			if( $user && ! is_wp_error( $user ) ){

				$api_log_out_url = add_query_arg( 'wpmautic-action', 'api-logout', $redirect_url );
				?>
				<p>
					<span><?php printf( __( 'Your are authenticate as %s', 'wp-mautic'), "<code>{$user->username}</code>" ); ?></span>
					<a href="<?php echo $api_log_out_url; ?>" class="button"><?php _e('Log Out'); ?></a>
				</p>
				<?php
			}

		} else {

			$get_auth_param = array(
				'client_id'     => $public_key,
				'grant_type'    => 'authorization_code',
				'redirect_uri'  => urlencode( $redirect_url ),
				'response_type' => 'code',
				// TODO 'state' => 'UNIQUE_STATE_STRING'
			);

			$get_auth_code_url = add_query_arg( $get_auth_param, $url . '/oauth/v2/authorize' );


			?>
			<p>
				<a class="button" href="<?php echo $get_auth_code_url; ?>"><?php _e('Authenticate your website to your Mautic instance', 'wp-mautic') ?></a>
			</p>
			<?php
		}


	} else {
		?>
		<p class="description">
			<?php _e('You need to file in your Mautic URL, API Public Key and API Private Key', 'wp-mautic'); ?>
		</p>
		<?php
	}


}


/**
 * Validate base URL input value
 *
 * @param  array $input Input data.
 * @return array
 */
function wpmautic_options_validate( $input ) {
	$options = get_option( 'wpmautic_options' );

	$input['base_url'] = isset( $input['base_url'] )
		? trim( $input['base_url'], " \t\n\r\0\x0B/" )
		: '';

	$options['base_url'] = esc_url_raw( trim( $input['base_url'], " \t\n\r\0\x0B/" ) );
	$options['script_location'] = isset( $input['script_location'] )
		? trim( $input['script_location'] )
		: 'header';
	if ( ! in_array( $options['script_location'], array( 'header', 'footer' ), true ) ) {
		$options['script_location'] = 'header';
	}

	$options['fallback_activated'] = isset( $input['fallback_activated'] ) && '1' === $input['fallback_activated']
		? true
		: false;
	$options['track_logged_user'] = isset( $input['track_logged_user'] ) && '1' === $input['track_logged_user']
		? true
		: false;

	return $options;
}
