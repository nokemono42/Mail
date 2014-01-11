<?php 
/**
* A PHP class for sending email messages. Options include sending plaintext, html, multipart messages, and attachments.
*
* @url https://github.com/stlewis/Mail
* @author Steve Lewis <steve@thoughtsandrambles.com>
* @version 0.1.0
* @package Mail
*
**/

class Mail {
	/**
	* @var string $to The email address, (or addresses, if comma separated), to be sent.
	* @var string $from The email address delivered from.
	* @var string $subject The subject line.
	* @var string $text_content The plaintext version of the message.
	* @var string $html_content The HTML version of the message.
	* @var string $body The complete body of the email, including all mixed content.
	* @var array $attachments An array of the file paths to the attachments.
	* @var array $headers An array of the headers.
	* @var string $header_string The string, (and therefore final), representation of the headers.
	* @var string $boundary_hash The separator between the various mixed parts of the email.
	* @var boolean $sent Was email message was successfully sent?
	**/
	public $to;
	public $from;
	public $subject;
	public $text_content;
	public $html_content;
	public $body;
	public $attachments;
	public $headers;
	public $header_string; 
	public $boundary_hash;
	public $sent;

	/**
	* Initialization of a Mail object.
	**/
	public function __construct($to, $from, $subject, $text_content = "", $html_content = "") {
		$this->to = $to;
		$this->from = $from;
		$this->subject = $subject;
		$this->text_content = $text_content;
		$this->html_content = $html_content;
		$this->body = "";
		$this->attachments = array();
		$this->headers = array();
		$this->boundary_hash = md5(date('r', time()));
	}

	public function send() {
		$this->prepare_headers();
		$this->prepare_body();
		if (!empty($this->attachments)) {
			$this->prepare_attachments();
		}
		$this->sent = mail($this->to, $this->subject, $this->body, $this->header_string);
		return $this->sent;
	}

	/**
	* @param string $header Add a new header to the message. Note: must be a properly formatted email header.
	**/
	public function add_header($header) {
		$this->headers[] = $header;
	}

	/**
	* @param string $file The path to the file attachment.
	**/
	public function add_attachment($file) {
		$this->attachments[] = $file;
	}

	private function prepare_body() {
		$this->body .= "--PHP-mixed-{$this->boundary_hash}\n";
		$this->body .= "Content-Type: multipart/alternative; boundary=\"PHP-alt-{$this->boundary_hash}\"\n\n";
		if (!empty($this->text_content)) $this->prepare_text();
		if (!empty($this->html_content)) $this->prepare_html();
		$this->body .= "--PHP-alt-{$this->boundary_hash}--\n\n";
	}

	private function prepare_headers() {
		$this->set_default_headers();
		$this->header_string = implode("\r\n", $this->headers) . "\r\n";
	}

	private function set_default_headers() {
		$this->headers[] = 'MIME-Version: 1.0';
		$this->headers[] = "From: {$this->from}";
		$this->headers[] = "To: {$this->to}";
		$this->headers[] = "Subject: {$this->subject}";
		# Default assumes a multi-part message. Parts: HTML, text version, and attachment.
		$this->headers[] = "Content-type: multipart/mixed; boundary=\"PHP-mixed-{$this->boundary_hash}\"";
	}

	private function prepare_attachments() {
		foreach($this->attachments as $attachment) {
			$file_name = basename($attachment);
			$this->body .= "--PHP-mixed-{$this->boundary_hash}\n";
			$this->body .= "Content-Type: application/octet-stream; name=\"{$file_name}\"\n";
			$this->body .= "Content-Transfer-Encoding: base64\n";
			$this->body .= "Content-Disposition: attachment\n\n";
			$this->body .= chunk_split(base64_encode(file_get_contents($attachment)));
			$this->body .= "\n\n";
		}
		$this->body .= "--PHP-mixed-{$this->boundary_hash}--\n\n";
	}

	private function prepare_text(){
		$this->body .= "--PHP-alt-{$this->boundary_hash}\n";
		$this->body .= "Content-Type: text/plain; charset=\"iso-8859-1\"\n";
		$this->body .= "Content-Transfer-Encoding: 7bit\n\n";
		$this->body .= $this->text_content . "\n\n";
	}

	private function prepare_html(){
		$this->body .= "--PHP-alt-{$this->boundary_hash}\n";
		$this->body .= "Content-Type: text/html; charset=\"iso-8859-1\"\n";
		$this->body .= "Content-Transfer-Encoding: 7bit\n\n";
		$this->body .= $this->html_content . "\n\n";
	}
}
?>