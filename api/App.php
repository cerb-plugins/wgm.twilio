<?php
if(class_exists('Extension_PageMenuItem')):
class WgmTwilio_SetupPluginsMenuItem extends Extension_PageMenuItem {
	const POINT = 'wgmtwilio.setup.menu.plugins.twilio';
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('extension', $this);
		$tpl->display('devblocks:wgm.twilio::setup/menu_item.tpl');
	}
};
endif;

if(class_exists('Extension_PageSection')):
class WgmTwilio_SetupSection extends Extension_PageSection {
	const ID = 'wgmtwilio.setup.twilio';
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();

		$visit = CerberusApplication::getVisit();
		$visit->set(ChConfigurationPage::ID, 'twilio');
		
		$params = array(
			'api_sid' => DevblocksPlatform::getPluginSetting('wgm.twilio','api_sid',''),
			'api_token' => DevblocksPlatform::getPluginSetting('wgm.twilio','api_token',''),
			'default_caller_id' => DevblocksPlatform::getPluginSetting('wgm.twilio','default_caller_id',''),
		);
		$tpl->assign('params', $params);
		
		$tpl->display('devblocks:wgm.twilio::setup/index.tpl');
	}
	
	function saveJsonAction() {
		try {
			@$api_sid = DevblocksPlatform::importGPC($_REQUEST['api_sid'],'string','');
			@$api_token = DevblocksPlatform::importGPC($_REQUEST['api_token'],'string','');
			@$default_caller_id = DevblocksPlatform::importGPC($_REQUEST['default_caller_id'],'string','');
			
			if(empty($api_sid) || empty($api_token))
				throw new Exception("Both API fields are required.");
			
			DevblocksPlatform::setPluginSetting('wgm.twilio','api_sid',$api_sid);
			DevblocksPlatform::setPluginSetting('wgm.twilio','api_token',$api_token);
			DevblocksPlatform::setPluginSetting('wgm.twilio','default_caller_id',$default_caller_id);
			
			echo json_encode(array('status'=>true,'message'=>'Saved!'));
			return;
			
		} catch (Exception $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;
			
		}
		
	}
};
endif;

class WgmTwilio_API {
	static $_instance = null;
	private $_api_sid = null;
	private $_api_token = null;
	private $_api_version = '2010-04-01';
	private $_default_caller_id = '';
	private $_twilio = null;
	
	private function __construct() {
		$this->_api_sid = DevblocksPlatform::getPluginSetting('wgm.twilio','api_sid','');
		$this->_api_token = DevblocksPlatform::getPluginSetting('wgm.twilio','api_token','');
		$this->_default_caller_id = DevblocksPlatform::getPluginSetting('wgm.twilio','default_caller_id','');
		$this->_twilio = new TwilioRestClient($this->_api_sid, $this->_api_token);
	}
	
	/**
	 * @return WgmTwilio_API
	 */
	static public function getInstance() {
		if(null == self::$_instance) {
			self::$_instance = new WgmTwilio_API();
		}

		return self::$_instance;
	}
	
	/**
	 *
	 * @param string $rel_path
	 * @param string $method
	 * @param array $vars
	 * @return TwilioRestResponse
	 */
	public function request($rel_path, $method, $vars) {
		$rel_path = ltrim($rel_path,'/');
				
		$path = sprintf("/%s/Accounts/%s/%s",
			$this->_api_version,
			$this->_api_sid,
			$rel_path
		);
		return $this->_twilio->request($path, $method, $vars);
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
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		$tpl->display('devblocks:wgm.twilio::events/action_send_sms_twilio.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$twilio = WgmTwilio_API::getInstance();
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		
		if(false === ($sms_to = $tpl_builder->build(@$params['phone'], $dict)))
			$sms_to = null;
		
		if(empty($sms_to)) {
			return "[ERROR] No destination phone number.";
		}
		
		// Translate message tokens
		if(false !== ($content = $tpl_builder->build(@$params['content'], $dict))) {
			$out = sprintf(">>> Sending SMS via Twilio\nFrom: %s\nTo: %s\n\n%s\n",
				$twilio->getDefaultCallerId(),
				$sms_to,
				$content
			);
		}
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$twilio = WgmTwilio_API::getInstance();
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		
		// Translate message tokens
		$sms_to = $tpl_builder->build(@$params['phone'], $dict);
		
		if(empty($sms_to)) {
			return;
		}
		
		// Translate message tokens
		$content = $tpl_builder->build(@$params['content'], $dict);
		
		$data = array(
			"From" => $twilio->getDefaultCallerId(),
			"To" => $sms_to,
			"Body" => $content
		);
		$response = $twilio->request('/SMS/Messages', 'POST', $data);
	}
};
endif;
