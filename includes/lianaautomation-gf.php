<?php
/**
 * LianaAutomation Gravity Forms handler
 *
 * PHP Version 7.4
 *
 * @package  LianaAutomation
 * @license  https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL-3.0-or-later
 * @link     https://www.lianatech.com
 */

/**
 * Gravity Forms functionality. Sends the information to Automation API.
 *
 * See https://docs.gravityforms.com/gform_after_submission/ for tips.
 *
 * @param mixed $entry Entry Object - The entry that was just created.
 * @param mixed $form  Form Object  - The current form.
 *
 * @return bool
 */
function liana_automation_gf( $entry, $form ):bool {
	// Gets liana_t tracking cookie if set.
	if ( isset( $_COOKIE['liana_t'] ) ) {
		$liana_t = sanitize_key( $_COOKIE['liana_t'] );
	} else {
		// We shall send the form even without tracking cookie data.
		$liana_t = null;
	}

	// Extract the form data to Automation compatible array.
	$gravity_forms_array = array();

	// Try to find an email address from the form data.
	$email = null;

	// Try to find an email address from the form data.
	$sms = null;

	// Inspired by the example from https://docs.gravityforms.com/gform_after_submission/#h-4-access-the-entry-by-looping-through-the-form-fields.
	// Inspired also by the ideas from https://www.php.net/manual/en/function.array-key-exists.php.
	foreach ( $form['fields'] as $field ) {
		$inputs = $field->get_entry_inputs();
		if ( is_array( $inputs ) ) {
			foreach ( $inputs as $input ) {
				$value = rgar( $entry, (string) $input['id'] );
				// Do something with the value!
				if ( ! empty( $value ) ) {
					if ( empty( $email ) ) {
						if ( preg_match( '/email/i', $field->label ) || 'email' === $field->type ) {
							$email = $value;
						}
					}
					if ( empty( $sms ) ) {
						if ( preg_match( '/phone/i', $field->label ) ) {
							$sms = $value;
						}
					}
					if ( ! array_key_exists( $field->label, $gravity_forms_array ) ) {
						$gravity_forms_array[ $field->label ] = $value;
					} else {
						$gravity_forms_array[ $field->label ] .= ',' . $value;
					}
				}
			}
		} else {
			$value = rgar( $entry, (string) $field->id );
			// Do something with the $value!
			if ( ! empty( $value ) ) {
				// If we still don't have email, try to get it here.
				if ( empty( $email ) ) {
					if ( preg_match( '/email/i', $field->label ) || 'email' === $field->type ) {
						$email = $value;
					}
				}
				if ( empty( $sms ) ) {
					if ( preg_match( '/phone/i', $field->label ) ) {
						$sms = $value;
					}
				}
				// If nonempty multivalue, convert to commaseparated.
				if ( ! array_key_exists( $field->label, $gravity_forms_array ) ) {
					$gravity_forms_array[ $field->label ] = $value;
				} else {
					$gravity_forms_array[ $field->label ] .= ',' . $value;
				}
			}
		}
	}

	// Add Gravity Forms 'magic' values for title and id.
	$gravity_forms_array['formtitle'] = $form['title'];
	$gravity_forms_array['formid']    = $form['id'];

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
		// phpcs:disable WordPress.PHP.DevelopmentFunctions
		error_log( 'lianaautomation_gf: gravity_forms_array:' );
		error_log( print_r( $gravity_forms_array, true ) );
		// phpcs:enable
	}

	if ( empty( $email ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
			// phpcs:disable WordPress.PHP.DevelopmentFunctions
			error_log( 'ERROR: No /email/i found on form data. Bailing out.' );
			// phpcs:enable
		}
		return false;
	}

	/**
	* Retrieve Liana Options values (Array of All Options). Bail out if empty.
	*/
	$lianaautomation_gf_options = get_option( 'lianaautomation_gf_options' );
	if ( empty( $lianaautomation_gf_options ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
			// phpcs:disable WordPress.PHP.DevelopmentFunctions
			error_log( 'lianaautomation_gf_options was empty' );
			// phpcs:enable
		}
		return false;
	}

	// The user id, integer. Bail out if empty.
	if ( empty( $lianaautomation_gf_options['lianaautomation_user'] ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
			// phpcs:disable WordPress.PHP.DevelopmentFunctions
			error_log( 'lianaautomation_options lianaautomation_user was empty' );
			// phpcs:enable
		}
		return false;
	}
	$user = $lianaautomation_gf_options['lianaautomation_user'];

	// Hexadecimal secret string. Bail out if empty.
	if ( empty( $lianaautomation_gf_options['lianaautomation_key'] ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
			// phpcs:disable WordPress.PHP.DevelopmentFunctions
			error_log( 'lianaautomation_gf_options lianaautomation_key was empty!' );
			// phpcs:enable
		}
		return false;
	}
	$secret = $lianaautomation_gf_options['lianaautomation_key'];

	// The base url for our API installation. Bail out if empty.
	if ( empty( $lianaautomation_gf_options['lianaautomation_url'] ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
			// phpcs:disable WordPress.PHP.DevelopmentFunctions
			error_log( 'lianaautomation_gf_options lianaautomation_url was empty!' );
			// phpcs:enable
		}
		return false;
	}
	$url = $lianaautomation_gf_options['lianaautomation_url'];

	// The realm of our API installation, all caps alphanumeric string. Bail out if empty.
	if ( empty( $lianaautomation_gf_options['lianaautomation_realm'] ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
			// phpcs:disable WordPress.PHP.DevelopmentFunctions
			error_log( 'lianaautomation_gf_options lianaautomation_realm was empty!' );
			// phpcs:enable
		}
		return false;
	}
	$realm = $lianaautomation_gf_options['lianaautomation_realm'];

	// The channel ID of our automation. Bail out if empty.
	if ( empty( $lianaautomation_gf_options['lianaautomation_channel'] ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
			// phpcs:disable WordPress.PHP.DevelopmentFunctions
			error_log( 'lianaautomation_gf_options lianaautomation_channel was empty!' );
			// phpcs:enable
		}
		return false;
	}
	$channel = $lianaautomation_gf_options['lianaautomation_channel'];

	/**
	* General variables
	*/
	$base_path    = 'rest';             // Base path of the api end points.
	$content_type = 'application/json'; // Content will be send as json.
	$method       = 'POST';             // Method is always POST.

	// Build the identity array!
	$identity = array();
	if ( ! empty( $email ) ) {
		$identity['email'] = $email;
	}
	if ( ! empty( $liana_t ) ) {
		$identity['token'] = $liana_t;
	}
	if ( ! empty( $sms ) ) {
		$identity['sms'] = $sms;
	}

	// Bail out if no identities found!
	if ( empty( $identity ) ) {
		return false;
	}

	// Import Data!
	$path = 'v1/import';

	$data = array(
		'channel'       => $channel,
		'no_duplicates' => false,
		'data'          => array(
			array(
				'identity' => $identity,
				'events'   => array(
					array(
						'verb'  => 'formsend',
						'items' => $gravity_forms_array,
					),
				),
			),
		),
	);

	// Encode our body content data.
	$data = wp_json_encode( $data );
	// Get the current datetime in ISO 8601.
	$date = gmdate( 'c' );
	// md5 hash our body content.
	$content_md5 = md5( $data );
	// Create our signature.
	$signature_content = implode(
		"\n",
		array(
			$method,
			$content_md5,
			$content_type,
			$date,
			$data,
			"/{$base_path}/{$path}",
		),
	);

	$signature = hash_hmac( 'sha256', $signature_content, $secret );

	// Create the authorization header value.
	$auth = "{$realm} {$user}:" . $signature;

	// Create our full stream context with all required headers.
	$ctx = stream_context_create(
		array(
			'http' => array(
				'method'  => $method,
				'header'  => implode(
					"\r\n",
					array(
						"Authorization: {$auth}",
						"Date: {$date}",
						"Content-md5: {$content_md5}",
						"Content-Type: {$content_type}",
					)
				),
				'content' => $data,
			),
		)
	);

	// Build full path.
	$full_path = "{$url}/{$base_path}/{$path}";

	// Open a data stream.
	$fp = fopen( $full_path, 'rb', false, $ctx );

	// If LianaAutomation API settings is invalid or endpoint is not working properly, bail out.
	if ( ! $fp ) {
		return false;
	}
	// Handle the response.
	$response = stream_get_contents( $fp );
	// Decode the json response.
	$response = json_decode( $response, true );

	return true;
}

add_action( 'gform_after_submission', 'liana_automation_gf', 10, 2 );
