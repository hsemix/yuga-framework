<?php
namespace Yuga\Mailables\Native;

use Yuga\App;
use Yuga\View\ViewModel;
use Yuga\Mailables\Mailable;
use Yuga\Application\Application;

class YugaMailer extends Mailable
{

	/**
	 * Used as the User-Agent and X-Mailer headers' value.
	 *
	 * @var	string
	 */
	public $userAgent	= 'Yuga';

	/**
	 * Path to the Sendmail binary.
	 *
	 * @var	string
	 */
	public $mailPath	= '/usr/sbin/sendmail';	// Sendmail path

	/**
	 * Which method to use for sending e-mails.
	 *
	 * @var	string	'mail', 'sendmail' or 'smtp'
	 */
	public $protocol	= 'mail';		// mail/sendmail/smtp

	/**
	 * STMP Server host
	 *
	 * @var	string
	 */
	public $smtp_host	= '';

	/**
	 * SMTP Username
	 *
	 * @var	string
	 */
	public $smtp_user	= '';

	/**
	 * SMTP Password
	 *
	 * @var	string
	 */
	public $smtp_pass	= '';

	/**
	 * SMTP Server port
	 *
	 * @var	int
	 */
	public $smtp_port	= 25;

	/**
	 * SMTP connection timeout in seconds
	 *
	 * @var	int
	 */
	public $smtp_timeout	= 5;

	/**
	 * SMTP persistent connection
	 *
	 * @var	bool
	 */
	public $smtp_keepalive	= false;

	/**
	 * SMTP Encryption
	 *
	 * @var	string	empty, 'tls' or 'ssl'
	 */
	public $smtp_crypto	= '';

	/**
	 * Whether to apply word-wrapping to the message body.
	 *
	 * @var	bool
	 */
	public $wordwrap	= true;

	/**
	 * Number of characters to wrap at.
	 *
	 * @see	static::$wordwrap
	 * @var	int
	 */
	public $wrapchars	= 76;

	/**
	 * Message format.
	 *
	 * @var	string	'text' or 'html'
	 */
	public $mailtype	= 'text';

	/**
	 * Character set (default: utf-8)
	 *
	 * @var	string
	 */
	public $charset		= 'UTF-8';

	/**
	 * Alternative message (for HTML messages only)
	 *
	 * @var	string
	 */
	public $altMessage	= '';

	/**
	 * Whether to validate e-mail addresses.
	 *
	 * @var	bool
	 */
	public $validate	= false;

	/**
	 * X-Priority header value.
	 *
	 * @var	int	1-5
	 */
	public $priority	= 3;			// Default priority (1 - 5)

	/**
	 * Newline character sequence.
	 * Use "\r\n" to comply with RFC 822.
	 *
	 * @link	http://www.ietf.org/rfc/rfc822.txt
	 * @var	string	"\r\n" or "\n"
	 */
	public $newline		= "\n";			// Default newline. "\r\n" or "\n" (Use "\r\n" to comply with RFC 822)

	/**
	 * CRLF character sequence
	 *
	 * RFC 2045 specifies that for 'quoted-printable' encoding,
	 * "\r\n" must be used. However, it appears that some servers
	 * (even on the receiving end) don't handle it properly and
	 * switching to "\n", while improper, is the only solution
	 * that seems to work for all environments.
	 *
	 * @link	http://www.ietf.org/rfc/rfc822.txt
	 * @var	string
	 */
	public $crlf		= "\n";

	/**
	 * Whether to use Delivery Status Notification.
	 *
	 * @var	bool
	 */
	public $dsn		= false;

	/**
	 * Whether to send multipart alternatives.
	 * Yahoo! doesn't seem to like these.
	 *
	 * @var	bool
	 */
	public $sendMultipart	= true;

	/**
	 * Whether to send messages to BCC recipients in batches.
	 *
	 * @var	bool
	 */
	public $bccBatchMode	= false;

	/**
	 * BCC Batch max number size.
	 *
	 * @see	static::$bccBatchMode
	 * @var	int
	 */
	public $bccBatchSize	= 200;

	

	/**
	 * Whether PHP is running in safe mode. Initialized by the class constructor.
	 *
	 * @var	bool
	 */
	protected $safeModel		= false;

	/**
	 * Subject header
	 *
	 * @var	string
	 */
	protected $subject		= '';

	/**
	 * Message body
	 *
	 * @var	string
	 */
	protected $body		= '';

	/**
	 * Final message body to be sent.
	 *
	 * @var	string
	 */
	protected $finalBody		= '';

	/**
	 * Final headers to send
	 *
	 * @var	string
	 */
	protected $headerString		= '';

	/**
	 * SMTP Connection socket placeholder
	 *
	 * @var	resource
	 */
	protected $smtpConnect	= '';

	/**
	 * Mail encoding
	 *
	 * @var	string	'8bit' or '7bit'
	 */
	protected $encoding		= '8bit';

	/**
	 * Whether to perform SMTP authentication
	 *
	 * @var	bool
	 */
	protected $smtp_auth		= false;

	/**
	 * Whether to send a Reply-To header
	 *
	 * @var	bool
	 */
	protected $replyToFlag	= false;

	/**
	 * Debug messages
	 *
	 * @see	static::printDebugger()
	 * @var	string
	 */
	protected $debugMessage		= [];

	/**
	 * Recipients
	 *
	 * @var	string[]
	 */
	protected $recipients		= [];

	/**
	 * CC Recipients
	 *
	 * @var	string[]
	 */
	protected $ccArray		= [];

	/**
	 * BCC Recipients
	 *
	 * @var	string[]
	 */
	protected $bccArray		= [];

	/**
	 * Message headers
	 *
	 * @var	string[]
	 */
	protected $headers		= [];

	/**
	 * Attachment data
	 *
	 * @var	array
	 */
	protected $attachments		= [];

	/**
	 * Valid $protocol values
	 *
	 * @see	static::$protocol
	 * @var	string[]
	 */
	protected $protocols = [
		'mail', 
		'sendmail', 
		'smtp'
	];

	/**
	 * Base charsets
	 *
	 * Character sets valid for 7-bit encoding,
	 * excluding language suffix.
	 *
	 * @var	string[]
	 */
	protected $baseCharsets	= [
		'us-ascii', 
		'iso-2022-'
	];

	/**
	 * Bit depths
	 *
	 * Valid mail encodings
	 *
	 * @see	static::$encoding
	 * @var	string[]
	 */
	protected $bitDepths	= [
		'7bit', 
		'8bit'
	];

	/**
	 * $priority translations
	 *
	 * Actual values to send with the X-Priority header
	 *
	 * @var	string[]
	 */
	protected $priorities = [
		1 => '1 (Highest)',
		2 => '2 (High)',
		3 => '3 (Normal)',
		4 => '4 (Low)',
		5 => '5 (Lowest)'
	];

