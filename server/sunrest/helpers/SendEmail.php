<?php 



use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../services/lib/PHPMailer/src/Exception.php';
require __DIR__ . '/../services/lib/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../services/lib/PHPMailer/src/SMTP.php';


//include(__DIR__ . '/../services/lib/PHPMailer/PHPMailerAutoload.php');
//include(__DIR__ . '/../services/lib/PHPMailer/class.smtp.php');

class SendEmail extends PHPMailer {
	
	private $mail;
	
	private $debug = 0;
	
	private $debugOutput = 'html';
	
	
	
	function __construct() {
		//SMTP needs accurate times, and the PHP time zone MUST be set
		//This should be done in your php.ini, but this is how to do it if you don't have access to that
		date_default_timezone_set('Etc/UTC');
		//Create a new PHPMailer instance
		//$this->mail = new PHPMailer();
		//Tell PHPMailer to use SMTP
		$this->isSMTP();
		
		// L'smtp di default
		$this->setSmtp('mail1');
		
		//Enable SMTP debugging
		// 0 = off (for production use)
		// 1 = client messages
		// 2 = client and server messages
		$this->SMTPDebug = $this->debug;

		//Ask for HTML-friendly debug output
		$this->Debugoutput = $this->debugOutput;
		
	}
	
	public function setSmtp($smtp) {
		switch($smtp) {
			case 'mail1':
				$this->Host = 'mail.myhost.com';
				$this->Port = 587;
				$this->SMTPSecure = 'tls';
				$this->SMTPAuth = true;
				$this->Username = 'info@myhost.com';
				$this->Password = '';
				break;
			case 'mail2':
				$this->Host = 'smtp.myhost2.com';
				$this->Port = 587;
				$this->SMTPSecure = 'tls';
				$this->SMTPAuth = true;
				$this->Username = '';
				$this->Password = '';				
				break;
		}
	}
	
	/**
	 * Setta l'indirizzo e il nome del destinatario
	 * Se si e' nell'ambiente di sviluppo invia tutto a
	 * un'email predefinita
	 * {@inheritDoc}
	 * @see PHPMailer::addAddress()
	 */
	public function addAddress($email, $name=null) {
		if (DEV_ENV) {
			$email = TEST_EMAIL;
			$name = "Test email";
		}
		parent::addAddress($email, $name);
	}
	
	/**
	 * Setta l'indirizzo e il nome del conoscente nascosto
	 * Se si e' nell'ambiente di sviluppo invia tutto a
	 * un'email predefinita
	 * {@inheritDoc}
	 * @see PHPMailer::addAddress()
	 */
	public function addBCC($email, $name=null) {
		if (DEV_ENV) {
			$email = TEST_EMAIL;
			$name = "Test email";
		}
		parent::addBCC($email, $name);
	}	
	
	
	
/*
	

	
	//Set who the message is to be sent from
	$mail->setFrom('from@example.com', 'First Last');
	
	//Set an alternative reply-to address
	$mail->addReplyTo('replyto@example.com', 'First Last');
	
	//Set who the message is to be sent to
	$mail->addAddress('nicoladimatteo@gmail.com', 'Nicola');
	
	//Set the subject line
	$mail->Subject = 'PHPMailer GMail SMTP test';
	
	//Read an HTML message body from an external file, convert referenced images to embedded,
	//convert HTML into a basic plain-text alternative body
	//$mail->msgHTML(file_get_contents('contents.html'), dirname(__FILE__));
	$mail->Body = 'prova';
	
	//Replace the plain text body with one created manually
	$mail->AltBody = 'This is a plain-text message body';
	
	//Attach an image file
	//$mail->addAttachment('images/phpmailer_mini.png');
	
	//send the message, check for errors
	if (!$mail->send()) {
		echo "Mailer Error: " . $mail->ErrorInfo;
	} else {
		echo "Message sent!";
	}	
	
}

*/
}

?>
