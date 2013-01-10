<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Mandrill
{

	private $CI;

	public $name = 'Mandrill';

	public function  __construct()
	{

		$this->CI =& get_instance();
		
		// Load our language
		$this->CI->lang->load('newsletters/drivers/mandrill');
	}
	
	// --------------------------------------------------------------------------

	/**
	 * Install the driver's settings
	 *
	 * @access public
	 * @return bool
	 */
	function install()
	{
		// Remove any old settings
		$this->CI->db->delete('settings', array('module' => 'mandrill'));

		$settings = array(
			array(
				'slug' => 'mandrill_status',
				'title' => lang('mandrill:status'),
				'description' => '',
				'`default`' => '1',
				'`value`' => '1',
				'type' => 'select',
				'`options`' => lang('mandrill:status:options'),
				'is_required' => 1,
				'is_gui' => 0,
				'module' => 'mandrill'
				),
			array(
				'slug' => 'mandrill_api_key',
				'title' => lang('mandrill:api_key'),
				'description' => lang('mandrill:api_key:description'),
				'`default`' => '',
				'`value`' => '',
				'type' => 'text',
				'`options`' => '',
				'is_required' => 0,
				'is_gui' => 1,
				'module' => 'mandrill'
				),
			array(
				'slug' => 'mandrill_webhook_key',
				'title' => lang('mandrill:webhook_key'),
				'description' => lang('mandrill:webhook_key:description'),
				'`default`' => rand_string(10),
				'`value`' => rand_string(10),
				'type' => 'text',
				'`options`' => '',
				'is_required' => 0,
				'is_gui' => 1,
				'module' => 'mandrill'
				),
			);// Eof settings
		
		// Try installing the settings
		foreach ($settings as $k=>&$setting)
		{
			log_message('debug', '-- Settings: installing '.$setting['slug']);

			// Push to the last or something
			$setting['order'] = 99 - $k;

			if ( ! $this->CI->db->insert('settings', $setting))
			{
				log_message('debug', '-- -- could not install '.$setting['slug']);
				return false;
			}
		}

		return true;
	}
	
	// --------------------------------------------------------------------------

	/**
	 * Uninstall the driver's settings
	 *
	 * @access public
	 * @return bool
	 */
	function uninstall()
	{
		return $this->CI->db->delete('settings', array('module' => 'mandrill'));
	}

	// --------------------------------------------------------------------------

	/**
	 * Send using Mandrill!
	 *
	 * @access public
	 * @return object
	 */
	function send($message, $data)
	{
		/*
		 * Build object for their RESTful API
		 */

		$json = array(
			'key' => $this->CI->settings->mandrill_api_key,
			'type' => 'messages',
			'call' => 'send',
			'message' => array(
				'html' => $message,
				'text' => $this->CI->parser->parse_string($data['newsletter']->plain_text, $data, $data, true),
				'subject' => $this->CI->parser->parse_string($data['newsletter']->message_subject, $data, true),
				'from_email' => $data['newsletter']->from_email,
				'from_name' => $data['newsletter']->from_name,
				'to' => array(
					array('email' => $data['subscriber']->email['email_address'])
					),
				'track_opens' => false,
				'track_clicks' => false,
				'auto_text' => false,
				'url_strip_qs' => false,
				'recipient_metadata' => array(
					array(
						'rcpt' => $data['subscriber']->email['email_address'],
						'values' => array(
							'newsletter_str_id' => $data['newsletter']->str_id,
							'subscriber_str_id' => $data['subscriber']->str_id,
							'queue' => $data['queue']->id,
							'siteref' => SITE_REF
							),
						),
					),
				),
			'async' => true,	// Sends faster
			);



		/*
		 * Setup our cURL call to Mandrill
		 */

		// Get started
		$curl 	= curl_init('https://mandrillapp.com/api/1.0/messages/send.json');

		// Tell curl to use HTTP POST
		curl_setopt($curl, CURLOPT_POST, true);

		// Don't return headers
		curl_setopt($curl, CURLOPT_HEADER, false);

		// Set the POST arguments to pass on
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($json));

		// Exucute it
		$response = curl_exec($curl);
		
		curl_close($curl); 



		/*
		 * Format the response a bit
		 */

		$response = json_decode($response);

		$response = (is_array($response) ? $response[0] : $response);



		/*
		 * Prep a return from the driver
		 * - Status = success|error
		 * - Error message
		 */
		$return = array(
			'status' => ($response->status == 'error' ? 'error' : 'success'),
			'message' => ($response->status == 'error' ? $response->message : null),
			);

		return (object) $return;
	}
}
