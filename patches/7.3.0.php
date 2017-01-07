<?php
$db = DevblocksPlatform::getDatabaseService();
$logger = DevblocksPlatform::getConsoleLog();
$settings = DevblocksPlatform::getPluginSettingsService();
$tables = $db->metaTables();

// ===========================================================================
// Migrate Twilio setup to `connected_account` table (if exists)

$api_sid = $settings->get('wgm.twilio', 'api_sid', null);
$api_token = $settings->get('wgm.twilio', 'api_token', null);
$default_caller_id = $settings->get('wgm.twilio', 'default_caller_id', null);

if(!is_null($api_sid) || !is_null($api_token)) {
	$params = [
		'api_sid' => $api_sid,
		'api_token' => $api_token,
		'default_caller_id' => $default_caller_id,
	];
	
	$id = DAO_ConnectedAccount::create([
		DAO_ConnectedAccount::NAME => 'Twilio',
		DAO_ConnectedAccount::EXTENSION_ID => 'wgm.twilio.service.provider',
		DAO_ConnectedAccount::OWNER_CONTEXT => 'cerberusweb.contexts.app',
		DAO_ConnectedAccount::OWNER_CONTEXT_ID => 0,
	]);
	
	DAO_ConnectedAccount::setAndEncryptParams($id, $params);
	
	$settings->delete('wgm.twilio', ['api_sid','api_token','default_caller_id']);
	
	// [TODO] Update VA actions that use the Twilio account access
}

// ===========================================================================
// Finish up

return TRUE;
