<?php 



function wp_mail($to, $subject, $content, $headers='')
{
	
	require_once('Swift/lib/Swift.php');
	require_once('Swift/lib/Swift/Connection/SMTP.php');
	require_once('mail_setup.php');
	
	$st_smtp_config['port'] = MAIL_SMTP_PORT;
	$st_smtp_config['ssl'] = MAIL_SMTP_SSL;
	$st_smtp_config['server'] = MAIL_SMTP_SERVER;
	$st_smtp_config['username'] = MAIL_SMTP_USERNAME;
	$st_smtp_config['password'] = MAIL_SMTP_PASSWORD;
	
	


/* CONNECT TO SMTP */
	if ($st_smtp_config['port'] == "25"){ //standard port 25 connect
		$smtp =& new Swift_Connection_SMTP("localhost");
	}elseif ($st_smtp_config['port'] == 465 && $st_smtp_config['ssl'] == 'ssl'){ //standard SSL connection
		$smtp =& new Swift_Connection_SMTP($st_smtp_config['server'], SWIFT_SMTP_PORT_SECURE, SWIFT_SMTP_ENC_SSL);
	}elseif ($st_smtp_config['port'] == 465 && $st_smtp_config['ssl'] == 'tls'){ //standard TLS connection
		$smtp =& new Swift_Connection_SMTP($st_smtp_config['server'], SWIFT_SMTP_PORT_SECURE, SWIFT_SMTP_ENC_TLS);
	}else{ //SSL or TLS on a non-standard port (arbitrary)
		$smtp =& new Swift_Connection_SMTP($st_smtp_config['server'], $st_smtp_config['port'], $st_smtp_config['ssl']);
	}
/* AUTHENTICATE SMTP */
	$smtp->setUsername($st_smtp_config['username']);
	$smtp->setpassword($st_smtp_config['password']);
	 
	$swift =& new Swift($smtp);	
	
/* CHECK HEADERS */
		$attached = strpos($content, 'Content-Disposition: attachment;');
		$hasbcc = strpos($headers, 'Bcc:');
		$replyto = strpos($headers, 'From:');
		$html = strpos($headers, 'text/html');

/* CREATE MESSAGE */	
	if($html === false){
		$message =& new Swift_Message($subject, $content);
	}else{
		$message =& new Swift_Message($subject, $content, "text/html");
	}
/* SET FROM ADDRESS */
	if ($replyto === false){
			$from = MAIL_SMTP_FROMADD;
	}else{	
			$from = strstr($headers, 'From:');
			$from = ereg_replace("[\n\r\t]", "{|}", $from);
			$from = explode('{|}', $from);
			//print_r($from);
			$from = $from[0];
			$from = str_replace('From:', '', $from);
			$from = ltrim($from);
			$from = explode('<', $from);
			$from = '"'.trim($from[0]).'" <'.$from[1];
			$from = rtrim($from, '<');
			$from = str_replace('"', '', $from);
			$from = rtrim($from, ' ');
	}	

/* CHECK FOR ATTACHMENT */
	if ($attached === false){ }else{
				$messd = nl2br($content);
				$mess = explode('<br />', $messd);
				foreach($mess as $x){
					$app .= rtrim(strstr($x, 'application'), ';');
					$filename .= rtrim(str_replace('filename="', '', strstr($x, 'filename')), '"');
					$plain = strpos($x, 'text/plain');
					if( $plain === false){}else{
						$text = true;
					}
					$end = strpos($x, 'application/');
					if($end === false){}else{
						$text = false;
					}
					if($text = true){
							$count = 0;
							if($count > 0){
								$tmsg .= $x."\n";
							}
							$count = $count + 1;
					}
				}
				$name = $filename;
				$file = ABSPATH . WP_BACKUP_DIR . '/' . $filename;
				$message->attach(new Swift_Message_Attachment(  new Swift_File($file), $name, $app));
				
		/* CHECK FOR MESSAGE */
		
		$message->attach(new Swift_Message_Part($tmsg));
	}
	
/* CHECK FOR BCC */
	if ($hasbcc === false){}else{
				$headbr = nl2br($headers);
				$head = explode('<br />', $headbr);
				foreach($head as $h){
					if (strpos($h, '@') == TRUE){
						$e = ereg_replace("[\n\r\t]", "\t", $h);
						$e = str_replace('Bcc:', '', $e);
						$e = ltrim($e);
						$bcc[] = array('', rtrim($e, ','));
					}
				}
				unset($bcc[0]);
				unset($bcc[1]);
				$bcc= array_values($bcc);
				$recipients =& new Swift_RecipientList();
				foreach($bcc as $rec){
					$recipients->addTo($rec);
				}
				$batch = true;
	}

/* SEND EMAIL */
	if($batch == true){;
		if ($swift->batchSend($message, $recipients, $from)) {return true;}
		else{ echo "Message failed to send to ".$to." from ".$from;}
	}else{
		if ($swift->send($message, $to, $from)) {return true;}
		else{ echo "Message failed to send to ".$to." from ".$from;}
	}
	$swift->disconnect();
	
}

?>