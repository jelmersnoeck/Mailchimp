<?php

/**
 * MailChimp class
 *
 * This source file can be used to communicate with MailChimp.
 *
 * @author		Jelmer Snoeck <jelmer.snoeck@netlash.com>
 * @version		1.0.0
 */
class MailChimp
{
	/**
	 * Show error codes?
	 *
	 * @var	bool
	 */
	const DEBUG = true;


	/**
	 * Double opt in or not?
	 *
	 * @var	bool
	 */
	const DOUBLE_OPTIN = false;


	/**
	 * The API Version
	 */
	const VERSION = '1.0.0';


	/**
	 * The API Key
	 *
	 * @var string
	 */
	private $apiKey;


	/**
	 * The API URL
	 *
	 * @var	string
	 */
	private $apiURL;


	/**
	 * The MailChimp API Version
	 *
	 * This will be used in the URL.
	 *
	 * @var	string
	 */
	private $apiVersion = '1.3';


	/**
	 * The list ID
	 *
	 * @var	string
	 */
	private $listID;


	/**
	 * The constructor
	 *
	 * @return	void
	 * @param	string $apiKey			The API key.
	 * @param	string[optional] $URL	The URL to call to.
	 */
	public function __construct($apiKey, $URL = null)
	{
		$this->setAPIKey($apiKey);
		$this->setURL($URL);
	}


	/**
	 * Builds a queryString from an array
	 *
	 * @return	string
	 * @param	array $parameters		The parameters.
	 */
	private function buildQueryString(array $parameters)
	{
		// the querystring
		$queryString = '';

		// loop the parameters
		foreach($parameters as $parameter => $value)
		{
			// build the temporary string
			if(is_array($value)) $tmpString = $this->buildQueryString($value);
			else $tmpString = '&' . $parameter . '=' . utf8_decode($value);

			// add it to the querystring
			$queryString.= $tmpString;
		}

		// return
		return $queryString;
	}


	/**
	 * Does a call to the server
	 *
	 * @return	mixed
	 * @param	string $method					What are you requesting?
	 * @param	array[optional] $parameters		The parameters of your request.
	 */
	private function doCall($method, array $parameters = array())
	{
		// standard dataCenter
		$dataCenter = 'us1';

		// is the dataCenter provided in the API Key?
		if(strstr($this->getAPIKey(), '-'))
		{
			// set the dataCenter (apikey-DC)
			$dataCenter = explode('-', $this->getAPIKey());
			$dataCenter = $dataCenter[1];
		}

		// add the api key to the parameters
		$parameters['apikey'] = $this->getAPIKey();

		// get the URL (for easy use)
		$apiURL = $this->getURL();
		$callURL = $apiURL['scheme'] . '://' . $dataCenter . '.' . $apiURL['host'] . $apiURL['path'] . '?';

		// the querystring
		$queryString = 'output=php&method=' . $method;

		// any parameters given?
		if(!empty($parameters)) $queryString.= $this->buildQueryString($parameters);

		$callURL.= $queryString;

		// set curl options
		$cOptions[CURLOPT_URL] = $callURL;
		$cOptions[CURLOPT_RETURNTRANSFER] = true;
		$cOptions[CURLOPT_TIMEOUT] = 30;

		// start curl request
		$curl = curl_init();

		// set the options
		curl_setopt_array($curl, $cOptions);

		// execute
		$response = unserialize(curl_exec($curl));
		$headers = curl_getinfo($curl);

		// errors?
		if(is_array($response) && array_key_exists('error', $response))
		{
			// user already exists use custom error handling
			if($response['code'] == 214) return false;

			// print the errors?
			if(self::DEBUG)
			{
				echo '<pre>';

				// dump the header information
				var_dump($headers);

				// dump the respons
				var_dump($response);

				echo '</pre>';
			}

			// throw new exception
			throw new MailChimpException(null, (int) $headers['http_code']);
		}
		// close the curl request
		curl_close($curl);

		// return
		return $response;
	}


	/**
	 * This gets the API Key
	 *
	 * @return	string
	 */
	public function getAPIKey()
	{
		return $this->apiKey;
	}


	/**
	 * Gets the list ID
	 *
	 * @return	string
	 */
	public function getListID()
	{
		return $this->listID;
	}


	/**
	 * Gets the lists from the server
	 *
	 * Filter options:
	 * string list_id optional - return a single list using a known list_id. Accepts multiples separated by commas when not using exact matching
	 * string from_name optional - only lists that have a default from name matching this
	 * string from_email optional - only lists that have a default from email matching this
	 * string from_subject optional - only lists that have a default from email matching this
	 * string created_before optional - only show lists that were created before this date/time (in GMT) - format is YYYY-MM-DD HH:mm:ss (24hr)
	 * string created_after optional - only show lists that were created since this date/time (in GMT) - format is YYYY-MM-DD HH:mm:ss (24hr)
	 * boolean exact optional - flag for whether to filter on exact values when filtering, or search within content for filter values - defaults to true
	 *
	 * @return	mixed
	 * @param	array[optional] $filters		The filters to apply.
	 * @param	int[optional] $offset			The offset from the items.
	 * @param	int[optional] $limit			The limit of items to get.
	 */
	public function getLists($filters = array(), $offset = 0, $limit = 25)
	{
		// set the parameters
        $parameters = array();
        $parameters['offset'] = $offset;
        $parameters['limit'] = $limit;
        $parameters['filters'] = $filters;

        // do the call
        return $this->doCall('lists', $parameters);
	}


	/**
	 * This gets the API URL
	 *
	 * @return	string
	 */
	public function getURL()
	{
		return $this->apiURL;
	}


