<?php require_once('initialize.php');
require_once(EMAIL_PATH.DS.'email_data.php');
// if request comes from a remote address
if(isset($_SERVER['REMOTE_ADDR'])){
	die('Permission Denied');
} else {
	if(isset($argv)){

		$cronlog_location 	= SITE_ROOT.'/logs/cronjobs/cronlog_'.date("m-d-Y").'.txt';
		$errorLog_location = SITE_ROOT.'/logs/cronjobs/error_'.date("m-d-Y").'.txt';
		$server_time = $currentUTCTime = date("H:i:s",strtotime('+4 hours')); // converts detroit time to UTC

		foreach ($argv as $arg) {
			switch ($arg) {

			/**
			* Check State of Songcircle
			*
			* NOTE: Cron runs on 1,16,31,46 minute of every hour
			*/
				case 'songcircle_state':
					// log call
					file_put_contents($cronlog_location,$server_time.' songcircle_state'.PHP_EOL,FILE_APPEND);

				// if Incomplete Songcircles Exist (Not Started OR Started)
				if($result = $songcircle->getIncompleteSongcircles()){

					foreach ($result as $songcircle_result) {

						$songcircle_id = $songcircle_result['songcircle_id'];
						// date of songcircle in minutes (in UTC)
						$date_of_songcircle = floor( strtotime($songcircle_result['date_of_songcircle']) / 60 );
						// duration of songcircle in minutes
						$duration_of_songcircle = ( strtotime($songcircle_result['duration']) - strtotime('today') ) / 60;
						// current time plus 4 hours (for UTC)
						$currentUTCTime = date("G:i:s",strtotime('+4 hours'));
						// current time in minutes
						$currentUTCTime = floor( strtotime($currentUTCTime) / 60);
						// get different in MINUTES between now and $date_of_songcircle
						$diff_in_minutes = floor( ($currentUTCTime - $date_of_songcircle) );

					  // if Songcircle has started and hasn't finished yet AND current status isn't already set to 1
						if ( ($diff_in_minutes >= 0) && ($diff_in_minutes < $duration_of_songcircle)
							&& ($songcircle_result['songcircle_status'] != 1) ){

							// set status to 1 (started)
							$songcircle_status = 1;
							$songcircle->updateSongcircleState($songcircle_id,$songcircle_status);

							file_put_contents($cronlog_location,$server_time.' -- UPDATED_STATUS - '.$songcircle_id.' Status '.$songcircle_status.PHP_EOL,FILE_APPEND);

						}
						// if Songcircle has ended AND current status isn't already set to 5
						elseif ( ($diff_in_minutes > $duration_of_songcircle) && ($songcircle_result['songcircle_status'] != 5 ) ){

							// set status to 5 (complete)
							$songcircle_status = 5;
							$songcircle->updateSongcircleState($songcircle_id,$songcircle_status);

							file_put_contents($cronlog_location,$server_time.' -- UPDATED_STATUS - '.$songcircle_id.' Status '.$songcircle_status.PHP_EOL,FILE_APPEND);

						}
						else
						{
							file_put_contents($cronlog_location,$server_time.' -- no action - '.$songcircle_id.PHP_EOL, FILE_APPEND);
						}
					} // end of: foreach ($result as $songcircle_result)
				} // end of: if($result = $songcircle->getIncompleteSongcircles())
				else
				{
					file_put_contents($cronlog_location,$server_time.' -- no songcircles found'.PHP_EOL,FILE_APPEND);
				}
					break;

			/**
			* Sends reminder email, RELATIVE TO USER TIMEZONE, 3 days out from event
			*
			* NOTE: Runs on 3 & 34
			*
			*/
				case 'reminder_songcircle':

				// write to log
				file_put_contents($cronlog_location,$server_time.' reminder_songcircle'.PHP_EOL,FILE_APPEND);

				$sql = "SELECT sc.songcircle_id, songcircle_name, date_of_songcircle, sr.user_id, ur.user_name, user_email,
								ut.full_timezone, TIMESTAMPDIFF( MINUTE, CONVERT_TZ( now(), @@global.time_zone, '+0:00' ), date_of_songcircle ) AS diff
								FROM songcircle_create AS sc,	songcircle_register AS sr
								INNER JOIN user_register AS ur ON sr.user_id = ur.user_id
								INNER JOIN user_timezone AS ut ON ur.user_id = ut.user_id
								WHERE sc.songcircle_id = sr.songcircle_id
								AND sc.songcircle_status = 0
								AND sr.confirm_status = 1
								AND TIMESTAMPDIFF( MINUTE, CONVERT_TZ( now(), @@global.time_zone, '+0:00' ), date_of_songcircle ) > 1425
								AND TIMESTAMPDIFF( MINUTE, CONVERT_TZ( now(), @@global.time_zone, '+0:00' ), date_of_songcircle ) < 1440";
								/**
								* NOTE: 1410 - 1440 only denote a 1 day difference. Ratify this to denote 3 days (4305-4320)
								*/

				if($result = $db->getRows($sql)){

					// foreach result/user
					foreach($result as $songcircle_user_data){

						// query for user information
						if( $user->setUserData($songcircle_user_data['user_id']) ){

							// convert time of songcircle to user timezone
							$songcircle_user_data['date_of_songcircle'] = $songcircle->callUserTimezone($songcircle_user_data['date_of_songcircle'],$user->timezone);

							// construct email
							$to = "{$user->username} <{$user->user_email}>";
							$subject = $songcircle_user_data['songcircle_name']." is happening soon!";
							$from = "Songfarm <noreply@songfarm.ca>";
							if($message = initiateEmail($email_data['first_reminder'],$songcircle_user_data)){
								$headers = "From: {$from}\r\n";
								$headers.= "Content-Type: text/html; charset=utf-8";

								// if mail successful
								if( mail($to,$subject,$message,$headers,'-fsongfarm') ){

									file_put_contents($cronlog_location, $server_time.' -- Email Sent: User '.$songcircle_user_data['user_id'].', '.$songcircle_user_data['songcircle_id'].PHP_EOL,FILE_APPEND);

								}
								else
								{
									// could not send email
										// log error
										file_put_contents($errorLog_location,$server_time.' '.error_get_last().' -- FAILED: Could not send email ('.$_SERVER['PHP_SELF'].__LINE__.') to '.$songcircle_user_data['user_id'].' for '.$songcircle_user_data['songcircle_id'].' ('.$songcircle_user_data['songcircle_name'].') on '.$songcircle_user_data['date_of_songcircle'].PHP_EOL,FILE_APPEND);
								}

							} // end of: if($message = initiateEmail($email_data['reminder_email'],$songcircle_user_data))
							else
							{
								// could not construct email
									// log error
									file_put_contents($errorLog_location,$server_time.' '.error_get_last().' -- FAILED: Could not construct email ('.$_SERVER['PHP_SELF'].__LINE__.') '.$songcircle_user_data['songcircle_id'].' ('.$songcircle_user_data['songcircle_name'].') '.$songcircle_user_data['date_of_songcircle'].PHP_EOL,FILE_APPEND);
							}

						} // end of: if($user->setUserData($songcircle_user_data['user_id']))
						else
						{
							// No user information available for given user id
								// log error
								file_put_contents($errorLog_location,$server_time.' '.error_get_last().' -- FAILED: No user information available for user id: '.$songcircle_user_data['user_id']. '('.$_SERVER['PHP_SELF'].__LINE__.') '.PHP_EOL,FILE_APPEND);
						}
					} // end of: foreach($result as $row)

				} // end of: if($result = $db->getRows($sql))
				else
				{
					// TEST
					// write to log
					file_put_contents($cronlog_location,$server_time.' -- no action'.PHP_EOL,FILE_APPEND);
				}
					break;

			/**
			* Sends join link to user, RELATIVE TO USER TIMEZONE, 15 minutes before event
			*
			* NOTE: Cron runs every 15 minutes
			*/
				case 'join_songcircle':

				// write to log
				file_put_contents($cronlog_location,$server_time.' join_songcircle'.PHP_EOL,FILE_APPEND);

				$sql="SELECT sc.songcircle_id, songcircle_name, date_of_songcircle, sr.user_id, verification_key,
							confirm_status, ur.user_name, user_email, ut.full_timezone, TIMESTAMPDIFF( MINUTE, CONVERT_TZ( now(),   @@global.time_zone, '+0:00' ), date_of_songcircle ) AS diff
							FROM songcircle_create AS sc, songcircle_register AS sr
							INNER JOIN user_register AS ur ON sr.user_id = ur.user_id
							INNER JOIN user_timezone AS ut ON ur.user_id = ut.user_id
							WHERE sc.songcircle_id = sr.songcircle_id
							AND sc.songcircle_status = 0
							AND sr.confirm_status = 1
							-- get songcircles where difference between now (UTC) and date of songcircle (also UTC) is 1 hour
							AND TIMESTAMPDIFF( MINUTE, CONVERT_TZ( now(), @@global.time_zone, '+0:00' ), date_of_songcircle ) > 50
							AND TIMESTAMPDIFF( MINUTE, CONVERT_TZ( now(), @@global.time_zone, '+0:00' ), date_of_songcircle ) < 70";
							/**
							* NOTE: constraints determined to find time area approx. 1 hr before songcircle
							*/

				if($result = $db->getRows($sql)){

					foreach($result as $songcircle_user_data){

						// file_put_contents($cronlog_location, $server_time.' -- Join link sent - User '.$songcircle_user_data['user_id'].'  '.$songcircle_user_data['songcircle_id'].PHP_EOL,FILE_APPEND);

						// construct email
						$to = "{$songcircle_user_data['user_name']} <{$songcircle_user_data['user_email']}>";
						$subject = "Join ".$songcircle_user_data['songcircle_name']." now!";
						$from = "Songfarm <noreply@songfarm.ca>";
						if( $message = initiateEmail($email_data['join_songcircle'],$songcircle_user_data) ){
							$headers = "From: {$from}\r\n";
							$headers.= "Content-Type: text/html; charset=utf-8";
							if( mail($to,$subject,$message,$headers,'-fsongfarm') )
							{
								file_put_contents($cronlog_location,$server_time.' -- Join link sent - User '.$songcircle_user_data['user_id'].', '.$songcircle_user_data['songcircle_id'].' diff: '.$songcircle_user_data['diff'].PHP_EOL,FILE_APPEND);
							}
							else
							{
								// could not send email
								file_put_contents($errorLog_location,$server_time.' '.error_get_last().' -- FAILED: Could not send email ('.$_SERVER['PHP_SELF'].__LINE__.') '.$songcircle_user_data['songcircle_id'].' ('.$songcircle_user_data['songcircle_name'].') '.$songcircle_user_data['date_of_songcircle'].PHP_EOL,FILE_APPEND);
							}

						} // end of: if($message = initiateEmail())
						else
						{
							// could not construct email
							file_put_contents($errorLog_location,$server_time.' '.error_get_last().' -- FAILED: Could not construct email ('.$_SERVER['PHP_SELF'].__LINE__.') '.$songcircle_user_data['songcircle_id'].' ('.$songcircle_user_data['songcircle_name'].') '.$songcircle_user_data['date_of_songcircle'].PHP_EOL,FILE_APPEND);
						}

					} // end of: foreach($result as $row)
				} // end of: if($result = $db->getRows($sql))
				else
				{
					// write to log
					file_put_contents($cronlog_location,$server_time.' -- no action'.PHP_EOL,FILE_APPEND);
				}
					break;

			/**
			* Clears completed/expired songcircles
			*
			* NOTE: Runs every 30 minutes on the 1/31
			*/
				case 'clear_expired_songcircle':

				// write to log
				file_put_contents($cronlog_location, $server_time.' clear_expired_songcircle'.PHP_EOL,FILE_APPEND);

				// if songcircle start time + duration of songcircle is less than current time (in UTC)
				$sql = "SELECT id, songcircle_id, date_of_songcircle ";
				$sql.= "FROM songcircle_create ";
				$sql.= "WHERE DATE_ADD(date_of_songcircle, INTERVAL duration HOUR_SECOND) < DATE_ADD(NOW(), INTERVAL '5' HOUR)";

				if($result = $db->getRows($sql)){

					foreach ($result as $songcircle) {

						// get id for expired
						$id = $songcircle['id'];
						$songcircle_id = $songcircle['songcircle_id'];

						// delete record by id
						$sql = "DELETE FROM songcircle_create WHERE id = $id LIMIT 1";
						if($result = $db->query($sql)){
							if(mysqli_affected_rows($db->connection) > 0){
								// if successful delete, write to log
								file_put_contents($cronlog_location,$server_time.' -- REMOVED_EXPIRED: '.$songcircle_id.' '.$songcircle['date_of_songcircle'].PHP_EOL, FILE_APPEND);
							} else {
								// failed to delete record
								// log error
								file_put_contents($errorLog_location,$server_time.' '.error_get_last().' -- FAILED: No record deleted ('.$_SERVER['PHP_SELF'].__LINE__.') id: '.$id.', songcircle_id: '.$songcircle_id.PHP_EOL,FILE_APPEND);
							}
						} // end of: if($result = $db->query($sql))
					} // end of: foreach ($result as $songcircle)
				}
				else
				{
					// if NO result, write to log
					file_put_contents($cronlog_location,$server_time.' -- no action'.PHP_EOL,FILE_APPEND);
				}
					break;

			} // end of: switch ($arg)
		} // end of: foreach ($argv as $arg)
	} // end of: if(isset($argv)
}
