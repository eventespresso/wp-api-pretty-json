<?php
/*
  Plugin Name: WP API Pretty JSON
  Plugin URI: http://www.eventespresso.com
  Description: Plugin for changing all JSON returned by the WP API into pretty JSON for easier debugging and development
  Version: 0.1.0
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
add_filter( 'json_serve_request', 'wp_api_pretty_json_serve_request', 10, 5 );
function wp_api_pretty_json_serve_request( $served, $result, $path, $method, $server ){
		if ( 'HEAD' === $method ) {
			return false;
		}

		$result = json_encode( $server->prepare_response( $result ) );

		$result = wp_api_pretty_json_prettify( $result );


		if ( isset( $_GET['_jsonp'] ) ) {
			// Prepend '/**/' to mitigate possible JSONP Flash attacks
			// http://miki.it/blog/2014/7/8/abusing-jsonp-with-rosetta-flash/
			echo '/**/' . $_GET['_jsonp'] . '(' . $result . ')';
		} else {
			echo $result;
		}
	return true;
}
/**
* grabbed from  http://www.php.net/manual/en/function.json-encode.php#80339,
* it will put the json in a more readable format
* @param string $json
* @return string
*/
function wp_api_pretty_json_prettify($json) {
		$tab = "  ";
		$new_json = "";
		$indent_level = 0;
		$in_string = false;

		$json_obj = json_decode($json);

		if($json_obj === false)
			return false;

		$json = json_encode($json_obj);
		$len = strlen($json);

		for($c = 0; $c < $len; $c++)
		{
			$char = $json[$c];
			switch($char)
			{
				case '{':
				case '[':
					if(!$in_string)
					{
						$new_json .= $char . "\n" . str_repeat($tab, $indent_level+1);
						$indent_level++;
					}
					else
					{
						$new_json .= $char;
					}
					break;
				case '}':
				case ']':
					if(!$in_string)
					{
						$indent_level--;
						$new_json .= "\n" . str_repeat($tab, $indent_level) . $char;
					}
					else
					{
						$new_json .= $char;
					}
					break;
				case ',':
					if(!$in_string)
					{
						$new_json .= ",\n" . str_repeat($tab, $indent_level);
					}
					else
					{
						$new_json .= $char;
					}
					break;
				case ':':
					if(!$in_string)
					{
						$new_json .= ": ";
					}
					else
					{
						$new_json .= $char;
					}
					break;
				case '"':
					if($c > 0 && $json[$c-1] != '\\')
					{
						$in_string = !$in_string;
					}
				default:
					$new_json .= $char;
					break;
			}
		}

		return $new_json;
	}