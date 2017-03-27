<?php
/*
  Plugin Name: WP API Pretty JSON
  Plugin URI: http://www.eventespresso.com
  Description: Plugin for changing all JSON returned by the WP API into pretty JSON for easier debugging and development
  Version: 0.2.0
  Author: Event Espresso (Mike Nelson)
  Author URI: http://www.eventespresso.com
  Copyright 2014 Event Espresso (email : support@eventespresso.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA02110-1301USA
 *
 * ------------------------------------------------------------------------
 *
 * WP API Pretty JSON
 *
 *
 * @ package		Event Espresso
 * @ author		Event Espresso
 * @ copyright	(c) 2008-2014 Event Espresso  All Rights Reserved.
 * @ license		http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link			http://www.eventespresso.com
 * @ version	 	EE4
 *
 * ------------------------------------------------------------------------
 */
add_filter('rest_pre_serve_request','wp_api_pretty_json_serve_request', 10, 4);
function wp_api_pretty_json_serve_request(
        $served,
        WP_REST_Response $result,
        WP_REST_Request $request,
        WP_REST_Server $server
){

    //copy all this from WP_REST_Server::serve() in order to get the jsonp callback
    /**
     * Filters whether jsonp is enabled.
     *
     * @since 4.4.0
     *
     * @param bool $jsonp_enabled Whether jsonp is enabled. Default true.
     */
    $jsonp_enabled = apply_filters( 'rest_jsonp_enabled', true );

    $jsonp_callback = null;

    //copy all this from WP_REST_Server::serve() too in order to change the response to pretty
    if ( 'HEAD' === $request->get_method() ) {
        return null;
    }

    // Embed links inside the request.
    $result = $server->response_to_data( $result, isset( $_GET['_embed'] ) );

    $result = wp_api_pretty_json_prettify( $result );

    $json_error_message = wp_api_pretty_json_get_json_last_error();
    if ( $json_error_message ) {
        $json_error_obj = new WP_Error( 'rest_encode_error', $json_error_message, array( 'status' => 500 ) );
        $result = wp_api_pretty_json_error_to_response( $json_error_obj );
        $result = wp_api_pretty_json_prettify( $result->data[0] );
    }

    if ( $jsonp_callback ) {
        // Prepend '/**/' to mitigate possible JSONP Flash attacks.
        // https://miki.it/blog/2014/7/8/abusing-jsonp-with-rosetta-flash/
        echo '/**/' . $jsonp_callback . '(' . $result . ')';
    } else {
        echo $result;
    }

    return true;
}

function wp_api_pretty_json_get_json_last_error() {
    // See https://core.trac.wordpress.org/ticket/27799.
    if ( ! function_exists( 'json_last_error' ) ) {
        return false;
    }

    $last_error_code = json_last_error();

    if ( ( defined( 'JSON_ERROR_NONE' ) && JSON_ERROR_NONE === $last_error_code ) || empty( $last_error_code ) ) {
        return false;
    }

    return json_last_error_msg();
}

function wp_api_pretty_json_error_to_response( $error ) {
    $error_data = $error->get_error_data();

    if ( is_array( $error_data ) && isset( $error_data['status'] ) ) {
        $status = $error_data['status'];
    } else {
        $status = 500;
    }

    $errors = array();

    foreach ( (array) $error->errors as $code => $messages ) {
        foreach ( (array) $messages as $message ) {
            $errors[] = array( 'code' => $code, 'message' => $message, 'data' => $error->get_error_data( $code ) );
        }
    }

    $data = $errors[0];
    if ( count( $errors ) > 1 ) {
        // Remove the primary error.
        array_shift( $errors );
        $data['additional_errors'] = $errors;
    }

    $response = new WP_REST_Response( $data, $status );

    return $response;
}

/**
 * grabbed from  http://www.php.net/manual/en/function.json-encode.php#80339,
 * it will put the json in a more readable format
 *
 * @param string $json
 * @return string
 */
function wp_api_pretty_json_prettify($json_obj)
{
    $tab = "  ";
    $new_json = "";
    $indent_level = 0;
    $in_string = false;
    if ($json_obj === false) {
        return false;
    }
    $json = json_encode($json_obj);
    $len = strlen($json);
    for ($c = 0; $c < $len; $c++) {
        $char = $json[$c];
        switch ($char) {
            case '{':
            case '[':
                if (! $in_string) {
                    $new_json .= $char . "\n" . str_repeat($tab, $indent_level + 1);
                    $indent_level++;
                } else {
                    $new_json .= $char;
                }
                break;
            case '}':
            case ']':
                if (! $in_string) {
                    $indent_level--;
                    $new_json .= "\n" . str_repeat($tab, $indent_level) . $char;
                } else {
                    $new_json .= $char;
                }
                break;
            case ',':
                if (! $in_string) {
                    $new_json .= ",\n" . str_repeat($tab, $indent_level);
                } else {
                    $new_json .= $char;
                }
                break;
            case ':':
                if (! $in_string) {
                    $new_json .= ": ";
                } else {
                    $new_json .= $char;
                }
                break;
            case '"':
                if ($c > 0 && $json[$c - 1] != '\\') {
                    $in_string = ! $in_string;
                }
            default:
                $new_json .= $char;
                break;
        }
    }
    return $new_json;
}





