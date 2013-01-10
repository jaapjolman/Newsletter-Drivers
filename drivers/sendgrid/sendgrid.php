<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Sendgrid
{

	private $CI;

	public $name = 'Sendgrid';

	public function  __construct()
	{

		$this->CI =& get_instance();
		
		// Load our language
		$this->CI->lang->load('newsletters/drivers/sendgrid');
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
		$this->CI->db->delete('settings', array('module' => 'sendgrid'));

		$settings = array(
			array(
				'slug' => 'sendgrid_status',
				'title' => lang('sendgrid:status'),
				'description' => '',
				'`default`' => '1',
				'`value`' => '1',
				'type' => 'select',
				'`options`' => lang('sendgrid:status:options'),
				'is_required' => 1,
				'is_gui' => 0,
				'module' => 'sendgrid'
				),
			array(
				'slug' => 'sendgrid_username',
				'title' => lang('sendgrid:username'),
				'description' => lang('sendgrid:username:description'),
				'`default`' => '',
				'`value`' => '',
				'type' => 'text',
				'`options`' => '',
				'is_required' => 0,
				'is_gui' => 1,
				'module' => 'sendgrid'
				),
			array(
				'slug' => 'sendgrid_password',
				'title' => lang('sendgrid:password'),
				'description' => lang('sendgrid:password:description'),
				'`default`' => '',
				'`value`' => '',
				'type' => 'text',
				'`options`' => '',
				'is_required' => 0,
				'is_gui' => 1,
				'module' => 'sendgrid'
				),
			array(
				'slug' => 'sendgrid_events_key',
				'title' => lang('sendgrid:events_key'),
				'description' => lang('sendgrid:events_key:description'),
				'`default`' => rand_string(10),
				'`value`' => rand_string(10),
				'type' => 'text',
				'`options`' => '',
				'is_required' => 0,
				'is_gui' => 1,
				'module' => 'sendgrid'
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
		return $this->CI->db->delete('settings', array('module' => 'sendgrid'));
	}

	// --------------------------------------------------------------------------

	/**
	 * Send using sendgrid!
	 *
	 * @access public
	 * @return object
	 */
	function send($message, $data)
	{
		/*
		 * Initialize the email class
		 */

		$this->CI->email->initialize(
			array(
				'mailtype' => 'html',
				'charset' => 'UTF-8',
				'protocol' => 'smtp',
				'smtp_host' => 'smtp.sendgrid.net',
				'smtp_user' => $this->CI->settings->sendgrid_username,
				'smtp_pass' => $this->CI->settings->sendgrid_password,
				)
			);



		/*
		 * Eh.. Whatever, we're sending SMTP.
		 * Be prepared to wait a little bit because 
		 * SMTP transmission has a lot of chatter between servers.
		 */

		// Build the SMTP API Header
		$header = array('unique_args' => array('newsletter_str_id' => $newsletter->str_id, 'subscriber_str_id' => $recipient->str_id, 'queue' => $queue['entries'][0]['id'], 'siteref' => SITE_REF));

		// Build an email
		$this->CI->email->from($newsletter->from_email, $newsletter->from_name);
		$this->CI->email->to($recipient->email);
		$this->CI->email->subject($this->CI->newsletter->_replace_tags($newsletter->message_subject));
		$this->CI->email->message($message);
		$this->CI->email->set_alt_message($this->CI->newsletter->_replace_tags($newsletter->plain_text));

		// Build tracking headers and bounce (Return) Path
		$this->CI->email->set_header('X-APP-NEWSLETTER', $newsletter->str_id);
		$this->CI->email->set_header('X-APP-SUBSCRIBER', $recipient->str_id);
		$this->CI->email->set_header('X-APP-QUEUE', $queue['entries'][0]['id']);
		$this->CI->email->set_header('X-APP-SITEREF', SITE_REF);

		// Set the SMTPAPI header for Events (an app available from sendgrid)
		$this->CI->email->set_header('X-SMTPAPI', json_encode($header));

		// Send by Email class
		$status = $this->CI->email->send();



		/*
		 * Prep a return from the driver based on success
		 * - Status = success|error
		 * - Error message
		 */

		$return = array(
			'status' => ($status ? 'success' : 'error'),
			'message' => ($status ? null : $this->CI->email->print_debugger()),
			);

		return (object) $return;
	}
}
