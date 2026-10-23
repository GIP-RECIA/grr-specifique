<?php
/**
 * include/functions.inc.php
 * fichier Bibliothèque de fonctions de GRR
 * Dernière modification : $Date: 2018-02-23 18:00$
 * @author    JeromeB & Laurent Delineau & Marc-Henri PAMISEUX
 * @copyright Copyright 2003-2018 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

require_once __DIR__ . '/../vendor/autoload.php';

class Email{

	public static function Envois ($A, $sujet, $message, $DE, $cc1='', $cc2='') {

		if (Settings::get('grr_mail_method') == 'smtp') {

			$smtp1		= Settings::get('grr_mail_smtp');
			$username	= Settings::get('grr_mail_Username');
			$password	= Settings::get('grr_mail_Password');
			$smtpsecure	= Settings::get('smtp_secure');
			$port		= Settings::get('smtp_port');

			$mail = new \PHPMailer\PHPMailer\PHPMailer();
			$mail->CharSet = 'UTF-8';

			$mail->SMTPDebug = 0;

			$mail->isSMTP();
			//$mail->SMTPOptions = array( 'ssl' => array( 'verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true ) );
			$mail->Host = $smtp1;
			$mail->Username = $username;
			$mail->Password = $password;
			$mail->SMTPSecure = $smtpsecure;	// Enable TLS encryption, `ssl` also accepted
			$mail->Port = $port;

			if (Settings::get('grr_mail_Username') != "") {
				$mail->SMTPAuth = true;
			} else {
				$mail->SMTPAuth = false;
			}

			$mail->setFrom($DE, 'GRR');

			$lesDestinataires = explode(";", $A);
			for($i=0;$i<count($lesDestinataires);$i++){
				if (Settings::get("grr_mail_Bcc") == "y"){
					$mail->addCC($lesDestinataires[$i]);
				} else{
					$mail->addAddress($lesDestinataires[$i]);
				}
			}

			$mail->addReplyTo($DE, 'GRR');


			$mail->isHTML(true);

			$mail->Subject = $sujet;
			$mail->Body = nl2br($message);
			$mail->AltBody = 'Ce message ne peut-être affiché.';

			if(!$mail->send()) {
				echo 'Message could not be sent.';
				echo 'Mailer Error: ' . $mail->ErrorInfo;
			} else {
				//echo 'Message has been sent';
			}

		} else{
			$headers = "From: {$DE}" . "\r\n" .
				"Reply-To: {$DE}" . "\r\n" .
				'X-Mailer: PHP/' . phpversion();

			mail(str_replace(";",",",$A), $sujet, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', iconv('ISO-8859-1', 'UTF-8//IGNORE', str_replace("<br>","",$message))), $headers); //YN selon Rapace sur le forum
		}

	}

}
?>
