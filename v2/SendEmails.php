<?php

require_once 'vendor/autoload.php';
require_once 'config.php';

class SendEmails {
	
	public $fromEmail = 'LetsPlay@snapshotPlay.com';
	public $fromName = 'Snapshot Play';
	function __construct() {
		//do nothing	
	}
	public function send($to,$subject,$plainMessage,$htmlMessage=null) {
		$email = new SendGrid\Mail\Mail(); 
		$email->setFrom($this->fromEmail, $this->fromName);
		$email->setSubject($subject);
		//check if $to is an array then loop through and add to emails as needed
		if(is_array($to)) {
			foreach($to as $emailAddress) {
				$email->addTo($emailAddress);
			}
		} else {
			$email->addTo($to);
		}
		//add html message if it exists
		if(!empty($htmlMessage)) {
			$email->addContent("text/html", $htmlMessage);
		}	
		//add plain message
		$email->addContent("text/plain", $plainMessage);
		$sendgrid = new SendGrid(SENDGRID_API_KEY);
		try {
			//var_dump($email);
			$response = $sendgrid->send($email);
			return $response;
		} catch (Exception $e) {
			echo 'Email error - Caught exception: '. $e->getMessage() ."\n";
		}
	}
	
	 function generateHTMLMessage($resetCode) {
		$htmlMessage = '<html><body>';
		$htmlMessage .= '<h1>Password Reset Code</h1>';
		$htmlMessage .= '<p>You have requested to reset your password. Please use the following 6-digit code:</p>';
	
		// Include the reset code dynamically
		$htmlMessage .= '<div style="background-color: #f0f0f0; border: 1px solid #ccc; padding: 10px; display: inline-block;">';
		$htmlMessage .= '<h2 style="font-size: 24px; color: #333; margin: 0;">' . $resetCode . '</h2>';
		$htmlMessage .= '</div>';
	
		$htmlMessage .= '<p>This code is valid for a single use and will expire shortly.</p>';
		$htmlMessage .= '<p>If you didn\'t request this password reset, please disregard this email.</p>';
		$htmlMessage .= '<p>Thank you!</p>';
		$htmlMessage .= '</body></html>';
		
		return $htmlMessage;
	}
	
	function generateSupportEmailHTML($data) {
		$html = '<!DOCTYPE html>
		<html>
		<body>
			<h2>Results:</h2>
			<pre>' . print_r($data, true) . '</pre>
		</body>
		</html>';
		
			return $html;
		}

// Example notes JSON:
// 	$notes = '{
//     "withdrawGameResults": {
//         "3": {
//             "status": "success",
//             "message": "The host has successfully removed user 2 from game 3"
//         },
//         "4": {
//             "status": "error",
//             "message": "File not found."
//         }
//     },
//     "deactivateRecordsResults": {
//         "deactivateGamesXUsersResults": {
//             "status": "success",
//             "message": "1 row(s) updated in database"
//         }
//     },
//     "deleteSnapshotsResults": {
//         "deleteResult": {
//             "3": {
//                 "status": "error",
//                 "message": "File not found."
//             }
//         }
//     },
//     "updateUser": {
//         "status": "error",
//         "message": "Update Failed: SQLSTATE[22001]: String data, right truncated..."
//     }
// }';



}