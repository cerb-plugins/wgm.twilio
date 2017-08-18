<?php
$db = DevblocksPlatform::services()->database();
$logger = DevblocksPlatform::services()->log();
$settings = DevblocksPlatform::services()->pluginSettings();
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
	
	// Update VA actions that use the Twilio account access
	
	$sql = "select id, params_json from decision_node where params_json like '%wgmtwilio.event.action.send_sms%'";
	$results = $db->GetArrayMaster($sql);
	
	foreach($results as $row) {
		$is_changed = false;
		
		if(false == ($json = json_decode($row['params_json'], true)))
			continue;
		
		foreach($json['actions'] as &$action) {
			if($action['action'] == 'wgmtwilio.event.action.send_sms') {
				$action['connected_account_id'] = $id;
				$is_changed = true;
			}
		}
		
		if($is_changed) {
			$db->ExecuteMaster(sprintf("UPDATE decision_node SET params_json = %s WHERE id = %d",
				$db->qstr(json_encode($json)),
				$row['id']
			));
		}
	}
}

// ===========================================================================
// Finish up

return TRUE;
