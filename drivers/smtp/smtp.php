<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Smtp
{

	private $CI;

	public $name = 'smtp';

	public function  __construct()
	{

		$this->CI =& get_instance();
		
		// Load our language
		$this->CI->lang->load('newsletters/drivers/smtp');
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
		$this->CI->db->delete('settings', array('module' => 'smtp'));

		$settings = array(
			array(
				'slug' => 'smtp_status',
				'title' => lang('smtp:status'),
				'description' => '',
				'`default`' => '1',
				'`value`' => '1',
				'type' => 'select',
				'`options`' => lang('smtp:status:options'),
				'is_required' => 1,
				'is_gui' => 0,
				'module' => 'smtp'
				),
			array(
				'slug' => 'smtp_host',
				'title' => lang('smtp:host'),
				'description' => lang('smtp:host:description'),
				'`default`' => '',
				'`value`' => '',
				'type' => 'text',
				'`options`' => '',
				'is_required' => 0,
				'is_gui' => 1,
				'module' => 'smtp'
				),
			array(
				'slug' => 'smtp_username',
				'title' => lang('smtp:username'),
				'description' => lang('smtp:username:description'),
				'`default`' => '',
				'`value`' => '',
				'type' => 'text',
				'`options`' => '',
				'is_required' => 0,
				'is_gui' => 1,
				'module' => 'smtp'
				),
			array(
				'slug' => 'smtp_password',
				'title' => lang('smtp:password'),
				'description' => lang('smtp:password:description'),
				'`default`' => '',
				'`value`' => '',
				'type' => 'text',
				'`options`' => '',
				'is_required' => 0,
				'is_gui' => 1,
				'module' => 'smtp'
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
		return $this->CI->db->delete('settings', array('module' => 'smtp'));
	}

	// --------------------------------------------------------------------------

	/**
	 * Send using smtp!
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
				'smtp_host' => $this->CI->settings->smtp_host,
				'smtp_user' => $this->CI->settings->smtp_username,
				'smtp_pass' => $this->CI->settings->smtp_password,
				)
			);



		/*
		 * Eh.. Whatever, we're sending SMTP.
		 * Be prepared to wait a little bit because 
		 * SMTP transmission has a lot of chatter between servers.
		 */

		// Build an email
		$this->CI->email->from($newsletter->from_email, $newsletter->from_name);
		$this->CI->email->to($recipient->email);
		$this->CI->email->subject($this->CI->newsletter->_replace_tags($newsletter->message_subject));
		$this->CI->email->message($message);
		$this->CI->email->set_alt_message($this->CI->newsletter->_replace_tags($newsletter->plain_text));

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
