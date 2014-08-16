<?php
/**
 * Part of Windwalker project.
 *
 * @copyright  Copyright (C) 2008 - 2014 Asikart.com. All rights reserved.
 * @license    GNU General Public License version 2 or later;
 */

namespace Windwalker\Application\Web;

use Windwalker\Uri\Uri;

/**
 * Class to model a Web Client.
 *
 * @since  {DEPLOY_VERSION}
 */
class WebClient
{
	const WINDOWS = 1;
	const WINDOWS_PHONE = 2;
	const WINDOWS_CE = 3;
	const IPHONE = 4;
	const IPAD = 5;
	const IPOD = 6;
	const MAC = 7;
	const BLACKBERRY = 8;
	const ANDROID = 9;
	const LINUX = 10;
	const TRIDENT = 11;
	const WEBKIT = 12;
	const GECKO = 13;
	const PRESTO = 14;
	const KHTML = 15;
	const AMAYA = 16;
	const IE = 17;
	const FIREFOX = 18;
	const CHROME = 19;
	const SAFARI = 20;
	const OPERA = 21;
	const ANDROIDTABLET = 22;

	/**
	 * The detected platform on which the web client runs.
	 *
	 * @var    integer
	 * @since  {DEPLOY_VERSION}
	 */
	protected $platform;

	/**
	 * True if the web client is a mobile device.
	 *
	 * @var    boolean
	 * @since  {DEPLOY_VERSION}
	 */
	protected $mobile = false;

	/**
	 * The detected rendering engine used by the web client.
	 *
	 * @var    integer
	 * @since  {DEPLOY_VERSION}
	 */
	protected $engine;

	/**
	 * The detected browser used by the web client.
	 *
	 * @var    integer
	 * @since  {DEPLOY_VERSION}
	 */
	protected $browser;

	/**
	 * The detected browser version used by the web client.
	 *
	 * @var    string
	 * @since  {DEPLOY_VERSION}
	 */
	protected $browserVersion;

	/**
	 * The priority order detected accepted languages for the client.
	 *
	 * @var    array
	 * @since  {DEPLOY_VERSION}
	 */
	protected $languages = array();

	/**
	 * The priority order detected accepted encodings for the client.
	 *
	 * @var    array
	 * @since  {DEPLOY_VERSION}
	 */
	protected $encodings = array();

	/**
	 * The web client's user agent string.
	 *
	 * @var    string
	 * @since  {DEPLOY_VERSION}
	 */
	protected $userAgent;

	/**
	 * The web client's accepted encoding string.
	 *
	 * @var    string
	 * @since  {DEPLOY_VERSION}
	 */
	protected $acceptEncoding;

	/**
	 * The web client's accepted languages string.
	 *
	 * @var    string
	 * @since  {DEPLOY_VERSION}
	 */
	protected $acceptLanguage;

	/**
	 * True if the web client is a robot.
	 *
	 * @var    boolean
	 * @since  {DEPLOY_VERSION}
	 */
	protected $robot = false;

	/**
	 * An array of flags determining whether or not a detection routine has been run.
	 *
	 * @var    array
	 * @since  {DEPLOY_VERSION}
	 */
	protected $detection = array();

	/**
	 * Property uri.
	 *
	 * @var Uri
	 */
	protected $uri = null;

