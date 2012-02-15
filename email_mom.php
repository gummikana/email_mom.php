<?php

/*

email_mom.php is a small script that emails my mom, when I'm abroad to tell her that I'm alive.

My mom worries to unrational degree about my well being when I'm travelling. And she never reads 
her emails. So I decided to automate this process of informing my mom that I'm alive when I'm abroad.
This small php script checks the ip address of the visitor calling it and if that ip address is not
in Finland it'll generate a small email to my mom, mostly telling her where I am and that I'm alive.
It does this emailing only once in 24 hours. 

This script is called from a small python script that runs in the background and tries to call this
script every hour or so. So if I'm using my laptop (which I usually am) and I have internet connection
this should automatically make me a better son.


*/

// Your gmail user name. NOT THE @gmail.com part. If you don't want to use gmail to mail things, 
// retweak swift/mail_setup.php
define( "MAIL_SMTP_USERNAME", "YOUR_GMAIL_USER_NAME" );

// Your gmail password
define( "MAIL_SMTP_PASSWORD", "YOUR_GMAIL_PASSWORD" );

// Email address of your mom
define( "MOM_EMAIL_ADDRESS", "EMAIL_ADDRESS_OF_YOUR_MOM" );

// Some kind of protection that none without the api password can call this site
define( "API_PASSWORD", "SOME_KINDA_OF_API_PASSWORD" );

// on top of this, the script uses 4 .txt files to write things into. 
// You might have to create them and and change the permissions so that the script can write to them
// The files are:
// log.txt
// day_count.txt
// prev_email_time.txt
// prev_location.txt
//

require_once('geoloc_translations.php');
require_once('swift/mailer.php');


function log_visit() {
        $ip = $_SERVER['REMOTE_ADDR'];
        $ref = $_SERVER["HTTP_REFERER"];
        $bro = $_SERVER["HTTP_USER_AGENT"];
        $date = date("H:i:s d/m/Y");
        $logname = "log.txt";

        $fp = fopen( $logname, "a" );
        fwrite($fp, "$ip|$date|$regcode|$ref|$bro|$file\n");
        fclose($fp);
}

function get_prev_email_time() {
	$filename = "prev_email_time.txt";
	$fp = fopen( $filename, "r" );
	$contents = fread($fp, filesize($filename));
	fclose($fp);
	return intval( $contents );
}

function write_email_time() {
	$fp = fopen( "prev_email_time.txt", "w" );
	fwrite($fp, "" . time());
	fclose($fp);
}

function get_day_count() {
	$filename = "day_count.txt";
	$fp = fopen( $filename, "r" );
	$contents = fread($fp, filesize($filename));
	fclose($fp);
	return intval( $contents );
}

function write_day_count( $daycount ) {
	$fp = fopen( "day_count.txt", "w" );
	fwrite($fp, "" . $daycount );
	fclose($fp);
}


function get_prev_location() {
	$filename = "prev_location.txt";
	$fp = fopen( $filename, "r" );
	$contents = fread($fp, filesize($filename));
	fclose($fp);
	return $contents;
}

function write_location( $what ) {
	$fp = fopen( "prev_location.txt", "w" );
	fwrite($fp, $what );
	fclose($fp);
}

function parse_value( $data, $begining_part, $end_part, $start_here = 0 ) {
	$p1 = strpos( $data, $begining_part, $start_here );
	$p2 = strpos( $data, $end_part, $p1 + strlen( $begining_part ) );
	
	$p1 += strlen( $begining_part );
	
	$result = substr( $data, $p1, $p2 - $p1 );
	return $result;
}

function debug_die() {
	die();
}

//-----------------------------------------------------------------------------

log_visit();

//----------------- time since last visit -------------------------------------


$time_since_last_email = ( time() - get_prev_email_time() );
echo "Time since last email: " . $time_since_last_email . "\r\n";

if( $time_since_last_email < 24 * 60 * 60 ) {
	echo "Not enough time has passed since our last email\r\n";
	debug_die();
}

//-------------- get location -------------------------------------------------

$ip = $_SERVER['REMOTE_ADDR']; 

if( isset( $_GET['ip'] ) ) $ip = $_GET['ip'];
if( $_GET['api_key'] != API_PASSWORD ) die();

echo $_SERVER['REMOTE_ADDR'] . "\r\n";
$geo_data = unserialize(file_get_contents('http://www.geoplugin.net/php.gp?ip='.$ip));

foreach ($geo_data as $key => $value) {
    $geo_data[ $key ] = html_entity_decode( $value );
}

print_r( $geo_data );

//----------- daycount --------------------------------------------------------
$daycount = get_day_count();
$daycount++;
write_day_count( $daycount ); 

//----------- parse geolocation -----------------------------------------------
// also write it down
$country = $geo_data['geoplugin_countryName'];
$city = $geo_data['geoplugin_city'];
$country_code = $geo_data['geoplugin_countryCode'];

$combined_city_country = "";
if( $city != "" ) 
	$combined_city_country  = $city . ", " . $country;
else 
	$combined_city_country  = $country;

$prev_combined_city_country = get_prev_location();

write_location( $combined_city_country );


$translated_country = $translation_country_codes[ $country_code ];
if( $translated_country == "" ) $translated_country = $country;

// these are to add the human element of error :)
if( rand(0,100) < 15 ) $translated_country = strtolower( $translated_country );
if( rand(0,100) < 15 ) $city = strtolower( $city );

$translated_city_country = "";
if( $city != "" ) $translated_city_country  = $city . ", " . $translated_country;
else $translated_city_country  = $translated_country;

if( rand(0,100) < 20 ) $translated_city_country = strtolower( $translated_city_country );


