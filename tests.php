<?php

require_once 'mailchimp.php';

class MailchimpTest extends PHPUnit_Framework_TestCase
{
	/**
	 * The MailChimp instance
	 *
	 * @var Object
	 */
	private $mc;

	/**
	 * The constants
	 *
	 * @var	string
	 */
	private $apiKey = 'yourapikey';
	private $apiURL = 'http://api.mailchimp.com/1.3/?output=php';
	private $defaultList = 'yourdefaultlist';
	private $testEmail = 'your@email.com';

	public function setUp()
	{
		parent::setUp();

		$this->mc = new MailChimp($this->apiKey);
	}

	public function testAPIKey()
	{
		// gets the api key
		$this->assertEquals($this->mc->getAPIKey(), $this->apiKey);

		// sets the new api key and checks it
		$this->mc->setAPIKey('ditiseentest');
		$this->assertEquals($this->mc->getAPIKey(), 'ditiseentest');
	}

	public function testConnection()
	{
		// tetst the connection
		$this->assertTrue($this->mc->testConnection());
	}

	public function testLists()
	{
		$this->assertArrayHasKey('total', $this->mc->getLists());
	}

	public function testSubscribe()
	{
		$this->assertTrue($this->mc->subscribeEmail($this->testEmail, $this->defaultList));
		$this->assertTrue($this->mc->subscribeEmail($this->testEmail, $this->defaultList, true));
	}

	public function testURL()
	{
		// the basic url
		$this->assertArrayHasKey('host', $this->mc->getURL());
	}
}