	/**
	 * mbstring.func_overload flag
	 *
	 * @var	bool
	 */
	protected static $functionOverload;

	/**
	 * Constructor - Sets Email Preferences
	 *
	 * The constructor can be passed an array of config values
	 *
	 * @param	array	$config = []
	 * @return	void
	 */
	public function __construct(array $config = [])
	{
		parent::__construct();
		$this->charset = Application::CHARSET_UTF8;
		if (extension_loaded('mbstring')) {
			define('MB_ENABLED', true);
			// @ini_set('mbstring.internal_encoding', $this->charset);
			mb_substitute_character('none');
		} else {
			define('MB_ENABLED', false);
		}

		if (extension_loaded('iconv')) {
			define('ICONV_ENABLED', true);
			// @ini_set('iconv.internal_encoding', $this->charset);
		} else {
			define('ICONV_ENABLED', false);
		}
		$this->boot($config);
		$this->safeModel =  ini_get('safe_mode');
		isset(static::$functionOverload) || static::$functionOverload = (extension_loaded('mbstring') && ini_get('mbstring.func_overload'));
	}

	/**
	 * Initialize preferences
	 *
	 * @param	array	$config
	 * @return	static
	 */
	public function boot(array $config = [])
	{
		$this->clear();
		foreach ($config as $key => $val) {
			if (isset($this->$key)) {
				$method = 'set'.ucfirst($key);

				if (method_exists($this, $method)) {
					$this->$method($val);
				} else {
					$this->$key = $val;
				}
			}
		}
		if (strtolower($config['protocol']) == 'smtp') {
			$this->setNewLine("\r\n");
		}
		$this->charset = strtoupper($this->charset);
		$this->smtp_auth = isset($this->smtp_user[0], $this->smtp_pass[0]);

		return $this;
	}

	/**
	 * Initialize the Email Data
	 *
	 * @param	bool
	 * @return	static
	 */
	public function clear($clearattachments = false)
	{
		$this->subject		= '';
		$this->body		= '';
		$this->finalBody	= '';
		$this->headerString	= '';
		$this->replyToFlag	= false;
		$this->recipients	= [];
		$this->ccArray	= [];
		$this->bccArray	= [];
		$this->headers		= [];
		$this->debugMessage	= [];

		$this->setHeader('Date', $this->setDate());

		if ($clearattachments !== false) {
			$this->attachments = [];
		}

		return $this;
	}

	/**
	 * Set FROM
	 *
	 * @param	string	$from
	 * @param	string	$name
	 * @param	string	$return_path = NULL	Return-Path
	 * @return	static
	 */
	public function setFromAddress($from, $name = '', $return_path = NULL)
	{
		if (preg_match('/\<(.*)\>/', $from, $match)) {
			$from = $match[1];
		}

		if ($this->validate) {
			$this->validateEmail($this->stringToArray($from));
			if ($return_path) {
				$this->validateEmail($this->stringToArray($return_path));
			}
		}

		// prepare the display name
		if ($name !== '') {
			// only use Q encoding if there are characters that would require it
			if (!preg_match('/[\200-\377]/', $name)) {
				// add slashes for non-printing characters, slashes, and double quotes, and surround it in double quotes
				$name = '"'.addcslashes($name, "\0..\37\177'\"\\").'"';
			} else {
				$name = $this->prepareQEncoding($name);
			}
		}

		$this->setHeader('From', $name.' <'.$from.'>');

		isset($return_path) or $return_path = $from;
		$this->setHeader('Return-Path', '<'.$return_path.'>');

		return $this;
	}

	/**
	 * Set Reply-to
	 *
	 * @param	string
	 * @param	string
	 * @return	static
	 */
	public function addReplyTo($replyto, $name = '')
	{
		if (preg_match('/\<(.*)\>/', $replyto, $match)) {
			$replyto = $match[1];
		}

		if ($this->validate) {
			$this->validateEmail($this->stringToArray($replyto));
		}

		if ($name !== '') {
			// only use Q encoding if there are characters that would require it
			if (!preg_match('/[\200-\377]/', $name)) {
				// add slashes for non-printing characters, slashes, and double quotes, and surround it in double quotes
				$name = '"'.addcslashes($name, "\0..\37\177'\"\\").'"';
			} else {
				$name = $this->prepareQEncoding($name);
			}
		}

		$this->setHeader('Reply-To', $name.' <'.$replyto.'>');
		$this->replyToFlag = true;

		return $this;
	}

	/**
	 * Set Recipients
	 *
	 * @param	string
	 * @return	static
	 */
	public function addToRecipient($to)
	{
		$to = $this->stringToArray($to);
		$to = $this->cleanEmail($to);

		if ($this->validate) {
			$this->validateEmail($to);
		}

		if ($this->getProtocol() !== 'mail') {
			$this->setHeader('To', implode(', ', $to));
		}

		$this->recipients = $to;

		return $this;
	}

	/**
	 * Set CC
	 *
	 * @param	string
	 * @return	static
	 */
	public function addCC($cc)
	{
		$cc = $this->cleanEmail($this->stringToArray($cc));

		if ($this->validate) {
			$this->validateEmail($cc);
		}

		$this->setHeader('Cc', implode(', ', $cc));

		if ($this->getProtocol() === 'smtp') {
			$this->ccArray = $cc;
		}

		return $this;
	}

	/**
	 * Set BCC
	 *
	 * @param	string
	 * @param	string
	 * @return	static
	 */
	public function addBCC($bcc, $limit = '')
	{
		if ($limit !== '' && is_numeric($limit)){
			$this->bccBatchMode = true;
			$this->bccBatchSize = $limit;
		}

		$bcc = $this->cleanEmail($this->stringToArray($bcc));

		if ($this->validate) {
			$this->validateEmail($bcc);
		}

		if ($this->getProtocol() === 'smtp' or ($this->bccBatchMode && count($bcc) > $this->bccBatchSize)) {
			$this->bccArray = $bcc;
		} else {
			$this->setHeader('Bcc', implode(', ', $bcc));
		}

		return $this;
	}


	/**
	 * Set Email Subject
	 *
	 * @param	string
	 * @return	static
	 */
	public function setSubject($subject)
	{
		$subject = $this->prepareQEncoding($subject);
		$this->setHeader('Subject', $subject);
		return $this;
	}

	/**
	 * Set Body
	 *
	 * @param	string
	 * @return	static
	 */
	public function message($body)
	{
		$this->body = rtrim(str_replace("\r", '', $body));

		return $this;
	}

