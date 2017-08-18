<?php
class WgmTwilio_API {
	static $_instance = null;
	private $_api_sid = null;
	private $_api_token = null;
	private $_api_version = '2010-04-01';
	private $_default_caller_id = '';
	private $_twilio = null;
	
	/**
	 * @return WgmTwilio_API
	 */
	public function __construct($api_sid, $api_token, $default_caller_id) {
		$this->_api_sid = $api_sid;
		$this->_api_token = $api_token;
		$this->_default_caller_id = $default_caller_id;
		$this->_twilio = new TwilioRestClient($this->_api_sid, $this->_api_token);
	}
	
	/**
	 *
	 * @param string $rel_path
	 * @param string $method
	 * @param array $vars
	 * @return TwilioRestResponse
	 */
	public function request($rel_path, $method, $vars=array()) {
		$rel_path = ltrim($rel_path,'/');
				
		$path = sprintf("/%s/Accounts/%s/%s",
			$this->_api_version,
			$this->_api_sid,
			$rel_path
		);
		
		$resp = $this->_twilio->request($path, $method, $vars);
		
		return $resp;
	}
	
	/**
	 * @return string
	 */
	public function getDefaultCallerId() {
		return $this->_default_caller_id;
	}
};

if(class_exists('Extension_DevblocksEventAction')):
class WgmTwilio_EventActionSendSms extends Extension_DevblocksEventAction {
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=array(), $seq=null) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		if(isset($params['connected_account_id'])) {
			if(false != ($connected_account = DAO_ConnectedAccount::get($params['connected_account_id']))) {
				if(Context_ConnectedAccount::isReadableByActor($connected_account, $active_worker))
					$tpl->assign('connected_account', $connected_account);
			}
		}
		
		$tpl->display('devblocks:wgm.twilio::events/action_send_sms_twilio.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		if(false === ($sms_from = $tpl_builder->build(@$params['from'], $dict)))
			$sms_from = $twilio->getDefaultCallerId();
		
		if(false === ($sms_to = $tpl_builder->build(@$params['phone'], $dict)))
			$sms_to = null;
		
		if(!isset($params['connected_account_id']) || empty($params['connected_account_id']))
			return "[ERROR] No connected account is configured.";
		
		if(false == ($connected_account = DAO_ConnectedAccount::get($params['connected_account_id'])))
			return "[ERROR] Invalid connected account.";
		
		if(!Context_ConnectedAccount::isReadableByActor($connected_account, $trigger->getBot()))
			return "[ERROR] This bot is not authorized to use this connected account.";
		
		$credentials = $connected_account->decryptParams();
		
		@$api_sid = $credentials['api_sid'];
		@$api_token = $credentials['api_token'];
		@$default_caller_id = $credentials['default_caller_id'];
		
		if(empty($api_sid) || empty($api_token))
			return "[ERROR] The connected account credentials are invalid.";
			
		if(empty($sms_to)) {
			return "[ERROR] No destination phone number was provided.";
		}
		
		// Translate message tokens
		if(false !== ($content = $tpl_builder->build(@$params['content'], $dict))) {
			$out = sprintf(">>> Sending SMS via Twilio\nFrom: %s\nTo: %s\n\n%s\n",
				$sms_from,
				$sms_to,
				$content
			);
		}
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		if(false == ($connected_account_id = @$params['connected_account_id']))
			return;
		
		if(false == ($connected_account = DAO_ConnectedAccount::get($connected_account_id)))
			return false;
		
		if(!Context_ConnectedAccount::isReadableByActor($connected_account, $trigger->getBot()))
			return false;
		
		$credentials = $connected_account->decryptParams();
		
		@$api_sid = $credentials['api_sid'];
		@$api_token = $credentials['api_token'];
		@$default_caller_id = $credentials['default_caller_id'];
		
		if(empty($api_sid) || empty($api_token))
			return false;
		
		$twilio = new WgmTwilio_API($api_sid, $api_token, $default_caller_id);
		
		// Translate message tokens
		if(false == ($sms_from = $tpl_builder->build(@$params['from'], $dict)))
			$sms_from = $twilio->getDefaultCallerId();
		
		if(false == ($sms_to = $tpl_builder->build(@$params['phone'], $dict)))
			return;
		
		// Translate message tokens
		$content = $tpl_builder->build(@$params['content'], $dict);
		
		$data = array(
			"From" => $sms_from,
			"To" => $sms_to,
			"Body" => $content
		);
		$response = $twilio->request('/SMS/Messages', 'POST', $data);
	}
};
endif;

class ServiceProvider_Twilio extends Extension_ServiceProvider implements IServiceProvider_HttpRequestSigner {
	const ID = 'wgm.twilio.service.provider';
	
	function renderConfigForm(Model_ConnectedAccount $account) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$params = $account->decryptParams($active_worker);
		$tpl->assign('params', $params);
		
		$tpl->display('devblocks:wgm.twilio::provider/edit_params.tpl');
	}
	
	function saveConfigForm(Model_ConnectedAccount $account, array &$params) {
		@$edit_params = DevblocksPlatform::importGPC($_POST['params'], 'array', array());
		
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!isset($edit_params['api_sid']) || empty($edit_params['api_sid']))
			return "The 'Account SID' is required.";
		
		if(!isset($edit_params['api_token']) || empty($edit_params['api_token']))
			return "The 'Auth Token' is required.";
		
		if(!isset($edit_params['default_caller_id']) || empty($edit_params['default_caller_id']))
			return "The 'Default Caller ID' is required.";
		
		// Test the credentials
		
		$twilio = new WgmTwilio_API($edit_params['api_sid'], $edit_params['api_token'], $edit_params['default_caller_id']);
		
		$resp = $twilio->request('.json', 'GET');
		
		if($resp->IsError)
			return $resp->ErrorMessage;
		
		$json = json_decode($resp->ResponseText, true);
		
		if(!isset($json['friendly_name']))
			return "The given account could not be verified.";
		
		foreach($edit_params as $k => $v)
			$params[$k] = $v;
		
		return true;
	}
	
	function authenticateHttpRequest(Model_ConnectedAccount $account, &$ch, &$verb, &$url, &$body, &$headers) {
		$credentials = $account->decryptParams();
		
		if(
			!isset($credentials['api_sid'])
			|| !isset($credentials['api_token'])
		)
			return false;
		
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, sprintf("%s:%s", $credentials['api_sid'], $credentials['api_token']));
		return true;
	}
};