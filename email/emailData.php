<?php

// email arrays
$email_data = [
	"contact" => [
		"email_flag" => "autoresponder",
		"title" => "Message received!",
		"link" => "http://www.songfarm.ca/email/contactConfirmationEmail.html",
		"logo" => [
			"source" => "http://www.songfarm.ca/images/logo_email_s.png",
			"width" => "158",
			"height" => "48"
		],
		"header" => "Message received!",
		"intro" => "Thanks for taking the time to get in touch!",
		"body" => "We will view your message and may get back to you within the next 24 hours.",
		"signature" => "Thank you,<br />
		The Songfarm Team",
		"year" => date("Y")
	],
	"support" => [
		"email_flag" => "autoresponder",
		"title" => "Support Request",
		"link" => "http://www.songfarm.ca/email/supportConfirmationEmail.html",
		"logo" => [
			"source" => "http://www.songfarm.ca/images/logo_email_s.png",
			"width" => "158",
			"height" => "48"
		],
		"header" => "We're sorry you're experiencing problems! :(",
		"intro" => "At Songfarm, we strive to make your experience pleasant and enjoyable, so we're sorry you're having a hard time.",
		"body" => "You're receiving this email to notify you that we have received your support request and will reply to you within the next 24 hours.",
		"signature" => "Thank you for your patience and understanding,<br />
		The Songfarm Team",
		"year" => date("Y")
	],
	"confirmation" => [
		"email_flag" => "songcircle_confirmation",
		"title" => "Songcircle Registration Confirmation",
		"logo" => [ // may user alternate logo
			"source" => "http://www.songfarm.ca/images/songfarm_logo_m.png",
			"width" => "293",
			"height" => "194"
		],
		"header" => "One more step!",
		"greeting" => "Hi %name%,",
		"intro" => "You are almost done registering for <b>%event%</b> on %date_time%.",
		"body" => "Please confirm your registration by clicking the link below:",
		"ctaLink" => [
			"linkLocation" => "http://test.songfarm.ca/includes/songcircleConfirmUser.php?conference_id=%conference_id%&user_email=%email%&confirmation_key=%confirmation_key%",
			"linkText" => "Confirm Registration"
		],
		"signature" => "Thanks!<br />
		The Songfarm Team",
		"directive" => "You are receiving this email because you registered for a Songcircle on <a href=\"http://songfarm.ca\" style=\"text-decoration: none; color: #153643;\"><font color=\"#153643\">songfarm.ca</font></a><br />
		If you received this email in error or do not wish to receive any further communications from Songfarm, please ",
		"unsubscribeLink" => [
			"unsubscribeLinkLocation" => "..//includes/unsubscribe.php?user_email=%email%" // href for unsubscribe/deletion
		],
		"year" => date("Y")
	]
];
// end of data


?>