	/**
	 * Assign file attachments
	 *
	 * @param	string	$file	Can be local path, URL or buffered content
	 * @param	string	$disposition = 'attachment'
	 * @param	string|null	$newname
	 * @param	string	$mime = ''
	 * @return	static
	 */
	public function addAttachment($file, $disposition = '', $newname = null, $mime = '')
	{
		if ($mime === '') {
			if (strpos($file, '://') === false && !file_exists($file)) {
				$this->setErrorMessage('lang:email_attachment_missing', $file);
				return false;
			}

			if (!$fp = @fopen($file, 'rb')) {
				$this->setErrorMessage('lang:email_attachment_unreadable', $file);
				return false;
			}

			$file_content = stream_get_contents($fp);
			$mime = $this->mimeTypes(pathinfo($file, PATHINFO_EXTENSION));
			fclose($fp);
		} else {
			$file_content =& $file; // buffered file
		}

		$this->attachments[] = [
			'name'		=> [$file, $newname],
			'disposition'	=> empty($disposition) ? 'attachment' : $disposition,  // Can also be 'inline'  Not sure if it matters
			'type'		=> $mime,
			'content'	=> chunk_split(base64_encode($file_content)),
			'multipart'	=> 'mixed'
		];

		return $this;
	}

	/**
	 * Set and return attachment Content-ID
	 *
	 * Useful for attached inline pictures
	 *
	 * @param	string	$filename
	 * @return	string
	 */
	public function attachment_cid($filename)
	{
		for ($i = 0, $c = count($this->attachments); $i < $c; $i++) {
			if ($this->attachments[$i]['name'][0] === $filename) {
				$this->attachments[$i]['multipart'] = 'related';
				$this->attachments[$i]['cid'] = uniqid(basename($this->attachments[$i]['name'][0]).'@');
				return $this->attachments[$i]['cid'];
			}
		}

		return false;
	}

	/**
	 * Add a Header Item
	 *
	 * @param	string
	 * @param	string
	 * @return	static
	 */
	public function setHeader($header, $value)
	{
		$this->headers[$header] = str_replace(array("\n", "\r"), '', $value);
		return $this;
	}

	/**
	 * Convert a String to an Array
	 *
	 * @param	string
	 * @return	array
	 */
	protected function stringToArray($email)
	{
		if (!is_array($email)) {
			return (strpos($email, ',') !== false) ? preg_split('/[\s,]/', $email, -1, PREG_SPLIT_NO_EMPTY) : (array) trim($email);
		}

		return $email;
	}

	/**
	 * Set Multipart Value
	 *
	 * @param	string
	 * @return	static
	 */
	public function setAltMessage($str)
	{
		$this->altMessage = (string) $str;
		return $this;
	}

	/**
	 * Set Mailtype
	 *
	 * @param	string
	 * @return	static
	 */
	public function setMailType($type = 'text')
	{
		$this->mailtype = ($type === 'html') ? 'html' : 'text';
		return $this;
	}

	/**
	 * Set Wordwrap
	 *
	 * @param	bool
	 * @return	static
	 */
	public function setWordWrap($wordwrap = true)
	{
		$this->wordwrap = (bool) $wordwrap;
		return $this;
	}

	/**
	 * Set Protocol
	 *
	 * @param	string
	 * @return	static
	 */
	public function setProtocol($protocol = 'mail')
	{
		$this->protocol = in_array($protocol, $this->protocols, true) ? strtolower($protocol) : 'mail';
		return $this;
	}

	/**
	 * Set Priority
	 *
	 * @param	int
	 * @return	static
	 */
	public function setPriority($n = 3)
	{
		$this->priority = preg_match('/^[1-5]$/', $n) ? (int) $n : 3;
		return $this;
	}

	/**
	 * Set Newline Character
	 *
	 * @param	string
	 * @return	static
	 */
	public function setNewLine($newline = "\n")
	{
		$this->newline = in_array($newline, ["\n", "\r\n", "\r"]) ? $newline : "\n";
		return $this;
	}

	/**
	 * Set CRLF
	 *
	 * @param	string
	 * @return	static
	 */
	public function setCrlf($crlf = "\n")
	{
		$this->crlf = ($crlf !== "\n" && $crlf !== "\r\n" && $crlf !== "\r") ? "\n" : $crlf;
		return $this;
	}

	/**
	 * Get the Message ID
	 *
	 * @return	string
	 */
	protected function getMessageId()
	{
		$from = str_replace(array('>', '<'), '', $this->headers['Return-Path']);
		return '<'.uniqid('').strstr($from, '@').'>';
	}

	/**
	 * Get Mail Protocol
	 *
	 * @return	mixed
	 */
	protected function getProtocol()
	{
		$this->protocol = strtolower($this->protocol);
		in_array($this->protocol, $this->protocols, true) or $this->protocol = 'mail';
		return $this->protocol;
	}

	/**
	 * Get Mail Encoding
	 *
	 * @return	string
	 */
	protected function getEncoding()
	{
		in_array($this->encoding, $this->bitDepths) or $this->encoding = '8bit';

		foreach ($this->baseCharsets as $charset) {
			if (strpos($this->charset, $charset) === 0) {
				$this->encoding = '7bit';
			}
		}

		return $this->encoding;
	}

	/**
	 * Get content type (text/html/attachment)
	 *
	 * @return	string
	 */
	protected function getContentType()
	{
		if ($this->mailtype === 'html') {
			return empty($this->attachments) ? 'html' : 'html-attach';
		} elseif	($this->mailtype === 'text' &&!empty($this->attachments)) {
			return 'plain-attach';
		} else {
			return 'plain';
		}
	}

	/**
	 * Set RFC 822 Date
	 *
	 * @return	string
	 */
	protected function setDate()
	{
		$timezone = date('Z');
		$operator = ($timezone[0] === '-') ? '-' : '+';
		$timezone = abs($timezone);
		$timezone = floor($timezone/3600) * 100 + ($timezone % 3600) / 60;

		return sprintf('%s %s%04d', date('D, j M Y H:i:s'), $operator, $timezone);
	}

	/**
	 * Mime message
	 *
	 * @return	string
	 */
	protected function getMimeMessage()
	{
		return 'This is a multi-part message in MIME format.'.$this->newline.'Your email application may not support this format.';
	}

	/**
	 * Validate Email Address
	 *
	 * @param	string
	 * @return	bool
	 */
	public function validateEmail($email)
	{
		if (!is_array($email)) {
			$this->setErrorMessage('lang:email_must_be_array');
			return false;
		}

		foreach ($email as $val) {
			if (!$this->valid_email($val)) {
				$this->setErrorMessage('lang:email_invalid_address', $val);
				return false;
			}
		}

		return true;
	}

	/**
	 * Email Validation
	 *
	 * @param	string
	 * @return	bool
	 */
	public function valid_email($email)
	{
		return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
	}

	/**
	 * Clean Extended Email Address: Joe Smith <joe@smith.com>
	 *
	 * @param	string
	 * @return	string
	 */
	public function cleanEmail($email)
	{
		if (!is_array($email)) {
			return preg_match('/\<(.*)\>/', $email, $match) ? $match[1] : $email;
		}

		$cleanEmail = [];

		foreach ($email as $addy) {
			$cleanEmail[] = preg_match('/\<(.*)\>/', $addy, $match) ? $match[1] : $addy;
		}

		return $cleanEmail;
	}