	/**
	 * This sets the API Key
	 *
	 * @return	void
	 * @param	string $apiKey		The API Key.
	 */
	public function setAPIKey($apiKey)
	{
		$this->apiKey = (string) $apiKey;
	}


	/**
	 * Sets the list ID (This can be found on the mailchimp settings page)
	 *
	 * @return	void
	 * @param	string $listID		The list ID.
	 */
	public function setListID($listID)
	{
		$this->listID = (string) $listID;
	}


	/**
	 * This sets the URL
	 *
	 * @return	void
	 * @param	string $URL		The URL.
	 */
	public function setURL($URL)
	{
		// set the base URL
		if($URL === null) $tmpURL = 'http://api.mailchimp.com/' . $this->apiVersion . '/';
		else
		{
			// set parse url
			$this->apiURL = parse_url($URL);

			// test the connection
			$connection = $this->testConnection();

			// the URL is bad
			if(!$connection) throw new MailChimpException('Invalid URL. Please provide a valid API URL.');

			// set url
			$tmpURL = $URL;
		}

		// parse it as an URL, to get more info
		$this->apiURL = parse_url($tmpURL);
	}


	/**
	 * This subscribes an email to a list
	 *
	 * @return	mixed
	 * @param	string $email		The users email.
	 * @param	int $listID			The list ID to subscribe to.
	 * @param	bool $welcome		Should we send a welcome message?
	 */
	public function subscribeEmail($email, $list, $welcome = false)
	{
		// parameters
		$parameters = array();
		$parameters['id'] = $list;
		$parameters['email_address'] = $email;
		$parameters['double_optin'] = self::DOUBLE_OPTIN;
		$parameters['send_welcome'] = $welcome;

		// return
		return (bool) $this->doCall('listSubscribe', $parameters);
	}


	/**
	 * The test function
	 *
	 * @return	bool
	 */
	public function testConnection()
	{
		// do a ping
		$response = $this->doCall('ping');

		// return
		return (bool) ($response == "Everything's Chimpy!");
	}
}


/**
 * MailChimpException class
 *
 * @author	Jelmer Snoeck <jelmer.snoeck@netlash.com>
 */
class MailChimpException extends Exception
{
	/**
	 * Header codes
	 *
	 * @var	array
	 */
	private $errorCodes = array(
							'-32601' => 'Invalid method',
							'-32602' => 'Invalid parameters',
							'-99' => 'Unknown exception',
							'-98' => 'Request timeout',
							'-92' => 'URI Exception',
							'-91' => 'Database Exception',
							'-90' => 'XML RPC2 Exception',
							'-50' => 'Too many connections',
							'0' => 'Parse Exception',
							'100' => 'User unknown',
							'101' => 'User disabled',
							'102' => 'User does not exist',
							'103' => 'User not approved',
							'104' => 'Invalid API Key',
							'105' => 'Under maintenance',
							'106' => 'Invalid App Key',
							'107' => 'Invalid IP',
							'108' => 'Users does exist',
							'120' => 'Invalid action',
							'121' => 'Missing Email',
							'122' => 'Cannot send campaign',
							'123' => 'Missing module outbox',
							'124' => 'Module already purchased',
							'125' => 'Module not purchased',
							'126' => 'Not enough credits',
							'127' => 'Invalid payment',
							'200' => 'List does not exist',
							'211' => 'Invalid option',
							'212' => 'Invalid Unsubscribed member',
							'213' => 'Invalid bounce member',
							'214' => 'Already subscribed',
							'215' => 'Not subscribed',
							'220' => 'Invalid import',
							'221' => 'Pasted list duplicate',
							'222' => 'Pasted list invalid import',
							'230' => 'Already subscribed',
							'231' => 'Already unsubscribed',
							'232' => 'Email does not exist',
							'233' => 'Email not subscribed',
							'250' => 'Merge field required',
							'251' => 'Cannot remove email merge',
							'252' => 'Invalid merge ID',
							'253' => 'Too many merge fields',
							'254' => 'Invalid merge field',
							'270' => 'Invalid interest group',
							'271' => 'Too many interests groups',
							'300' => 'Campaign does not exist',
							'301' => 'Campaign stats not available',
							'310' => 'Invalid AB split',
							'311' => 'Invalid content',
							'312' => 'Invalid option',
							'313' => 'Invalid status',
							'314' => 'Campaign not saved',
							'315' => 'Invalid segment',
							'316' => 'Invalid RSS',
							'317' => 'Invalid auto',
							'318' => 'Invalid archive',
							'319' => 'Bounce missing',
							'330' => 'Invalid Ecomm Order',
							'350' => 'Unknown ABSplit error',
							'351' => 'Unknown ABSplit test',
							'352' => 'Unknown AB Test type',
							'353' => 'Unknown AB wait unit',
							'354' => 'Unknown AB winner type',
							'355' => 'AB Winner not selected',
							'500' => 'Invalid analytics',
							'501' => 'Invalid datetime',
							'502' => 'Invalid email',
							'503' => 'Invalid send type',
							'504' => 'Invalid template',
							'505' => 'Invalid tracking options',
							'506' => 'Invalid options',
							'507' => 'Invalid folder',
							'508' => 'Invalid URL',
							'550' => 'Module unknown',
							'551' => 'Montly plan unknown',
							'552' => 'Order type unknown',
							'553' => 'Invalid paging limit',
							'554' => 'Invalid paging start',
							'555' => 'Maximum size reached'
	);


	/**
	 * Default constructor
	 *
	 * @return	void
	 * @param	string[optional] $message		The error message.
	 * @param	int[optional] $code				The error code.
	 */
	public function __construct($message = null, $code = null)
	{
		// set the error message
		if($message === null && isset($this->errorCodes[(int) $code])) $message = $this->errorCodes[(int) $code];

		// call the parent
		parent::__construct($message, $code);
	}
}
