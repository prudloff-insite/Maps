<?php

/**
 * Initialization file for the Maps extension.
 * Extension documentation: http://www.mediawiki.org/wiki/Extension:Maps
 *
 * @file Maps.php
 * @ingroup Maps
 *
 * @author Jeroen De Dauw
 */

if( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

define('Maps_VERSION', '0.3');

$egMapsScriptPath = $wgScriptPath . '/extensions/Maps';
$egMapsIP = $IP . '/extensions/Maps';
$egMapsIncludePath = $wgServer . $egMapsScriptPath;

// Include the settings file
require_once($egMapsIP . '/Maps_Settings.php');

$wgExtensionFunctions[] = 'efMapsSetup';

$wgExtensionMessagesFiles['Maps'] = $egMapsIP . '/Maps.i18n.php';

$wgHooks['LanguageGetMagic'][] = 'efMapsFunctionMagic';
$wgHooks['AdminLinks'][] = 'efMapsAddToAdminLinks';

// Autoload the general classes
$wgAutoloadClasses['MapsMapFeature'] = $egMapsIP . '/Maps_MapFeature.php';
$wgAutoloadClasses['MapsBaseMap'] = $egMapsIP . '/Maps_BaseMap.php';
$wgAutoloadClasses['MapsMapper'] = $egMapsIP . '/Maps_Mapper.php';
$wgAutoloadClasses['MapsUtils'] = $egMapsIP . '/Maps_Utils.php';
$wgAutoloadClasses['MapsGeocoder'] = $egMapsIP . '/Maps_Geocoder.php';
$wgAutoloadClasses['MapsBaseGeocoder'] = $egMapsIP . '/Maps_BaseGeocoder.php';

// Add the services
$wgAutoloadClasses['MapsGoogleMaps'] = $egMapsIP . '/GoogleMaps/Maps_GoogleMaps.php';
$wgAutoloadClasses['MapsYahooMaps'] = $egMapsIP . '/YahooMaps/Maps_YahooMaps.php';
$wgAutoloadClasses['MapsOpenLayers'] = $egMapsIP . '/OpenLayers/Maps_OpenLayers.php';

// Array containing all map services made available by Maps.
// This does not reflect the enabled mapping services, see $egMapsAvailableServices in Maps_Settings.php for this.
// Each array item represents a service: the key is the main service name (used in switch statements),
// and the array values are the aliases for the main name (so can also be used as service=alias).
$egMapsServices = array();
$egMapsServices['googlemaps'] = array('aliases' => array('google', 'googlemap', 'gmap', 'gmaps'));
$egMapsServices['openlayers'] = array('aliases' => array('layers', 'openlayer'));
$egMapsServices['yahoomaps'] = array('aliases' => array('yahoo', 'yahoomap', 'ymap', 'ymaps'));
						
/**
 * Initialization function for the Maps extension
 */
function efMapsSetup() {
	global $wgExtensionCredits, $wgOut;	
	global $egMapsDefaultService, $egMapsAvailableServices, $egMapsServices, $egMapsScriptPath, $egMapsDefaultGeoService, $egMapsAvailableGeoServices;

	efMapsValidateGoogleMapsKey();
	
	// Make sure the default service is one of the enabled ones
	$egMapsDefaultService = in_array($egMapsDefaultService, $egMapsAvailableServices) ? $egMapsDefaultService : $egMapsAvailableServices[0];
	$egMapsDefaultGeoService = in_array($egMapsDefaultGeoService, $egMapsAvailableGeoServices) ? $egMapsDefaultGeoService : $egMapsAvailableGeoServices[0];
	
	$services_list = implode(', ', array_keys($egMapsServices));

	wfLoadExtensionMessages( 'Maps' );
	
	$wgExtensionCredits['parserhook'][] = array(
		'path' => __FILE__,
		'name' => wfMsg('maps_name'),
		'version' => Maps_VERSION,
		'author' => array("[http://bn2vs.com Jeroen De Dauw]", "[http://www.mediawiki.org/wiki/User:Yaron_Koren Yaron Koren]", "Robert Buzink", "Matt Williamson", "[http://www.sergeychernyshev.com Sergey Chernyshev]"),
		'url' => 'http://www.mediawiki.org/wiki/Extension:Maps',
		'description' =>  wfMsg( 'maps_desc', $services_list ),
		'descriptionmsg' => wfMsg( 'maps_desc', $services_list ),
	);

	efMapsAddParserHooks();
	
	$wgOut->addScriptFile($egMapsScriptPath . '/MapUtilityFunctions.js');
}

/**
 * Adds the parser function hooks
 */
function efMapsAddParserHooks() {
	global $wgParser;
	
	// A hook to enable the '#display_point' parser function
	$wgParser->setFunctionHook( 'display_point', array('MapsMapper', 'displayPointRender' ));

	// A hook to enable the '#display_adress' parser function
	$wgParser->setFunctionHook( 'display_address', array('MapsMapper', 'displayAddressRender' ));

	// A hook to enable the geocoder parser functions
	$wgParser->setFunctionHook( 'geocode', array('MapsGeocoder', 'renderGeocoder' ));
	$wgParser->setFunctionHook( 'geocodelat' , array('MapsGeocoder', 'renderGeocoderLat' ));
	$wgParser->setFunctionHook( 'geocodelng' , array('MapsGeocoder', 'renderGeocoderLng' ));
}

/**
 * This function ensures backward compatibility with Semantic Google Maps and other extensions
 * using $wgGoogleMapsKey instead of $egGoogleMapsKey.
 */
function efMapsValidateGoogleMapsKey() {
	global $egGoogleMapsKey, $wgGoogleMapsKey;
	
	if (strlen($egGoogleMapsKey) < 1 && isset($wgGoogleMapsKey)) $egGoogleMapsKey = $wgGoogleMapsKey;
}

/**
 * Adds the magic words for the parser functions
 */
function efMapsFunctionMagic( &$magicWords, $langCode ) {
	$magicWords['display_point'] = array( 0, 'display_point' );
	$magicWords['display_address'] = array( 0, 'display_address' );

	$magicWords['geocode'] = array( 0, 'geocode' );
	$magicWords['geocodelat']	= array ( 0, 'geocodelat' );
	$magicWords['geocodelng']	= array ( 0, 'geocodelng' );
	
	return true; // Unless we return true, other parser functions won't get loaded
}

/**
 * Adds a link to Admin Links page
 */
function efMapsAddToAdminLinks(&$admin_links_tree) {
	// TODO: move the documentation link to another section - and make it non dependant on SMW?
    $displaying_data_section = $admin_links_tree->getSection(wfMsg('smw_adminlinks_displayingdata'));
    // Escape if SMW hasn't added links
    if (is_null($displaying_data_section))
        return true;
    $smw_docu_row = $displaying_data_section->getRow('smw');
    wfLoadExtensionMessages('Maps');
    $maps_docu_label = wfMsg('adminlinks_documentation', wfMsg('maps_name'));
    $smw_docu_row->addItem(AlItem::newFromExternalLink("http://www.mediawiki.org/wiki/Extension:Maps", $maps_docu_label));
    return true;
}