	/**
	 * Build alternative plain text message
	 *
	 * Provides the raw message for use in plain-text headers of
	 * HTML-formatted emails.
	 * If the user hasn't specified his own alternative message
	 * it creates one by stripping the HTML
	 *
	 * @return	string
	 */
	protected function getAltMessage()
	{
		if (!empty($this->altMessage)) {
			return ($this->wordwrap) ? $this->wordWrap($this->altMessage, 76) : $this->altMessage;
		}

		$body = preg_match('/\<body.*?\>(.*)\<\/body\>/si', $this->body, $match) ? $match[1] : $this->body;
		$body = str_replace("\t", '', preg_replace('#<!--(.*)--\>#', '', trim(strip_tags($body))));

		for ($i = 20; $i >= 3; $i--) {
			$body = str_replace(str_repeat("\n", $i), "\n\n", $body);
		}

		// Reduce multiple spaces
		$body = preg_replace('| +|', ' ', $body);

		return ($this->wordwrap) ? $this->wordWrap($body, 76) : $body;
	}

	/**
	 * Word Wrap
	 *
	 * @param	string
	 * @param	int	line-length limit
	 * @return	string
	 */
	public function wordWrap($str, $charlim = NULL)
	{
		// Set the character limit, if not already present
		if (empty($charlim)) {
			$charlim = empty($this->wrapchars) ? 76 : $this->wrapchars;
		}

		// Standardize newlines
		if (strpos($str, "\r") !== false) {
			$str = str_replace(array("\r\n", "\r"), "\n", $str);
		}

		// Reduce multiple spaces at end of line
		$str = preg_replace('| +\n|', "\n", $str);

		// If the current word is surrounded by {unwrap} tags we'll
		// strip the entire chunk and replace it with a marker.
		$unwrap = [];
		if (preg_match_all('|\{unwrap\}(.+?)\{/unwrap\}|s', $str, $matches)) {
			for ($i = 0, $c = count($matches[0]); $i < $c; $i++) {
				$unwrap[] = $matches[1][$i];
				$str = str_replace($matches[0][$i], '{{unwrapped'.$i.'}}', $str);
			}
		}

		// Use PHP's native function to do the initial wordwrap.
		// We set the cut flag to false so that any individual words that are
		// too long get left alone. In the next step we'll deal with them.
		$str = wordwrap($str, $charlim, "\n", false);

		// Split the string into individual lines of text and cycle through them
		$output = '';
		foreach (explode("\n", $str) as $line) {
			// Is the line within the allowed character count?
			// If so we'll join it to the output and continue
			if (static::strlen($line) <= $charlim) {
				$output .= $line.$this->newline;
				continue;
			}

			$temp = '';
			do {
				// If the over-length word is a URL we won't wrap it
				if (preg_match('!\[url.+\]|://|www\.!', $line)) {
					break;
				}

				// Trim the word down
				$temp .= static::substr($line, 0, $charlim - 1);
				$line = static::substr($line, $charlim - 1);
			} while (static::strlen($line) > $charlim);

			// If $temp contains data it means we had to split up an over-length
			// word into smaller chunks so we'll add it back to our current line
			if ($temp !== '') {
				$output .= $temp.$this->newline;
			}

			$output .= $line.$this->newline;
		}

		// Put our markers back
		if (count($unwrap) > 0) {
			foreach ($unwrap as $key => $val) {
				$output = str_replace('{{unwrapped'.$key.'}}', $val, $output);
			}
		}

		return $output;
	}

	/**
	 * Build final headers
	 *
	 * @return	void
	 */
	protected function buildHeaders()
	{
		$this->setHeader('User-Agent', $this->userAgent);
		$this->setHeader('X-Sender', $this->cleanEmail($this->headers['From']));
		$this->setHeader('X-Mailer', $this->userAgent);
		$this->setHeader('X-Priority', $this->priorities[$this->priority]);
		$this->setHeader('Message-ID', $this->getMessageId());
		$this->setHeader('Mime-Version', '1.0');
	}

	/**
	 * Write Headers as a string
	 *
	 * @return	void
	 */
	protected function writeHeaders()
	{
		if ($this->protocol === 'mail') {
			if (isset($this->headers['Subject'])) {
				$this->subject = $this->headers['Subject'];
				unset($this->headers['Subject']);
			}
		}

		reset($this->headers);
		$this->headerString = '';

		foreach ($this->headers as $key => $val) {
			$val = trim($val);

			if ($val !== '') {
				$this->headerString .= $key.': '.$val.$this->newline;
			}
		}

		if ($this->getProtocol() === 'mail') {
			$this->headerString = rtrim($this->headerString);
		}
	}

