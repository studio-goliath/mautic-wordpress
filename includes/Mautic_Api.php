<?php
/**
 * Class Mautic_Api
 */

class Mautic_Api {

	private $api_end_point;


	public function __construct() {

		$url = wpmautic_option( 'base_url', '' );

		$this->api_end_point = $url . '/api/';
	}


	/**
	 * @param string $end_point
	 * @param array $body
	 * @param string $method
	 *
	 * @return object|\WP_Error response body or WP_Error when something went wrong
	 */
	public function call( $end_point, $body = array(), $method = 'GET'){

		$token = $this->get_token();

		if( is_wp_error( $token ) ){

			return $token;

		} else {

			$response = wp_remote_request( $this->api_end_point . $end_point, array(
				'method'    => $method,
				'headers'    => array( 'Authorization' => "Bearer {$token}" ),
				'body'      => $body,
			));


			return $this->handle_remote_response( $response );
		}

	}

	public function wpmautic_delete_api_token(){
		delete_option( '_wpmautic_access_token' );
		delete_option( '_wpmautic_access_token_expire_in' );
		delete_option( '_wpmautic_refresh_token' );
	}


	/**
	 * @return string|\WP_Error $token to use on the API call on WP_Error on failure
	 */
	private function get_token(){

		$access_token = get_option( '_wpmautic_access_token' );
		$access_token_expire_in = get_option( '_wpmautic_access_token_expire_in' );

		if( $access_token && $access_token_expire_in ){

			$now = time();
			if( $now < $access_token_expire_in ) {

				return $access_token;

			} else {

				return $this->refresh_token();
			}

		}  else {
			return new WP_Error( 400, 'No token find, try re-authenticate' );
		}
	}


	/**
	 * @param array $response HTTP response
	 *
	 * @return object|\WP_Error $response body if no error or WP_Error
	 */
	private function handle_remote_response( $response ){

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if( $response_code == 200 ){

			$response_body = json_decode( $response_body );

			if( property_exists( $response_body, 'errors') ){

				$error_messages = wp_list_pluck( $response_body->errors, 'message');
				return new WP_Error( 400, implode( ', ', $error_messages ) );
			} else {

				return $response_body;
			}

		} elseif( is_wp_error( $response ) ) {

			return $response;

		} else {

			return new WP_Error( $response_code, 'Mautic API reponse error', $response_body );
		}
	}


	/**
	 * Exchange authorisation code for a valid token
	 *
	 * @param string $code code you get after you authorize your application on your Mautic instance
	 *
	 * @return object|\WP_Error
	 */
	public function auth_get_token( $code ){

		// We are getting back from mautic with a code
		$url = wpmautic_option( 'base_url', '' );
		$public_key = get_option( 'wpmautic_api_public_key', '' );
		$secret_key = get_option( 'wpmautic_api_secret_key', '' );

		$response = wp_remote_post($url . '/oauth/v2/token', array(
			'body'  => array(
				'client_id'     => $public_key,
				'client_secret' => $secret_key,
				'grant_type'    => 'authorization_code',
				'redirect_uri'  => wpmautic_get_api_auth_redirect_url(),
				'code'          => $code
			)
		) );

		$token_reponse = $this->handle_remote_response( $response );

		if( is_wp_error( $token_reponse ) ){

			return $token_reponse;

		} else {

			$this->save_token( $token_reponse );
			wp_redirect( wpmautic_get_api_auth_redirect_url() );
		}

	}


	/**
	 * Get a new token when current token expired
	 *
	 * @return string|\WP_Error return $token you can use on WP_Error
	 */
	private function refresh_token() {

		$refresh_token = get_option( '_wpmautic_refresh_token' );
		$url = wpmautic_option( 'base_url', '' );
		$public_key = get_option( 'wpmautic_api_public_key', '' );
		$secret_key = get_option( 'wpmautic_api_secret_key', '' );

		$response = wp_remote_post( $url . '/oauth/v2/token', array(
			'body' => array(
				'client_id'         => $public_key,
				'client_secret'     => $secret_key,
				'grant_type'        => 'refresh_token',
				'refresh_token'     => $refresh_token,
				'redirect_uri'     => wpmautic_get_api_auth_redirect_url()
			)
		));

		$token_reponse = $this->handle_remote_response( $response );

		if( is_wp_error( $token_reponse ) ){

			return $token_reponse;

		} else {

			return $this->save_token( $token_reponse );
		}

	}


	/**
	 * @param object $token_reponse get from the /auth/v2/token API endpoint
	 *          {
	 *              access_token: "NEW_ACCESS_TOKEN",
	 *              expires_in: 3600,
	 *              token_type: "bearer",
	 *              scope: "",
	 *              refresh_token: "REFRESH_TOKEN"
	 *          }
	 *
	 * @return string|\WP_Error $token you can use
	 */
	private function save_token( $token_reponse ){

		if( property_exists( $token_reponse, 'access_token' ) ) {

			update_option( '_wpmautic_access_token', $token_reponse->access_token );
			update_option( '_wpmautic_access_token_expire_in', time() + $token_reponse->expires_in );
			update_option( '_wpmautic_refresh_token', $token_reponse->refresh_token );

			return $token_reponse->access_token;

		} else {

			return new WP_Error( 400, 'Wrong Token response' );
		}
	}

}

add_action( 'update_option_wpmautic_api_public_key', array( 'Mautic_Api', 'wpmautic_delete_api_token') );
add_action( 'update_option_wpmautic_api_secret_key', array( 'Mautic_Api', 'wpmautic_delete_api_token') );