<?php
/**
 * Connect to BugHerd using their api and download project information to integrate
 *
 * @package WordPress
 * @subpackage wp-bugherd
 * @since 1.0.0.0
 */


class Bugherd_Client
{
	protected static $_username;
	protected static $_password;
	protected static $_is_authenticated;
	protected static $_projects;
	protected static $_errors;
	
	/**
	 * Login user
	 * BugHerd api do not provide any method to test credentials however we can try to download user project information
	 * 
	 * @param  string $username username to access bugherd account
	 * @param  string $password password
	 * @access public
	 * @since 1.0.0.0
	 * @return mixed WP_Error|true
	 */	
	public static function login($username, $password)
	{
		if(!Bugherd_Client::$_is_authenticated)
		{
			Bugherd_Client::$_username = $username;
			Bugherd_Client::$_password = $password;
			
			Bugherd_Client::$_projects = Bugherd_Client::api('projects','GET');
			if ( is_wp_error(Bugherd_Client::$_projects) )
			{
				Bugherd_Client::$_errors[] = Bugherd_Client::$_projects;
				return Bugherd_Client::$_projects;
			}else{
				Bugherd_Client::$_is_authenticated = true;
			}
		}
		
		return true;
	}
	
	/**
	 * Get list of errors
	 *
	 * @access public
	 * @since 1.0.0.0
	 * @return array
	 */
	public static function get_errors()
	{
		return Bugherd_Client::$_errors;
	}	
	
	/**
	 * Download list of existing projects
	 * 
	 * @access public
	 * @since 1.0.0.0
	 * @return array
	 */	
	public static function get_projects()
	{
		if(Bugherd_Client::$_is_authenticated)
		{		
			Bugherd_Client::$_projects = Bugherd_Client::api('projects','GET');
			return Bugherd_Client::$_projects;
		}
	}
	
	/**
	 * Add new project
	 *
	 * @param  string $project_name Default is sitename.
	 * @param  string $project_url Default is wordpress blog url.
	 * @param  string $project_webhook not implemented yet.
	 * @access public
	 * @since 1.0.0.0
	 * @return array $project_information
	 */
	public static function add_project($project_name = "", $project_url = "", $project_webhook = "")
	{
		if(Bugherd_Client::$_is_authenticated)
		{
			if(empty($project_name)) $project_name = get_bloginfo("sitename");
			if(empty($project_url)) $project_url = get_bloginfo("wpurl");
			
			$xml = sprintf('<?xml version="1.0" encoding="UTF-8"?>
				    <project>
						    <name>%s</name>
						    <devurl>%s</devurl>
						    <webhook>%s</webhook>
				    </project>', $project_name, $project_url, $project_webhook);
    
			$project_detail = Bugherd_Client::api('projects','POST', $xml);
			return $project_detail;
		}
	}	
	
	/**
	 * Query BugHerd server to fetch requested information
	 *
	 * @param  string $url part or full url
	 * @param  string $method HTTP Method GET/POST
	 * @param  string $xml for invoking api new request like new project, new user etc
	 * @access public
	 * @since 1.0.0.0
	 * @return mixed WP_Error|array
	 */	
	protected static function api($url, $method="POST", $xml = null)
	{
		global $wp_version;
		
		//Create a API URL
		$api_url = "https://www.bugherd.com/api_v1/";
		
		//we are not using json format as BugHerd API do not provide accurate information for invalid credentials.
		if(strpos($url, ".xml") === false) 
			$url = $url.".xml";
		
		if(strpos($url, "http://") === false)
			$url = $api_url.$url;
		
		//HTTP Auth
		$auth = Bugherd_Client::$_username.":".Bugherd_Client::$_password;
		$auth = base64_encode($auth);
		$headers = array(
							"Authorization" => "Basic $auth"
						);
		
		
		//Compose request
		$args = array(
						'method' => $method,
						'headers' => $headers
					);
		
		if(!empty($xml))
		{
			$args['headers']['Content-Type'] = "application/xml";
			$args['body'] = $xml;
		}
		
		$return = null;
		$response = @wp_remote_request($url, $args);
		$response_code = wp_remote_retrieve_response_code($response);
		
		if ( is_wp_error($response) ) 
		{
			$return = new WP_Error('wp-bugherd-client-http', __( 'An unexpected error occurred. Something may be wrong with bugherd.com or this server&#8217;s configuration. If you continue to have problems, please try to check your internet configurations.','wp-bugherd' ), $response->get_error_message() );
		}else if ( in_array($response_code, array(200, 401, 500)))
		{
			//Parsing Result
			$response_body = wp_remote_retrieve_body($response);
			
			//Fixing decompressing bug in wordpress 2.9 or earlier
			if (version_compare( $wp_version, '3.0', '<' ) ) 
			{
				if(@strpos($response_body, 'xml') == false)
				{
					$response_body = @gzinflate($response_body);
				}
			}
			
			$xml = @simplexml_load_string($response_body);
			
			
			if($xml === false)
			{
				$return = new WP_Error('wp-bugherd-client-parsing', __( 'Unable to parse xml result returned by BugHerd server.','wp-bugherd' ), $xml );
			}else{
				
				//In case our request was not successful
				if(isset($xml->error))
				{
					if (is_array($xml->error))
					{
							
						$errors = "";
						foreach ($xml->error as $error)
						{
							$errors .= (string) $error;
							$errors .= PHP_EOL;
						}
						$return = new WP_Error('wp-bugherd-client-error', $errors, Bugherd_Client::$_username);	
					}else{
						$error = (string) $xml->error;
						$return = new WP_Error('wp-bugherd-client-error', $error, Bugherd_Client::$_username);
					}
					
				}else{
					
					//we have found data
					$return = array();
					$root_name = $xml->getName();

					$json_2_array  = (array) json_decode(json_encode((array) $xml), 1);
					unset($json_2_array['@attributes']);
					
					if(array_key_exists($root_name, $json_2_array))
					{
						$return = $json_2_array[$root_name];
					}else{
						foreach($json_2_array as $k=>$item)
						{
							if(!is_numeric($k))
							{
								$return[$k] = $item;
							}else{
								$return[] = $item;
							}
						}
					}
					
				}
				
			}
		}
		
		return $return;
	}
}




?>