	/**
	 * Build Final Body and attachments
	 *
	 * @return	bool
	 */
	protected function buildMessage()
	{
		if ($this->wordwrap === true && $this->mailtype !== 'html') {
			$this->body = $this->wordWrap($this->body);
		}

		$this->writeHeaders();

		$hdr = ($this->getProtocol() === 'mail') ? $this->newline : '';
		$body = '';

		switch ($this->getContentType()) {
			case 'plain':

				$hdr .= 'Content-Type: text/plain; charset='.$this->charset.$this->newline
					.'Content-Transfer-Encoding: '.$this->getEncoding();

				if ($this->getProtocol() === 'mail') {
					$this->headerString .= $hdr;
					$this->finalBody = $this->body;
				} else {
					$this->finalBody = $hdr.$this->newline.$this->newline.$this->body;
				}

				return;

			case 'html':

				if ($this->sendMultipart === false) {
					$hdr .= 'Content-Type: text/html; charset='.$this->charset.$this->newline
						.'Content-Transfer-Encoding: quoted-printable';
				} else {
					$boundary = uniqid('B_ALT_');
					$hdr .= 'Content-Type: multipart/alternative; boundary="'.$boundary.'"';

					$body .= $this->getMimeMessage().$this->newline.$this->newline
						.'--'.$boundary.$this->newline

						.'Content-Type: text/plain; charset='.$this->charset.$this->newline
						.'Content-Transfer-Encoding: '.$this->getEncoding().$this->newline.$this->newline
						.$this->getAltMessage().$this->newline.$this->newline
						.'--'.$boundary.$this->newline

						.'Content-Type: text/html; charset='.$this->charset.$this->newline
						.'Content-Transfer-Encoding: quoted-printable'.$this->newline.$this->newline;
				}

				$this->finalBody = $body.$this->prepareQuotedPrintable($this->body).$this->newline.$this->newline;

				if ($this->getProtocol() === 'mail') {
					$this->headerString .= $hdr;
				} else {
					$this->finalBody = $hdr.$this->newline.$this->newline.$this->finalBody;
				}

				if ($this->sendMultipart !== false) {
					$this->finalBody .= '--'.$boundary.'--';
				}

				return;

			case 'plain-attach':

				$boundary = uniqid('B_ATC_');
				$hdr .= 'Content-Type: multipart/mixed; boundary="'.$boundary.'"';

				if ($this->getProtocol() === 'mail') {
					$this->headerString .= $hdr;
				}

				$body .= $this->getMimeMessage().$this->newline
					.$this->newline
					.'--'.$boundary.$this->newline
					.'Content-Type: text/plain; charset='.$this->charset.$this->newline
					.'Content-Transfer-Encoding: '.$this->getEncoding().$this->newline
					.$this->newline
					.$this->body.$this->newline.$this->newline;

				$this->appendAttachments($body, $boundary);

				break;
			case 'html-attach':

				$alt_boundary = uniqid('B_ALT_');
				$last_boundary = NULL;

				if ($this->attachmentsHaveMultipart('mixed')) {
					$atc_boundary = uniqid('B_ATC_');
					$hdr .= 'Content-Type: multipart/mixed; boundary="'.$atc_boundary.'"';
					$last_boundary = $atc_boundary;
				}

				if ($this->attachmentsHaveMultipart('related')) {
					$rel_boundary = uniqid('B_REL_');
					$rel_boundary_header = 'Content-Type: multipart/related; boundary="'.$rel_boundary.'"';

					if (isset($last_boundary)) {
						$body .= '--'.$last_boundary.$this->newline.$rel_boundary_header;
					} else {
						$hdr .= $rel_boundary_header;
					}

					$last_boundary = $rel_boundary;
				}

				if ($this->getProtocol() === 'mail') {
					$this->headerString .= $hdr;
				}

				static::strlen($body) && $body .= $this->newline.$this->newline;
				$body .= $this->getMimeMessage().$this->newline.$this->newline
					.'--'.$last_boundary.$this->newline

					.'Content-Type: multipart/alternative; boundary="'.$alt_boundary.'"'.$this->newline.$this->newline
					.'--'.$alt_boundary.$this->newline

					.'Content-Type: text/plain; charset='.$this->charset.$this->newline
					.'Content-Transfer-Encoding: '.$this->getEncoding().$this->newline.$this->newline
					.$this->getAltMessage().$this->newline.$this->newline
					.'--'.$alt_boundary.$this->newline

					.'Content-Type: text/html; charset='.$this->charset.$this->newline
					.'Content-Transfer-Encoding: quoted-printable'.$this->newline.$this->newline

					.$this->prepareQuotedPrintable($this->body).$this->newline.$this->newline
					.'--'.$alt_boundary.'--'.$this->newline.$this->newline;

				if (!empty($rel_boundary)) {
					$body .= $this->newline.$this->newline;
					$this->appendAttachments($body, $rel_boundary, 'related');
				}

				// multipart/mixed attachments
				if (!empty($atc_boundary)) {
					$body .= $this->newline.$this->newline;
					$this->appendAttachments($body, $atc_boundary, 'mixed');
				}

				break;
		}

		$this->finalBody = ($this->getProtocol() === 'mail') ? $body : $hdr.$this->newline.$this->newline.$body;

		return true;
	}

	protected function attachmentsHaveMultipart($type)
	{
		foreach ($this->attachments as &$attachment) {
			if ($attachment['multipart'] === $type){
				return true;
			}
		}

		return false;
	}

	/**
	 * Prepares attachment string
	 *
	 * @param	string	$body		Message body to append to
	 * @param	string	$boundary	Multipart boundary
	 * @param	string	$multipart	When provided, only attachments of this type will be processed
	 * @return	string
	 */
	protected function appendAttachments(&$body, $boundary, $multipart = null)
	{
		for ($i = 0, $c = count($this->attachments); $i < $c; $i++) {
			if (isset($multipart) && $this->attachments[$i]['multipart'] !== $multipart) {
				continue;
			}

			$name = isset($this->attachments[$i]['name'][1]) ? $this->attachments[$i]['name'][1] : basename($this->attachments[$i]['name'][0]);

			$body .= '--'.$boundary.$this->newline
				.'Content-Type: '.$this->attachments[$i]['type'].'; name="'.$name.'"'.$this->newline
				.'Content-Disposition: '.$this->attachments[$i]['disposition'].';'.$this->newline
				.'Content-Transfer-Encoding: base64'.$this->newline
				.(empty($this->attachments[$i]['cid']) ? '' : 'Content-ID: <'.$this->attachments[$i]['cid'].'>'.$this->newline)
				.$this->newline
				.$this->attachments[$i]['content'].$this->newline;
		}

		// $name won't be set if no attachments were appended,
		// and therefore a boundary wouldn't be necessary
		empty($name) or $body .= '--'.$boundary.'--';
	}

	/**
	 * Prep Quoted Printable
	 *
	 * Prepares string for Quoted-Printable Content-Transfer-Encoding
	 * Refer to RFC 2045 http://www.ietf.org/rfc/rfc2045.txt
	 *
	 * @param	string
	 * @return	string
	 */
	protected function prepareQuotedPrintable($str)
	{
		// ASCII code numbers for "safe" characters that can always be
		// used literally, without encoding, as described in RFC 2049.
		// http://www.ietf.org/rfc/rfc2049.txt
		static $ascii_safe_chars = array(
			// ' (  )   +   ,   -   .   /   :   =   ?
			39, 40, 41, 43, 44, 45, 46, 47, 58, 61, 63,
			// numbers
			48, 49, 50, 51, 52, 53, 54, 55, 56, 57,
			// upper-case letters
			65, 66, 67, 68, 69, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90,
			// lower-case letters
			97, 98, 99, 100, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 113, 114, 115, 116, 117, 118, 119, 120, 121, 122
		);

		// We are intentionally wrapping so mail servers will encode characters
		// properly and MUAs will behave, so {unwrap} must go!
		$str = str_replace(array('{unwrap}', '{/unwrap}'), '', $str);

		// RFC 2045 specifies CRLF as "\r\n".
		// However, many developers choose to override that and violate
		// the RFC rules due to (apparently) a bug in MS Exchange,
		// which only works with "\n".
		if ($this->crlf === "\r\n") {
			return quoted_printable_encode($str);
		}

		// Reduce multiple spaces & remove nulls
		$str = preg_replace(array('| +|', '/\x00+/'), array(' ', ''), $str);

		// Standardize newlines
		if (strpos($str, "\r") !== false) {
			$str = str_replace(array("\r\n", "\r"), "\n", $str);
		}

		$escape = '=';
		$output = '';

		foreach (explode("\n", $str) as $line) {
			$length = static::strlen($line);
			$temp = '';

			// Loop through each character in the line to add soft-wrap
			// characters at the end of a line " =\r\n" and add the newly
			// processed line(s) to the output (see comment on $crlf class property)
			for ($i = 0; $i < $length; $i++) {
				// Grab the next character
				$char = $line[$i];
				$ascii = ord($char);

				// Convert spaces and tabs but only if it's the end of the line
				if ($ascii === 32 or $ascii === 9) {
					if ($i === ($length - 1)) {
						$char = $escape.sprintf('%02s', dechex($ascii));
					}
				}
				// DO NOT move this below the $ascii_safe_chars line!
				//
				// = (equals) signs are allowed by RFC2049, but must be encoded
				// as they are the encoding delimiter!
				elseif ($ascii === 61) {
					$char = $escape.strtoupper(sprintf('%02s', dechex($ascii)));  // =3D
				} elseif (!in_array($ascii, $ascii_safe_chars, true)) {
					$char = $escape.strtoupper(sprintf('%02s', dechex($ascii)));
				}

				// If we're at the character limit, add the line to the output,
				// reset our temp variable, and keep on chuggin'
				if ((static::strlen($temp) + static::strlen($char)) >= 76) {
					$output .= $temp.$escape.$this->crlf;
					$temp = '';
				}

				// Add the character to our temporary line
				$temp .= $char;
			}

			// Add our completed line to the output
			$output .= $temp.$this->crlf;
		}

		// get rid of extra CRLF tacked onto the end
		return static::substr($output, 0, static::strlen($this->crlf) * -1);
	}

