<?php
/**
 * Handle Social Sign-In and Registration via Google and Apple OAuth2 / OIDC
 *
 * @since      2.2.0
 * @package    DLM
 * @subpackage DLM/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.SlowDBQuery

class DLM_Social_Auth {

	/**
	 * Database access instance
	 *
	 * @var DLM_DB
	 */
	private $db;

	/**
	 * Initialize the social authentication engine
	 *
	 * @param DLM_DB $db Database instance.
	 */
	public function __construct( $db ) {
		$this->db = $db;
	}

	/**
	 * Register REST API routes for OAuth callbacks and redirects
	 */
	public function register_routes() {
		register_rest_route( 'dlm/v1', '/auth/(?P<provider>google|apple)/redirect', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'handle_oauth_redirect' ),
			'permission_callback' => '__return_true',
		) );

		register_rest_route( 'dlm/v1', '/auth/google/callback', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'handle_google_callback' ),
			'permission_callback' => '__return_true',
		) );

		register_rest_route( 'dlm/v1', '/auth/apple/callback', array(
			'methods'             => array( 'GET', 'POST' ),
			'callback'            => array( $this, 'handle_apple_callback' ),
			'permission_callback' => '__return_true',
		) );
	}

	/**
	 * Get the callback / redirect URI for a provider
	 *
	 * @param string $provider 'google' or 'apple'.
	 * @return string Full REST callback URL.
	 */
	public static function get_callback_url( $provider ) {
		return esc_url_raw( rest_url( 'dlm/v1/auth/' . sanitize_key( $provider ) . '/callback' ) );
	}

	/**
	 * Get the initiation authorization URL for a provider with CSRF state token
	 *
	 * @param string $provider    'google' or 'apple'.
	 * @param string $redirect_to Optional post-login destination URL.
	 * @return string Initiation or direct OAuth URL.
	 */
	public static function get_auth_url( $provider, $redirect_to = '' ) {
		if ( empty( $redirect_to ) ) {
			$redirect_to = dlm_get_page_url( 'account' );
		}

		$state = wp_generate_password( 32, false );
		set_transient( 'dlm_oauth_state_' . $state, array(
			'provider'    => $provider,
			'redirect_to' => esc_url_raw( $redirect_to ),
			'created_at'  => time(),
		), 10 * MINUTE_IN_SECONDS );

		$callback_url = self::get_callback_url( $provider );

		if ( 'google' === $provider ) {
			$client_id = trim( get_option( 'dlm_google_client_id', '' ) );
			if ( empty( $client_id ) ) {
				return add_query_arg( 'error', 'google_not_configured', dlm_get_page_url( 'account' ) );
			}

			$params = array(
				'client_id'             => $client_id,
				'redirect_uri'          => $callback_url,
				'response_type'         => 'code',
				'scope'                 => 'openid email profile',
				'state'                 => $state,
				'access_type'           => 'online',
				'include_granted_scopes'=> 'true',
				'prompt'                => 'select_account',
			);

			return add_query_arg( $params, 'https://accounts.google.com/o/oauth2/v2/auth' );
		}

		if ( 'apple' === $provider ) {
			$services_id = trim( get_option( 'dlm_apple_services_id', '' ) );
			if ( empty( $services_id ) ) {
				return add_query_arg( 'error', 'apple_not_configured', dlm_get_page_url( 'account' ) );
			}

			$params = array(
				'client_id'     => $services_id,
				'redirect_uri'  => $callback_url,
				'response_type' => 'code id_token',
				'response_mode' => 'form_post',
				'scope'         => 'name email',
				'state'         => $state,
			);

			return add_query_arg( $params, 'https://appleid.apple.com/auth/authorize' );
		}

		return dlm_get_page_url( 'account' );
	}

	/**
	 * Rate limit check per IP address (Max 30 attempts per 10 minutes)
	 *
	 * @return bool True if allowed, false if throttled.
	 */
	private function check_rate_limit() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$transient_key = 'dlm_auth_rl_' . md5( $ip );
		$attempts = (int) get_transient( $transient_key );

		if ( $attempts > 30 ) {
			return false;
		}

		set_transient( $transient_key, $attempts + 1, 10 * MINUTE_IN_SECONDS );
		return true;
	}

	/**
	 * Verify and consume the OAuth CSRF state token
	 *
	 * @param string $state Token string from callback request.
	 * @param string $expected_provider 'google' or 'apple'.
	 * @return array|false State payload array or false if invalid/expired.
	 */
	private function verify_and_consume_state( $state, $expected_provider ) {
		if ( empty( $state ) ) {
			return false;
		}

		$transient_key = 'dlm_oauth_state_' . sanitize_text_field( $state );
		$stored = get_transient( $transient_key );

		if ( ! $stored || ! is_array( $stored ) ) {
			return false;
		}

		delete_transient( $transient_key );

		if ( ! isset( $stored['provider'] ) || $stored['provider'] !== $expected_provider ) {
			return false;
		}

		return $stored;
	}

	/**
	 * Handle Google OAuth2 callback
	 *
	 * @param WP_REST_Request $request REST request instance.
	 */
	public function handle_google_callback( $request ) {
		if ( ! $this->check_rate_limit() ) {
			wp_die( esc_html__( 'Too many authentication attempts. Please try again later.', 'digital-library-membership' ), 429 );
		}

		$error = $request->get_param( 'error' );
		if ( ! empty( $error ) ) {
			$redirect = add_query_arg( 'social_error', 'google_access_denied', dlm_get_page_url( 'account' ) );
			wp_safe_redirect( $redirect );
			exit;
		}

		$state = $request->get_param( 'state' );
		$code  = $request->get_param( 'code' );

		$state_data = $this->verify_and_consume_state( $state, 'google' );
		if ( ! $state_data || empty( $code ) ) {
			$redirect = add_query_arg( 'social_error', 'invalid_state', dlm_get_page_url( 'account' ) );
			wp_safe_redirect( $redirect );
			exit;
		}

		$client_id     = trim( get_option( 'dlm_google_client_id', '' ) );
		$client_secret = trim( get_option( 'dlm_google_client_secret', '' ) );
		$callback_url  = self::get_callback_url( 'google' );

		// 1. Exchange authorization code for token
		$token_response = wp_remote_post( 'https://oauth2.googleapis.com/token', array(
			'timeout' => 20,
			'headers' => array( 'Accept' => 'application/json' ),
			'body'    => array(
				'code'          => $code,
				'client_id'     => $client_id,
				'client_secret' => $client_secret,
				'redirect_uri'  => $callback_url,
				'grant_type'    => 'authorization_code',
			),
		) );

		if ( is_wp_error( $token_response ) ) {
			$redirect = add_query_arg( 'social_error', 'token_exchange_failed', dlm_get_page_url( 'account' ) );
			wp_safe_redirect( $redirect );
			exit;
		}

		$token_body = json_decode( wp_remote_retrieve_body( $token_response ), true );
		if ( empty( $token_body['access_token'] ) ) {
			$redirect = add_query_arg( 'social_error', 'no_access_token', dlm_get_page_url( 'account' ) );
			wp_safe_redirect( $redirect );
			exit;
		}

		// 2. Fetch user profile
		$userinfo_response = wp_remote_get( 'https://www.googleapis.com/oauth2/v3/userinfo', array(
			'timeout' => 20,
			'headers' => array(
				'Authorization' => 'Bearer ' . $token_body['access_token'],
				'Accept'        => 'application/json',
			),
		) );

		if ( is_wp_error( $userinfo_response ) ) {
			$redirect = add_query_arg( 'social_error', 'profile_fetch_failed', dlm_get_page_url( 'account' ) );
			wp_safe_redirect( $redirect );
			exit;
		}

		$profile = json_decode( wp_remote_retrieve_body( $userinfo_response ), true );

		if ( empty( $profile['sub'] ) || empty( $profile['email'] ) || empty( $profile['email_verified'] ) ) {
			$redirect = add_query_arg( 'social_error', 'unverified_email', dlm_get_page_url( 'account' ) );
			wp_safe_redirect( $redirect );
			exit;
		}

		// 3. Match or Create User
		$google_id  = sanitize_text_field( $profile['sub'] );
		$email      = sanitize_email( $profile['email'] );
		$name       = ! empty( $profile['name'] ) ? sanitize_text_field( $profile['name'] ) : '';
		$first_name = ! empty( $profile['given_name'] ) ? sanitize_text_field( $profile['given_name'] ) : '';
		$last_name  = ! empty( $profile['family_name'] ) ? sanitize_text_field( $profile['family_name'] ) : '';
		$avatar     = ! empty( $profile['picture'] ) ? esc_url_raw( $profile['picture'] ) : '';

		$user_id = $this->match_or_create_user( 'google', $google_id, $email, $name, $first_name, $last_name, $avatar );

		if ( is_wp_error( $user_id ) ) {
			$redirect = add_query_arg( 'social_error', 'account_creation_failed', dlm_get_page_url( 'account' ) );
			wp_safe_redirect( $redirect );
			exit;
		}

		// 4. Log in and redirect to dashboard
		$this->authenticate_and_redirect( $user_id, $state_data['redirect_to'] );
	}

	/**
	 * Handle Apple Sign-In callback
	 *
	 * @param WP_REST_Request $request REST request instance.
	 */
	public function handle_apple_callback( $request ) {
		if ( ! $this->check_rate_limit() ) {
			wp_die( esc_html__( 'Too many authentication attempts. Please try again later.', 'digital-library-membership' ), 429 );
		}

		$params = $request->get_params();

		$error = isset( $params['error'] ) ? sanitize_text_field( $params['error'] ) : '';
		if ( ! empty( $error ) ) {
			$redirect = add_query_arg( 'social_error', 'apple_access_denied', dlm_get_page_url( 'account' ) );
			wp_safe_redirect( $redirect );
			exit;
		}

		$state    = isset( $params['state'] ) ? sanitize_text_field( $params['state'] ) : '';
		$code     = isset( $params['code'] ) ? sanitize_text_field( $params['code'] ) : '';
		$id_token = isset( $params['id_token'] ) ? sanitize_text_field( $params['id_token'] ) : '';

		$state_data = $this->verify_and_consume_state( $state, 'apple' );
		if ( ! $state_data || ( empty( $code ) && empty( $id_token ) ) ) {
			$redirect = add_query_arg( 'social_error', 'invalid_state', dlm_get_page_url( 'account' ) );
			wp_safe_redirect( $redirect );
			exit;
		}

		// Extract user name object if Apple provided it during first-time authorization
		$first_name = '';
		$last_name  = '';
		if ( ! empty( $params['user'] ) ) {
			$user_json = is_string( $params['user'] ) ? json_decode( wp_unslash( $params['user'] ), true ) : $params['user'];
			if ( is_array( $user_json ) && ! empty( $user_json['name'] ) ) {
				$first_name = isset( $user_json['name']['firstName'] ) ? sanitize_text_field( $user_json['name']['firstName'] ) : '';
				$last_name  = isset( $user_json['name']['lastName'] ) ? sanitize_text_field( $user_json['name']['lastName'] ) : '';
			}
		}

		$services_id = trim( get_option( 'dlm_apple_services_id', '' ) );
		$team_id     = trim( get_option( 'dlm_apple_team_id', '' ) );
		$key_id      = trim( get_option( 'dlm_apple_key_id', '' ) );
		$private_key = trim( get_option( 'dlm_apple_private_key', '' ) );

		$apple_sub = '';
		$email     = '';

		// If code is provided and credentials exist, exchange for token with Apple API
		if ( ! empty( $code ) && ! empty( $team_id ) && ! empty( $key_id ) && ! empty( $private_key ) ) {
			$client_secret = $this->generate_apple_client_secret( $team_id, $services_id, $key_id, $private_key );

			if ( $client_secret ) {
				$token_response = wp_remote_post( 'https://appleid.apple.com/auth/token', array(
					'timeout' => 20,
					'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
					'body'    => array(
						'client_id'     => $services_id,
						'client_secret' => $client_secret,
						'code'          => $code,
						'grant_type'    => 'authorization_code',
						'redirect_uri'  => self::get_callback_url( 'apple' ),
					),
				) );

				if ( ! is_wp_error( $token_response ) ) {
					$token_body = json_decode( wp_remote_retrieve_body( $token_response ), true );
					if ( ! empty( $token_body['id_token'] ) ) {
						$id_token = $token_body['id_token'];
					}
				}
			}
		}

		// Decode the ID Token JWT payload
		if ( ! empty( $id_token ) ) {
			$jwt_parts = explode( '.', $id_token );
			if ( count( $jwt_parts ) === 3 ) {
				$payload_json = base64_decode( str_pad( strtr( $jwt_parts[1], '-_', '+/' ), strlen( $jwt_parts[1] ) % 4, '=', STR_PAD_RIGHT ) );
				$jwt_claims   = json_decode( $payload_json, true );

				if ( is_array( $jwt_claims ) ) {
					$apple_sub = isset( $jwt_claims['sub'] ) ? sanitize_text_field( $jwt_claims['sub'] ) : '';
					$email     = isset( $jwt_claims['email'] ) ? sanitize_email( $jwt_claims['email'] ) : '';
				}
			}
		}

		if ( empty( $apple_sub ) ) {
			$redirect = add_query_arg( 'social_error', 'apple_auth_failed', dlm_get_page_url( 'account' ) );
			wp_safe_redirect( $redirect );
			exit;
		}

		// If Apple didn't return an email in claims, try matching existing user by provider ID
		if ( empty( $email ) ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Match existing user by Apple ID.
			$existing_users = get_users( array(
				'meta_key'   => '_dlm_apple_id',
				'meta_value' => $apple_sub,
				'number'     => 1,
				'fields'     => 'ID',
			) );

			if ( ! empty( $existing_users ) ) {
				$this->authenticate_and_redirect( $existing_users[0], $state_data['redirect_to'] );
				return;
			}

			// Cannot create fresh account without email
			$redirect = add_query_arg( 'social_error', 'apple_email_missing', dlm_get_page_url( 'account' ) );
			wp_safe_redirect( $redirect );
			exit;
		}

		$full_name = trim( $first_name . ' ' . $last_name );
		$user_id   = $this->match_or_create_user( 'apple', $apple_sub, $email, $full_name, $first_name, $last_name, '' );

		if ( is_wp_error( $user_id ) ) {
			$redirect = add_query_arg( 'social_error', 'account_creation_failed', dlm_get_page_url( 'account' ) );
			wp_safe_redirect( $redirect );
			exit;
		}

		$this->authenticate_and_redirect( $user_id, $state_data['redirect_to'] );
	}

	/**
	 * Generate Apple Client Secret ES256 JWT using OpenSSL
	 *
	 * @param string $team_id     Apple 10-char Team ID.
	 * @param string $services_id Apple Services ID.
	 * @param string $key_id      Apple Key ID.
	 * @param string $private_key Apple EC .p8 private key string.
	 * @return string|false ES256 JWT or false on failure.
	 */
	private function generate_apple_client_secret( $team_id, $services_id, $key_id, $private_key ) {
		if ( ! function_exists( 'openssl_sign' ) ) {
			return false;
		}

		// Clean up and format private key if needed
		if ( strpos( $private_key, '-----BEGIN' ) === false ) {
			$private_key = "-----BEGIN PRIVATE KEY-----\n" . wordwrap( trim( $private_key ), 64, "\n", true ) . "\n-----END PRIVATE KEY-----";
		}

		$pkey_res = openssl_pkey_get_private( $private_key );
		if ( ! $pkey_res ) {
			return false;
		}

		$header = array(
			'alg' => 'ES256',
			'kid' => $key_id,
			'typ' => 'JWT',
		);

		$now = time();
		$payload = array(
			'iss' => $team_id,
			'iat' => $now,
			'exp' => $now + ( 86400 * 180 ), // 180 days (Apple max)
			'aud' => 'https://appleid.apple.com',
			'sub' => $services_id,
		);

		$encode_b64 = function( $data ) {
			return rtrim( strtr( base64_encode( is_array( $data ) ? wp_json_encode( $data ) : $data ), '+/', '-_' ), '=' );
		};

		$unsigned_token = $encode_b64( $header ) . '.' . $encode_b64( $payload );

		$signature = '';
		$signed = openssl_sign( $unsigned_token, $signature, $pkey_res, OPENSSL_ALGO_SHA256 );
		if ( ! $signed ) {
			return false;
		}

		// Convert DER signature to Raw R+S IEEE P1363 format required by ES256 JWT
		$raw_signature = $this->der_to_raw_signature( $signature );

		return $unsigned_token . '.' . $encode_b64( $raw_signature );
	}

	/**
	 * Convert DER-encoded ECDSA signature to raw R+S format for ES256 JWT
	 *
	 * @param string $der DER binary signature string.
	 * @return string 64-byte raw signature.
	 */
	private function der_to_raw_signature( $der ) {
		if ( strlen( $der ) < 8 || ord( $der[0] ) !== 0x30 ) {
			return $der;
		}

		$pos = 2;
		if ( ord( $der[1] ) & 0x80 ) {
			$pos += ( ord( $der[1] ) & 0x7f );
		}

		// Read R
		if ( ord( $der[ $pos ] ) !== 0x02 ) {
			return $der;
		}
		$r_len = ord( $der[ $pos + 1 ] );
		$r     = substr( $der, $pos + 2, $r_len );
		$pos  += 2 + $r_len;

		// Read S
		if ( ord( $der[ $pos ] ) !== 0x02 ) {
			return $der;
		}
		$s_len = ord( $der[ $pos + 1 ] );
		$s     = substr( $der, $pos + 2, $s_len );

		// Strip leading null bytes or pad to 32 bytes
		$r = ltrim( $r, "\x00" );
		$s = ltrim( $s, "\x00" );
		$r = str_pad( $r, 32, "\x00", STR_PAD_LEFT );
		$s = str_pad( $s, 32, "\x00", STR_PAD_LEFT );

		return $r . $s;
	}

	/**
	 * Match existing user or create a new user account
	 *
	 * @param string $provider    'google' or 'apple'.
	 * @param string $provider_id Social user ID (sub).
	 * @param string $email       User email address.
	 * @param string $name        Full name.
	 * @param string $first_name  First name.
	 * @param string $last_name   Last name.
	 * @param string $avatar_url  Profile photo URL.
	 * @return int|WP_Error User ID on success, WP_Error on failure.
	 */
	public function match_or_create_user( $provider, $provider_id, $email, $name = '', $first_name = '', $last_name = '', $avatar_url = '' ) {
		$meta_key = '_dlm_' . sanitize_key( $provider ) . '_id';

		// 1. Match by provider user ID
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Match existing user by social provider ID.
		$existing_provider_users = get_users( array(
			'meta_key'   => $meta_key,
			'meta_value' => $provider_id,
			'number'     => 1,
			'fields'     => 'ID',
		) );

		if ( ! empty( $existing_provider_users ) ) {
			$user_id = $existing_provider_users[0];
			if ( ! empty( $avatar_url ) && ! get_user_meta( $user_id, 'dlm_avatar_url', true ) ) {
				update_user_meta( $user_id, 'dlm_avatar_url', esc_url_raw( $avatar_url ) );
			}
			return $user_id;
		}

		// 2. Match by email
		$existing_user = get_user_by( 'email', $email );
		if ( $existing_user ) {
			update_user_meta( $existing_user->ID, $meta_key, sanitize_text_field( $provider_id ) );
			if ( ! empty( $avatar_url ) && ! get_user_meta( $existing_user->ID, 'dlm_avatar_url', true ) ) {
				update_user_meta( $existing_user->ID, 'dlm_avatar_url', esc_url_raw( $avatar_url ) );
			}
			return $existing_user->ID;
		}

		// 3. Create fresh WordPress user
		$base_username = sanitize_user( current( explode( '@', $email ) ), true );
		if ( empty( $base_username ) ) {
			$base_username = 'member';
		}

		$username = $base_username;
		$suffix = 1;
		while ( username_exists( $username ) ) {
			$username = $base_username . '_' . $suffix;
			$suffix++;
		}

		$random_password = wp_generate_password( 24, true, true );

		$user_id = wp_create_user( $username, $random_password, $email );
		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		// Set display name and profile fields
		$display_name = ! empty( $name ) ? $name : $username;
		wp_update_user( array(
			'ID'           => $user_id,
			'display_name' => $display_name,
			'first_name'   => $first_name,
			'last_name'    => $last_name,
		) );

		// Set default role and capabilities
		$user = new WP_User( $user_id );
		$user->set_role( 'subscriber' );
		$user->add_cap( 'read_dlm_library' );

		// Store social link and avatar
		update_user_meta( $user_id, $meta_key, sanitize_text_field( $provider_id ) );
		if ( ! empty( $avatar_url ) ) {
			update_user_meta( $user_id, 'dlm_avatar_url', esc_url_raw( $avatar_url ) );
		}

		// Clear analytics caches
		delete_transient( 'dlm_analytics_summary' );
		delete_transient( 'dlm_trending_books' );

		return $user_id;
	}

	/**
	 * Authenticate user and issue redirect
	 *
	 * @param int    $user_id     WordPress user ID.
	 * @param string $redirect_to Destination URL.
	 */
	private function authenticate_and_redirect( $user_id, $redirect_to = '' ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			wp_safe_redirect( dlm_get_page_url( 'account' ) );
			exit;
		}

		wp_clear_auth_cookie();
		wp_set_current_user( $user_id, $user->user_login );
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		do_action( 'wp_login', $user->user_login, $user );

		if ( empty( $redirect_to ) || ! wp_validate_redirect( $redirect_to, false ) ) {
			$redirect_to = dlm_get_page_url( 'account' );
		}

		wp_safe_redirect( $redirect_to );
		exit;
	}
}
// phpcs:enable WordPress.DB.SlowDBQuery