if( $geo_data['geoplugin_countryCode'] == "" &&  $geo_data['geoplugin_countryName'] == "" ) {
	echo "Unknown location...\r\n";
	debug_die();
}

// we're back in finland
if( $daycount > 2 && $combined_city_country != $prev_combined_city_country && $geo_data['geoplugin_countryCode'] == "FI" && $geo_data['geoplugin_countryName'] == "Finland" ) {
	// email mom, that I'm back in finland safely
	require_once('geoloc_email_text.php');
	write_day_count( 0 );
	wp_mail( MOM_EMAIL_ADDRESS, $subject_returned_to_finland[ array_rand( $subject_returned_to_finland ) ], $content_returned_to_finland[ array_rand( $content_returned_to_finland ) ] );
	debug_die();
}

if( $geo_data['geoplugin_countryCode'] == "FI" || $geo_data['geoplugin_countryName'] == "Finland" ) {
	echo "You're in Finland, no need to email mom...\r\n";
	// reset trip counter
	write_day_count( 0 );
	debug_die();
}

//--------------------- weather -----------------------------------------------

// Example output from Google Weather API
/* <?xml version="1.0"?><xml_api_reply version="1"><weather module_id="0" tab_id="0" mobile_row="0" mobile_zipped="1" row="0" section="0" ><forecast_information><city data="Masku, Finland Proper"/><postal_code data="Masku,Finland"/><latitude_e6 data=""/><longitude_e6 data=""/><forecast_date data="2012-02-11"/><current_date_time data="1970-01-01 00:00:00 +0000"/><unit_system data="US"/></forecast_information><current_conditions><condition data="Light snow"/><temp_f data="27"/><temp_c data="-3"/><humidity data="Humidity: 86%"/><icon data="/ig/images/weather/flurries.gif"/><wind_condition data="Wind: SW at 5 mph"/></current_conditions><forecast_conditions><day_of_week data="Sat"/><low data="21"/><high data="28"/><icon data="/ig/images/weather/mostly_sunny.gif"/><condition data="Partly Sunny"/></forecast_conditions><forecast_conditions><day_of_week data="Sun"/><low data="1"/><high data="30"/><icon data="/ig/images/weather/chance_of_snow.gif"/><condition data="Chance of Snow"/></forecast_conditions><forecast_conditions><day_of_week data="Mon"/><low data="16"/><high data="25"/><icon data="/ig/images/weather/fog.gif"/><condition data="Fog"/></forecast_conditions><forecast_conditions><day_of_week data="Tue"/><low data="9"/><high data="25"/><icon data="/ig/images/weather/chance_of_snow.gif"/><condition data="Chance of Snow"/></forecast_conditions></weather></xml_api_reply> */

function Unaccent($string)
{
	return strtr(utf8_decode($string), 
           utf8_decode('ŠŒšœŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏĞÑÒÓÔÕÖØÙÚÛÜİßàáâãäåæçèéêëìíîïğñòóôõöøùúûüıÿ'),
           'SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy');

}
	   
$xmlUrl = 'http://www.google.com/ig/api?weather='.urlencode(strtolower(Unaccent( $geo_data['geoplugin_city'] ) . ',' . Unaccent( $geo_data['geoplugin_countryName'] ))); // XML feed file/URL
$xmlStr = file_get_contents($xmlUrl);


//-- parse weather values -----------------------------------------------------

$temperature_c = parse_value( $xmlStr, 'temp_c data="', '"' );
$weather = parse_value( $xmlStr, 'condition data="', '"' );

$weather = $weather_translations[ strtolower($weather) ];

//---------------------- actual email -----------------------------------------

require_once('geoloc_email_text.php');


// needs a running day counter
$subject = "Matkaraportti $translated_city_country. Päivä " . $daycount;

// echo "\n\n\n";

$email_body = "";

// 90% of hello line
if( rand(0,100) < 85 )
	$email_body .= $hello_line[ array_rand( $hello_line ) ];

// if different location 100%
if( $prev_combined_city_country != $combined_city_country ) {
	$email_body .=$im_abroad_line_part1[ array_rand( $im_abroad_line_part1 ) ];	
	$email_body .=$im_abroad_line_part2[ array_rand( $im_abroad_line_part2 ) ];
} else {
	if( rand( 0,100) < 75 ) 
		$email_body .=$how_are_you_line[ array_rand( $how_are_you_line ) ];
}

// don't worry i'm alive
// 85%
if( rand(0,100) < 85 ) {
	$email_body .=$dont_worry_im_alive_part1[ array_rand( $dont_worry_im_alive_part1 ) ];
	$email_body .=$dont_worry_im_alive_part2[ array_rand( $dont_worry_im_alive_part2 ) ];
}

// if same location  15%
if( $prev_combined_city_country == $combined_city_country && rand(0,100) < 15 ) {
	$email_body .=$im_abroad_line_part1[ array_rand( $im_abroad_line_part1 ) ];
	$email_body .=$im_abroad_line_part2[ array_rand( $im_abroad_line_part2 ) ];
} 

// 50% of weather report
if( $weather != "" && rand( 0, 100 ) < 50 )
	$email_body .=$weather_line[ array_rand( $weather_line ) ];

// 50% of temperature report
if( $temperature_c != "" && rand( 0, 100 ) < 50 ) 
	$email_body .=$temperature_line[ array_rand( $temperature_line ) ];

// if different location 
if( $prev_combined_city_country != $combined_city_country && rand(0,100) < 50 ) {
	$email_body .= "\n\n";
	$email_body .=$how_are_you_line[ array_rand( $how_are_you_line ) ];
}

// 100%
$email_body .="\n\n\nPete";

wp_mail( MOM_EMAIL_ADDRESS, $subject, $email_body );
write_email_time();

echo $subject;
echo "\n\n";
echo $email_body;

?>