	/**
	 * Prep Q Encoding
	 *
	 * Performs "Q Encoding" on a string for use in email headers.
	 * It's related but not identical to quoted-printable, so it has its
	 * own method.
	 *
	 * @param	string
	 * @return	string
	 */
	protected function prepareQEncoding($str)
	{
		$str = str_replace(array("\r", "\n"), '', $str);

		if ($this->charset === 'UTF-8') {
			// Note: We used to have mb_encode_mimeheader() as the first choice
			//       here, but it turned out to be buggy and unreliable. DO NOT
			//       re-add it! -- Narf
			if (ICONV_ENABLED === true) {
				$output = @iconv_mime_encode('', $str,
					array(
						'scheme' => 'Q',
						'line-length' => 76,
						'input-charset' => $this->charset,
						'output-charset' => $this->charset,
						'line-break-chars' => $this->crlf
					)
				);

				// There are reports that iconv_mime_encode() might fail and return false
				if ($output !== false) {
					// iconv_mime_encode() will always put a header field name.
					// We've passed it an empty one, but it still prepends our
					// encoded string with ': ', so we need to strip it.
					return static::substr($output, 2);
				}

				$chars = iconv_strlen($str, 'UTF-8');
			} elseif (MB_ENABLED === true) {
				$chars = mb_strlen($str, 'UTF-8');
			}
		}

		// We might already have this set for UTF-8
		isset($chars) or $chars = static::strlen($str);

		$output = '=?'.$this->charset.'?Q?';
		for ($i = 0, $length = static::strlen($output); $i < $chars; $i++) {
			$chr = ($this->charset === 'UTF-8' && ICONV_ENABLED === true) ? '='.implode('=', str_split(strtoupper(bin2hex(iconv_substr($str, $i, 1, $this->charset))), 2)) : '='.strtoupper(bin2hex($str[$i]));

			// RFC 2045 sets a limit of 76 characters per line.
			// We'll append ?= to the end of each line though.
			if ($length + ($l = static::strlen($chr)) > 74) {
				$output .= '?='.$this->crlf // EOL
					.' =?'.$this->charset.'?Q?'.$chr; // New line
				$length = 6 + static::strlen($this->charset) + $l; // Reset the length for the new line
			} else {
				$output .= $chr;
				$length += $l;
			}
		}

		// End the header
		return $output.'?=';
	}

	/**
	 * Send Email
	 *
	 * @param	bool	$auto_clear = true
	 * @return	bool
	 */
	public function send($template = null, array $data = null, $auto_clear = true)
	{
		if ($template) {
			$this->view->setTemplateDirectory('mailables');
			$this->message(($template instanceof ViewModel) ? $template : $this->view->render($template, $data));
		}
		if (!isset($this->headers['From'])) {
			$this->setErrorMessage('lang:email_no_from');
			return false;
		}

		if ($this->replyToFlag === false) {
			$this->addReplyTo($this->headers['From']);
		}

		if (!isset($this->recipients) &&!isset($this->headers['To']) &&!isset($this->bccArray) &&!isset($this->headers['Bcc']) &&!isset($this->headers['Cc'])) {
			$this->setErrorMessage('lang:email_norecipients');
			return false;
		}

		$this->buildHeaders();

		if ($this->bccBatchMode && count($this->bccArray) > $this->bccBatchSize) {
			$result = $this->batchBccSend();

			if ($result && $auto_clear) {
				$this->clear();
			}

			return $result;
		}

		if ($this->buildMessage() === false) {
			return false;
		}

		$result = $this->spoolEmail();

		if ($result && $auto_clear) {
			$this->clear();
		}

		return $result;
	}

	/**
	 * Batch Bcc Send. Sends groups of BCCs in batches
	 *
	 * @return	void
	 */
	public function batchBccSend()
	{
		$float = $this->bccBatchSize - 1;
		$set = '';
		$chunk = [];

		for ($i = 0, $c = count($this->bccArray); $i < $c; $i++) {
			if (isset($this->bccArray[$i])) {
				$set .= ', '.$this->bccArray[$i];
			}

			if ($i === $float) {
				$chunk[] = static::substr($set, 1);
				$float += $this->bccBatchSize;
				$set = '';
			}

			if ($i === $c-1) {
				$chunk[] = static::substr($set, 1);
			}
		}

		for ($i = 0, $c = count($chunk); $i < $c; $i++) {
			unset($this->headers['Bcc']);

			$bcc = $this->cleanEmail($this->stringToArray($chunk[$i]));

			if ($this->protocol !== 'smtp') {
				$this->setHeader('Bcc', implode(', ', $bcc));
			} else {
				$this->bccArray = $bcc;
			}

			if ($this->buildMessage() === false) {
				return false;
			}

			$this->spoolEmail();
		}
	}

	/**
	 * Unwrap special elements
	 *
	 * @return	void
	 */
	protected function unWrapSpecials()
	{
		$this->finalBody = preg_replace_callback('/\{unwrap\}(.*?)\{\/unwrap\}/si', array($this, 'removeNlCallback'), $this->finalBody);
	}

	/**
	 * Strip line-breaks via callback
	 *
	 * @param	string	$matches
	 * @return	string
	 */
	protected function removeNlCallback($matches)
	{
		if (strpos($matches[1], "\r") !== false or strpos($matches[1], "\n") !== false) {
			$matches[1] = str_replace(array("\r\n", "\r", "\n"), '', $matches[1]);
		}

		return $matches[1];
	}

	/**
	 * Spool mail to the mail server
	 *
	 * @return	bool
	 */
	protected function spoolEmail()
	{
		$this->unWrapSpecials();

		$protocol = $this->getProtocol();
		$method   = 'sendWith'.ucfirst($protocol);
		if (!$this->$method()) {
			$this->setErrorMessage('lang:email_send_failure_'.($protocol === 'mail' ? 'phpmail' : $protocol));
			return false;
		}

		$this->setErrorMessage('lang:email_sent', $protocol);
		return true;
	}