	/**
	 * Class constructor.
	 *
	 * @param   string  $userAgent       The optional user-agent string to parse.
	 * @param   string  $acceptEncoding  The optional client accept encoding string to parse.
	 * @param   string  $acceptLanguage  The optional client accept language string to parse.
	 *
	 * @since   {DEPLOY_VERSION}
	 */
	public function __construct($userAgent = null, $acceptEncoding = null, $acceptLanguage = null)
	{
		// If no explicit user agent string was given attempt to use the implicit one from server environment.
		if (empty($userAgent) && isset($_SERVER['HTTP_USER_AGENT']))
		{
			$this->userAgent = $_SERVER['HTTP_USER_AGENT'];
		}
		else
		{
			$this->userAgent = $userAgent;
		}

		// If no explicit acceptable encoding string was given attempt to use the implicit one from server environment.
		if (empty($acceptEncoding) && isset($_SERVER['HTTP_ACCEPT_ENCODING']))
		{
			$this->acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'];
		}
		else
		{
			$this->acceptEncoding = $acceptEncoding;
		}

		// If no explicit acceptable languages string was given attempt to use the implicit one from server environment.
		if (empty($acceptLanguage) && isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
		{
			$this->acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		}
		else
		{
			$this->acceptLanguage = $acceptLanguage;
		}
	}

	/**
	 * getSystemUri
	 *
	 * @param string $requestUri
	 * @param bool   $refresh
	 *
	 * @return  Uri
	 */
	public function getSystemUri($requestUri = null, $refresh = false)
	{
		if ($this->uri && !$refresh)
		{
			return $this->uri;
		}

		$requestUri = $requestUri ? : $this->detectRequestUri();

		// Start with the requested URI.
		$uri = new Uri($requestUri);

		// If we are working from a CGI SAPI with the 'cgi.fix_pathinfo' directive disabled we use PHP_SELF.
		if (strpos(php_sapi_name(), 'cgi') !== false && !ini_get('cgi.fix_pathinfo') && !empty($_SERVER['REQUEST_URI']))
		{
			// We aren't expecting PATH_INFO within PHP_SELF so this should work.
			$uri->setPath(rtrim(dirname($_SERVER['PHP_SELF']), '/\\'));
		}
		else
			// Pretty much everything else should be handled with SCRIPT_NAME.
		{
			$uri->setPath(rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'));
		}

		// Clear the unused parts of the requested URI.
		$uri->setQuery(null);
		$uri->setFragment(null);

		return $this->uri = $uri;
	}

	/**
	 * Method to detect the requested URI from server environment variables.
	 *
	 * @return  string  The requested URI
	 *
	 * @since   {DEPLOY_VERSION}
	 */
	public function detectRequestUri()
	{
		// First we need to detect the URI scheme.
		if ($this->isSSLConnection())
		{
			$scheme = 'https://';
		}
		else
		{
			$scheme = 'http://';
		}

		/*
		 * There are some differences in the way that Apache and IIS populate server environment variables.  To
		 * properly detect the requested URI we need to adjust our algorithm based on whether or not we are getting
		 * information from Apache or IIS.
		 */

		// If PHP_SELF and REQUEST_URI are both populated then we will assume "Apache Mode".
		if (!empty($_SERVER['PHP_SELF']) && !empty($_SERVER['REQUEST_URI']))
		{
			// The URI is built from the HTTP_HOST and REQUEST_URI environment variables in an Apache environment.
			$uri = $scheme . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		}
		else
			// If not in "Apache Mode" we will assume that we are in an IIS environment and proceed.
		{
			// IIS uses the SCRIPT_NAME variable instead of a REQUEST_URI variable... thanks, MS
			$uri = $scheme . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];

			// If the QUERY_STRING variable exists append it to the URI string.
			if (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING']))
			{
				$uri .= '?' . $_SERVER['QUERY_STRING'];
			}
		}

		return trim($uri);
	}

	/**
	 * Detects the client browser and version in a user agent string.
	 *
	 * @param   string  $userAgent  The user-agent string to parse.
	 *
	 * @return  void
	 *
	 * @since   {DEPLOY_VERSION}
	 */
	protected function detectBrowser($userAgent)
	{
		$patternBrowser = '';

		// Attempt to detect the browser type.  Obviously we are only worried about major browsers.
		if ((stripos($userAgent, 'MSIE') !== false) && (stripos($userAgent, 'Opera') === false))
		{
			$this->browser = self::IE;
			$patternBrowser = 'MSIE';
		}
		elseif ((stripos($userAgent, 'Firefox') !== false) && (stripos($userAgent, 'like Firefox') === false))
		{
			$this->browser = self::FIREFOX;
			$patternBrowser = 'Firefox';
		}
		elseif (stripos($userAgent, 'Chrome') !== false)
		{
			$this->browser = self::CHROME;
			$patternBrowser = 'Chrome';
		}
		elseif (stripos($userAgent, 'Safari') !== false)
		{
			$this->browser = self::SAFARI;
			$patternBrowser = 'Safari';
		}
		elseif (stripos($userAgent, 'Opera') !== false)
		{
			$this->browser = self::OPERA;
			$patternBrowser = 'Opera';
		}

		// If we detected a known browser let's attempt to determine the version.
		if ($this->browser)
		{
			// Build the REGEX pattern to match the browser version string within the user agent string.
			$pattern = '#(?<browser>Version|' . $patternBrowser . ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';

			// Attempt to find version strings in the user agent string.
			$matches = array();

			if (preg_match_all($pattern, $userAgent, $matches))
			{
				// Do we have both a Version and browser match?
				if (count($matches['browser']) == 2)
				{
					// See whether Version or browser came first, and use the number accordingly.
					if (strripos($userAgent, 'Version') < strripos($userAgent, $patternBrowser))
					{
						$this->browserVersion = $matches['version'][0];
					}
					else
					{
						$this->browserVersion = $matches['version'][1];
					}
				}
				elseif (count($matches['browser']) > 2)
				{
					$key = array_search('Version', $matches['browser']);

					if ($key)
					{
						$this->browserVersion = $matches['version'][$key];
					}
				}
				else
					// We only have a Version or a browser so use what we have.
				{
					$this->browserVersion = $matches['version'][0];
				}
			}
		}

		// Mark this detection routine as run.
		$this->detection['browser'] = true;
	}

	/**
	 * Method to detect the accepted response encoding by the client.
	 *
	 * @param   string  $acceptEncoding  The client accept encoding string to parse.
	 *
	 * @return  void
	 *
	 * @since   {DEPLOY_VERSION}
	 */
	protected function detectEncoding($acceptEncoding)
	{
		// Parse the accepted encodings.
		$this->encodings = array_map('trim', (array) explode(',', $acceptEncoding));

		// Mark this detection routine as run.
		$this->detection['acceptEncoding'] = true;
	}

	/**
	 * Detects the client rendering engine in a user agent string.
	 *
	 * @param   string  $userAgent  The user-agent string to parse.
	 *
	 * @return  void
	 *
	 * @since   {DEPLOY_VERSION}
	 */
	protected function detectEngine($userAgent)
	{
		if (stripos($userAgent, 'MSIE') !== false || stripos($userAgent, 'Trident') !== false)
		{
			// Attempt to detect the client engine -- starting with the most popular ... for now.
			$this->engine = self::TRIDENT;
		}
		elseif (stripos($userAgent, 'AppleWebKit') !== false || stripos($userAgent, 'blackberry') !== false)
		{
			// Evidently blackberry uses WebKit and doesn't necessarily report it.  Bad RIM.
			$this->engine = self::WEBKIT;
		}
		elseif (stripos($userAgent, 'Gecko') !== false && stripos($userAgent, 'like Gecko') === false)
		{
			// We have to check for like Gecko because some other browsers spoof Gecko.
			$this->engine = self::GECKO;
		}
		elseif (stripos($userAgent, 'Opera') !== false || stripos($userAgent, 'Presto') !== false)
		{
			// Sometimes Opera browsers don't say Presto.
			$this->engine = self::PRESTO;
		}
		elseif (stripos($userAgent, 'KHTML') !== false)
		{
			// *sigh*
			$this->engine = self::KHTML;
		}
		elseif (stripos($userAgent, 'Amaya') !== false)
		{
			// Lesser known engine but it finishes off the major list from Wikipedia :-)
			$this->engine = self::AMAYA;
		}

		// Mark this detection routine as run.
		$this->detection['engine'] = true;
	}

	/**
	 * Method to detect the accepted languages by the client.
	 *
	 * @param   mixed  $acceptLanguage  The client accept language string to parse.
	 *
	 * @return  void
	 *
	 * @since   {DEPLOY_VERSION}
	 */
	protected function detectLanguage($acceptLanguage)
	{
		// Parse the accepted encodings.
		$this->languages = array_map('trim', (array) explode(',', $acceptLanguage));

		// Mark this detection routine as run.
		$this->detection['acceptLanguage'] = true;
	}

	/**
	 * Detects the client platform in a user agent string.
	 *
	 * @param   string  $userAgent  The user-agent string to parse.
	 *
	 * @return  void
	 *
	 * @since   {DEPLOY_VERSION}
	 */
	protected function detectPlatform($userAgent)
	{
		// Attempt to detect the client platform.
		if (stripos($userAgent, 'Windows') !== false)
		{
			$this->platform = self::WINDOWS;

			// Let's look at the specific mobile options in the Windows space.
			if (stripos($userAgent, 'Windows Phone') !== false)
			{
				$this->mobile = true;
				$this->platform = self::WINDOWS_PHONE;
			}
			elseif (stripos($userAgent, 'Windows CE') !== false)
			{
				$this->mobile = true;
				$this->platform = self::WINDOWS_CE;
			}
		}
		elseif (stripos($userAgent, 'iPhone') !== false)
		{
			// Interestingly 'iPhone' is present in all iOS devices so far including iPad and iPods.
			$this->mobile = true;
			$this->platform = self::IPHONE;

			// Let's look at the specific mobile options in the iOS space.
			if (stripos($userAgent, 'iPad') !== false)
			{
				$this->platform = self::IPAD;
			}
			elseif (stripos($userAgent, 'iPod') !== false)
			{
				$this->platform = self::IPOD;
			}
		}
		elseif (stripos($userAgent, 'iPad') !== false)
		{
			// In case where iPhone is not mentioed in iPad user agent string
			$this->mobile = true;
			$this->platform = self::IPAD;
		}
		elseif (stripos($userAgent, 'iPod') !== false)
		{
			// In case where iPhone is not mentioed in iPod user agent string
			$this->mobile = true;
			$this->platform = self::IPOD;
		}
		elseif (preg_match('/macintosh|mac os x/i', $userAgent))
		{
			// This has to come after the iPhone check because mac strings are also present in iOS devices.
			$this->platform = self::MAC;
		}
		elseif (stripos($userAgent, 'Blackberry') !== false)
		{
			$this->mobile = true;
			$this->platform = self::BLACKBERRY;
		}
		elseif (stripos($userAgent, 'Android') !== false)
		{
			$this->mobile = true;
			$this->platform = self::ANDROID;
			/**
			 * Attempt to distinguish between Android phones and tablets
			 * There is no totally foolproof method but certain rules almost always hold
			 *   Android 3.x is only used for tablets
			 *   Some devices and browsers encourage users to change their UA string to include Tablet.
			 *   Google encourages manufacturers to exclude the string Mobile from tablet device UA strings.
			 *   In some modes Kindle Android devices include the string Mobile but they include the string Silk.
			 */
			if (stripos($userAgent, 'Android 3') !== false || stripos($userAgent, 'Tablet') !== false
				|| stripos($userAgent, 'Mobile') === false || stripos($userAgent, 'Silk') !== false )
			{
				$this->platform = self::ANDROIDTABLET;
			}
		}
		elseif (stripos($userAgent, 'Linux') !== false)
		{
			$this->platform = self::LINUX;
		}

		// Mark this detection routine as run.
		$this->detection['platform'] = true;
	}

	/**
	 * Determines if the browser is a robot or not.
	 *
	 * @param   string  $userAgent  The user-agent string to parse.
	 *
	 * @return  void
	 *
	 * @since   {DEPLOY_VERSION}
	 */
	protected function detectRobot($userAgent)
	{
		if (preg_match('/http|bot|robot|spider|crawler|curl|^$/i', $userAgent))
		{
			$this->robot = true;
		}
		else
		{
			$this->robot = false;
		}

		$this->detection['robot'] = true;
	}

	/**
	 * getPlatform
	 *
	 * @param bool $refresh
	 *
	 * @return  int
	 */
	public function getPlatform($refresh = false)
	{
		if (empty($this->detection['platform']) || $refresh)
		{
			$this->detectPlatform($this->userAgent);
		}

		return $this->platform;
	}

	/**
	 * getMobile
	 *
	 * @param bool $refresh
	 *
	 * @return  boolean
	 */
	public function getMobile($refresh = false)
	{
		if (empty($this->detection['platform']) || $refresh)
		{
			$this->detectPlatform($this->userAgent);
		}

		return $this->mobile;
	}

	/**
	 * getEngine
	 *
	 * @param bool $refresh
	 *
	 * @return  int
	 */
	public function getEngine($refresh = false)
	{
		if (empty($this->detection['engine']) || $refresh)
		{
			$this->detectEngine($this->userAgent);
		}

		return $this->engine;
	}

	/**
	 * getBrowser
	 *
	 * @param bool $refresh
	 *
	 * @return  int
	 */
	public function getBrowser($refresh = false)
	{
		if (empty($this->detection['browser']) || $refresh)
		{
			$this->detectBrowser($this->userAgent);
		}

		return $this->browser;
	}

	/**
	 * getBrowserVersion
	 *
	 * @param bool $refresh
	 *
	 * @return  string
	 */
	public function getBrowserVersion($refresh = false)
	{
		if (empty($this->detection['browser']) || $refresh)
		{
			$this->detectBrowser($this->userAgent);
		}

		return $this->browserVersion;
	}

	/**
	 * getLanguages
	 *
	 * @param bool $refresh
	 *
	 * @return  array
	 */
	public function getLanguages($refresh = false)
	{
		if (empty($this->detection['acceptLanguage']) || $refresh)
		{
			$this->detectLanguage($this->acceptLanguage);
		}

		return $this->languages;
	}

	/**
	 * getEncodings
	 *
	 * @param bool $refresh
	 *
	 * @return  array
	 */
	public function getEncodings($refresh = false)
	{
		if (empty($this->detection['acceptEncoding']) || $refresh)
		{
			$this->detectEncoding($this->acceptEncoding);
		}

		return $this->encodings;
	}

	/**
	 * getUserAgent
	 *
	 * @return  string
	 */
	public function getUserAgent()
	{
		return $this->userAgent;
	}

	/**
	 * setUserAgent
	 *
	 * @param   string $userAgent
	 *
	 * @return  WebClient  Return self to support chaining.
	 */
	public function setUserAgent($userAgent)
	{
		$this->userAgent = $userAgent;

		return $this;
	}

	/**
	 * getRobot
	 *
	 * @param bool $refresh
	 *
	 * @return  boolean
	 */
	public function isRobot($refresh = false)
	{
		if (empty($this->detection['robot']) || $refresh)
		{
			$this->detectRobot($this->userAgent);
		}

		return $this->robot;
	}

	/**
	 * Determine if we are using a secure (SSL) connection.
	 *
	 * @return  boolean  True if using SSL, false if not.
	 *
	 * @since   {DEPLOY_VERSION}
	 */
	public function isSSLConnection()
	{
		return (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off');
	}
}