	/**
	 * Validate email for shell
	 *
	 * Applies stricter, shell-safe validation to email addresses.
	 * Introduced to prevent RCE via sendmail's -f option.
	 *
	 * @see	https://github.com/bcit-ci/CodeIgniter/issues/4963
	 * @see	https://gist.github.com/Zenexer/40d02da5e07f151adeaeeaa11af9ab36
	 * @license	https://creativecommons.org/publicdomain/zero/1.0/	CC0 1.0, Public Domain
	 *
	 * Credits for the base concept go to Paul Buonopane <paul@namepros.com>
	 *
	 * @param	string	$email
	 * @return	bool
	 */
	protected function validateEmailForShell(&$email)
	{
// 		if (function_exists('idn_to_ascii') && $atpos = strpos($email, '@')) {
// 			$email = static::substr($email, 0, ++$atpos).idn_to_ascii(static::substr($email, $atpos));
// 		}

		return (filter_var($email, FILTER_VALIDATE_EMAIL) === $email && preg_match('#\A[a-z0-9._+-]+@[a-z0-9.-]{1,253}\z#i', $email));
	}

	/**
	 * Send using mail()
	 *
	 * @return	bool
	 */
	protected function sendWithMail()
	{
		if (is_array($this->recipients)) {
			$this->recipients = implode(', ', $this->recipients);
		}

		// validateEmailForShell() below accepts by reference,
		// so this needs to be assigned to a variable
		$from = $this->cleanEmail($this->headers['Return-Path']);

		if ($this->safeModel === true ||!$this->validateEmailForShell($from)) {
			return mail($this->recipients, $this->subject, $this->finalBody, $this->headerString);
		} else {
			// most documentation of sendmail using the "-f" flag lacks a space after it, however
			// we've encountered servers that seem to require it to be in place.
			return mail($this->recipients, $this->subject, $this->finalBody, $this->headerString, '-f '.$from);
		}
	}

	/**
	 * Send using Sendmail
	 *
	 * @return	bool
	 */
	protected function sendWithSendmail()
	{
		// validateEmailForShell() below accepts by reference,
		// so this needs to be assigned to a variable
		$from = $this->cleanEmail($this->headers['From']);
		if ($this->validateEmailForShell($from)) {
			$from = '-f '.$from;
		} else {
			$from = '';
		}

		if (false === ($fp = @popen($this->mailPath.' -oi '.$from.' -t', 'w'))) {
			// server probably has popen disabled, so nothing we can do to get a verbose error.
			return false;
		}

		fputs($fp, $this->headerString);
		fputs($fp, $this->finalBody);

		$status = pclose($fp);

		if ($status !== 0) {
			$this->setErrorMessage('lang:email_exit_status', $status);
			$this->setErrorMessage('lang:email_no_socket');
			return false;
		}

		return true;
	}

	/**
	 * Send using SMTP
	 *
	 * @return	bool
	 */
	protected function sendWithSmtp()
	{
		if ($this->smtp_host === '') {
			$this->setErrorMessage('lang:email_no_hostname');
			return false;
		}

		if (!$this->smtpConnect() or!$this->smtpAuthenticate()) {
			return false;
		}

		if (!$this->sendCommand('from', $this->cleanEmail($this->headers['From']))) {
			$this->smtpEnd();
			return false;
		}

		foreach ($this->recipients as $val) {
			if (!$this->sendCommand('to', $val)) {
				$this->smtpEnd();
				return false;
			}
		}

		if (count($this->ccArray) > 0) {
			foreach ($this->ccArray as $val) {
				if ($val !== '' &&!$this->sendCommand('to', $val)) {
					$this->smtpEnd();
					return false;
				}
			}
		}

		if (count($this->bccArray) > 0) {
			foreach ($this->bccArray as $val) {
				if ($val !== '' &&!$this->sendCommand('to', $val)) {
					$this->smtpEnd();
					return false;
				}
			}
		}

		if (!$this->sendCommand('data')) {
			$this->smtpEnd();
			return false;
		}

		// perform dot transformation on any lines that begin with a dot
		$this->sendData($this->headerString.preg_replace('/^\./m', '..$1', $this->finalBody));

		$this->sendData('.');

		$reply = $this->getSmtpData();
		$this->setErrorMessage($reply);

		$this->smtpEnd();

		if (strpos($reply, '250') !== 0) {
			$this->setErrorMessage('lang:email_smtp_error', $reply);
			return false;
		}

		return true;
	}

	/**
	 * SMTP End
	 *
	 * Shortcut to send RSET or QUIT depending on keep-alive
	 *
	 * @return	void
	 */
	protected function smtpEnd()
	{
		($this->smtp_keepalive) ? $this->sendCommand('reset') : $this->sendCommand('quit');
	}

	/**
	 * SMTP Connect
	 *
	 * @return	string
	 */
	protected function smtpConnect()
	{
		if (is_resource($this->smtpConnect)) {
			return true;
		}

		$ssl = ($this->smtp_crypto === 'ssl') ? 'ssl://' : '';

		$this->smtpConnect = fsockopen($ssl.$this->smtp_host, $this->smtp_port, $errno, $errstr, $this->smtp_timeout);

		if (!is_resource($this->smtpConnect)) {
			$this->setErrorMessage('lang:email_smtp_error', $errno.' '.$errstr);
			return false;
		}

		stream_set_timeout($this->smtpConnect, $this->smtp_timeout);
		$this->setErrorMessage($this->getSmtpData());

		if ($this->smtp_crypto === 'tls') {
			$this->sendCommand('hello');
			$this->sendCommand('starttls');

			$crypto = stream_socket_enable_crypto($this->smtpConnect, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

			if ($crypto !== true) {
				$this->setErrorMessage('lang:email_smtp_error', $this->getSmtpData());
				return false;
			}
		}

		return $this->sendCommand('hello');
	}

	/**
	 * Send SMTP command
	 *
	 * @param	string
	 * @param	string
	 * @return	bool
	 */
	protected function sendCommand($cmd, $data = '')
	{
		switch ($cmd) {
			case 'hello' :

				if ($this->smtp_auth or $this->getEncoding() === '8bit') {
					$this->sendData('EHLO '.$this->getHostName());
				} else {
					$this->sendData('HELO '.$this->getHostName());
				}

						$resp = 250;
			break;
			case 'starttls'	:

				$this->sendData('STARTTLS');
				$resp = 220;
			break;
			case 'from' :

				$this->sendData('MAIL FROM:<'.$data.'>');
				$resp = 250;
			break;
			case 'to' :

				if ($this->dsn) {
					$this->sendData('RCPT TO:<'.$data.'> NOTIFY=SUCCESS,DELAY,FAILURE ORCPT=rfc822;'.$data);
				} else {
					$this->sendData('RCPT TO:<'.$data.'>');
				}

				$resp = 250;
			break;
			case 'data'	:

				$this->sendData('DATA');
				$resp = 354;
			break;
			case 'reset':

				$this->sendData('RSET');
				$resp = 250;
			break;
			case 'quit'	:

				$this->sendData('QUIT');
				$resp = 221;
			break;
		}

		$reply = $this->getSmtpData();

		$this->debugMessage[] = '<pre>'.$cmd.': '.$reply.'</pre>';

		if ((int) static::substr($reply, 0, 3) !== $resp) {
			$this->setErrorMessage('lang:email_smtp_error', $reply);
			return false;
		}

		if ($cmd === 'quit') {
			fclose($this->smtpConnect);
		}

		return true;
	}

	/**
	 * SMTP Authenticate
	 *
	 * @return	bool
	 */
	protected function smtpAuthenticate()
	{
		if (!$this->smtp_auth) {
			return true;
		}

		if ($this->smtp_user === '' && $this->smtp_pass === '') {
			$this->setErrorMessage('lang:email_no_smtp_unpw');
			return false;
		}

		$this->sendData('AUTH LOGIN');

		$reply = $this->getSmtpData();

		if (strpos($reply, '503') === 0) { // Already authenticated
			return true;
		} elseif (strpos($reply, '334') !== 0) {
			$this->setErrorMessage('lang:email_failed_smtp_login', $reply);
			return false;
		}

		$this->sendData(base64_encode($this->smtp_user));

		$reply = $this->getSmtpData();

		if (strpos($reply, '334') !== 0) {
			$this->setErrorMessage('lang:email_smtp_auth_un', $reply);
			return false;
		}

		$this->sendData(base64_encode($this->smtp_pass));

		$reply = $this->getSmtpData();

		if (strpos($reply, '235') !== 0) {
			$this->setErrorMessage('lang:email_smtp_auth_pw', $reply);
			return false;
		}

		if ($this->smtp_keepalive) {
			$this->smtp_auth = false;
		}

		return true;
	}

	/**
	 * Send SMTP data
	 *
	 * @param	string	$data
	 * @return	bool
	 */
	protected function sendData($data)
	{
		$data .= $this->newline;
		for ($written = $timestamp = 0, $length = static::strlen($data); $written < $length; $written += $result) {
			if (($result = fwrite($this->smtpConnect, static::substr($data, $written))) === false) {
				break;
			}
			// See https://bugs.php.net/bug.php?id=39598 and http://php.net/manual/en/function.fwrite.php#96951
			elseif ($result === 0) {
				if ($timestamp === 0) {
					$timestamp = time();
				} elseif ($timestamp < (time() - $this->smtp_timeout)) {
					$result = false;
					break;
				}

				usleep(250000);
				continue;
			} else {
				$timestamp = 0;
			}
		}

		if ($result === false) {
			$this->setErrorMessage('lang:email_smtp_data_failure', $data);
			return false;
		}

		return true;
	}

	/**
	 * Get SMTP data
	 *
	 * @return	string
	 */
	protected function getSmtpData()
	{
		$data = '';

		while ($str = fgets($this->smtpConnect, 512)) {
			$data .= $str;

			if ($str[3] === ' ') {
				break;
			}
		}

		return $data;
	}

	/**
	 * Get Hostname
	 *
	 * There are only two legal types of hostname - either a fully
	 * qualified domain name (eg: "mail.example.com") or an IP literal
	 * (eg: "[1.2.3.4]").
	 *
	 * @link	https://tools.ietf.org/html/rfc5321#section-2.3.5
	 * @link	http://cbl.abuseat.org/namingproblems.html
	 * @return	string
	 */
	protected function getHostName()
	{
		if (isset($_SERVER['SERVER_NAME'])) {
			return $_SERVER['SERVER_NAME'];
		}

		return isset($_SERVER['SERVER_ADDR']) ? '['.$_SERVER['SERVER_ADDR'].']' : '[127.0.0.1]';
	}

	/**
	 * Get Debug Message
	 *
	 * @param	array	$include	List of raw data chunks to include in the output
	 *					Valid options are: 'headers', 'subject', 'body'
	 * @return	string
	 */
	public function printDebugger($include = array('headers', 'subject', 'body'))
	{
		$msg = '';

		if (count($this->debugMessage) > 0) {
			foreach ($this->debugMessage as $val) {
				$msg .= $val;
			}
		}

		// Determine which parts of our raw data needs to be printed
		$raw_data = '';
		is_array($include) || $include = array($include);

		if (in_array('headers', $include, true)) {
			$raw_data = htmlspecialchars($this->headerString)."\n";
		}

		if (in_array('subject', $include, true)) {
			$raw_data .= htmlspecialchars($this->subject)."\n";
		}

		if (in_array('body', $include, true)) {
			$raw_data .= htmlspecialchars($this->finalBody);
		}

		return $msg.($raw_data === '' ? '' : '<pre>'.$raw_data.'</pre>');
	}

	/**
	 * Set Message
	 *
	 * @param	string	$msg
	 * @param	string	$val = ''
	 * @return	void
	 */
	protected function setErrorMessage($msg, $val = '')
	{
		if (sscanf($msg, 'lang:%s', $line) !== 1) {
			$this->debugMessage[] = str_replace('%s', $val, $msg).'<br />';
		} else {
			$this->debugMessage[] = str_replace('%s', $val, $line).'<br />';
		}
	}

	/**
	 * Mime Types
	 *
	 * @param	string
	 * @return	string
	 */
	protected function mimeTypes($ext = '')
	{
		$ext = strtolower($ext);

		$mimes =& get_mimes();

		if (isset($mimes[$ext])) {
			return is_array($mimes[$ext]) ? current($mimes[$ext]) : $mimes[$ext];
		}

		return 'application/x-unknown-content-type';
	}

	/**
	 * Destructor
	 *
	 * @return	void
	 */
	public function __destruct()
	{
		is_resource($this->smtpConnect) && $this->sendCommand('quit');
	}

	/**
	 * Byte-safe strlen()
	 *
	 * @param	string	$str
	 * @return	int
	 */
	protected static function strlen($str)
	{
		return (static::$functionOverload) ? mb_strlen($str, '8bit') : strlen($str);
	}

	/**
	 * Byte-safe substr()
	 *
	 * @param	string	$str
	 * @param	int	$start
	 * @param	int	$length
	 * @return	string
	 */
	protected static function substr($str, $start, $length = NULL)
	{
		if (static::$functionOverload){
			// mb_substr($str, $start, null, '8bit') returns an empty
			// string on PHP 5.3
			isset($length) or $length = ($start >= 0 ? static::strlen($str) - $start : -$start);
			return mb_substr($str, $start, $length, '8bit');
		}

		return isset($length) ? substr($str, $start, $length) : substr($str, $start);
	}
}