<?php 
require_once 'X/VlcShares.php';
require_once 'X/VlcShares/Plugins/Abstract.php';
require_once 'Zend/Config.php';
require_once 'Zend/Controller/Request/Abstract.php';

class X_VlcShares_Plugins_Outputs extends X_VlcShares_Plugins_Abstract {

	public function __construct() {
		
		$this->setPriority('postRegisterVlcArgs', 100) 
			->setPriority('getStreamItems')
			->setPriority('getModeItems')
			->setPriority('preGetSelectionItems')
			->setPriority('getSelectionItems')
			->setPriority('preSpawnVlc', 100) // TODO remove this. Only in dev env
			;
		
	}	

	/**
	 * Give back the link for change modes
	 * and the default config for this location
	 * 
	 * @param string $provider
	 * @param string $location
	 * @param Zend_Controller_Action $controller
	 */
	public function getModeItems($provider, $location, Zend_Controller_Action $controller) {
		
		X_Debug::i('Plugin triggered');
		
		$urlHelper = $controller->getHelper('url');

		$outputLabel = X_Env::_('p_outputs_selection_auto');

		$outputId = $controller->getRequest()->getParam($this->getId(), false);
		
		// search for the output in db
		if ( $outputId !== false ) {
			$output = new Application_Model_Output();
			Application_Model_OutputsMapper::i()->find($outputId, $output);
			if ( $output->getId() !== null ) {
				$outputLabel = $output->getLabel();
			}
		}
		
		return array(
			array(
				'label'	=>	X_Env::_('p_outputs_output').": $outputLabel",
				'link'	=>	X_Env::completeUrl($urlHelper->url(array(
						'action'	=>	'selection',
						'pid'		=>	$this->getId()
					), 'default', false)
				)
			)
		);
		
	}
	
	/**
	 * Show the title in selection page if pid is this id
	 * @param unknown_type $provider
	 * @param unknown_type $location
	 * @param unknown_type $pid
	 * @param Zend_Controller_Action $controller
	 */
	public function preGetSelectionItems($provider, $location, $pid, Zend_Controller_Action $controller) {
		// we want to expose items only if pid is this plugin
		if ( $this->getId() != $pid) return;
			
		X_Debug::i('Plugin triggered');		
		
		$urlHelper = $controller->getHelper('url');
		
		return array(
			array(
				'label' => X_Env::_('p_outputs_selection_title'),
				'link'	=>	X_Env::completeUrl($urlHelper->url()),
			),
		);
	}
	
	/**
	 * Show plugin options
	 * @param unknown_type $provider
	 * @param unknown_type $location
	 * @param unknown_type $pid
	 * @param Zend_Controller_Action $controller
	 */
	public function getSelectionItems($provider, $location, $pid, Zend_Controller_Action $controller) {
		// we want to expose items only if pid is this plugin
		if ( $this->getId() != $pid) return;
		
		X_Debug::i('Plugin triggered');
		
		$urlHelper = $controller->getHelper('url');
		
		// i try to mark current selected sub based on $this->getId() param
		// in $currentSub i get the name of the current profile
		$currentOutput = $controller->getRequest()->getParam($this->getId(), false);

		$return = array(
			array(
				'label'	=>	X_Env::_('p_outputs_selection_auto'),
				'link'	=>	X_Env::completeUrl($urlHelper->url(array(
						'action'	=>	'mode',
						$this->getId() => null, // unset this plugin selection
						'pid'		=>	null
					), 'default', false)
				),
				'highlight' => ($currentOutput === false)
			)
		);
		
		
		// check for infile subs
		$outputs = Application_Model_OutputsMapper::i()->fetchByConds($this->helpers()->devices()->getDeviceType());
		
		if ( count($outputs) ) {
			X_Debug::i("Valid outputs for device {$this->helpers()->devices()->getDeviceType()}: ".count($outputs));
		} else {
			X_Debug::e("No valid profiles for device {$this->helpers()->devices()->getDeviceType()}: i need at least an output");
		}
		
		// the best is the first
		foreach ($outputs as $output) {
			/* @var $profile Application_Model_Output */
			X_Debug::i("Valid output: [{$output->getId()}] {$output->getLabel()} ({$output->getCondDevices()})");
			$return[] = array(
				'label'	=>	$output->getLabel(),
				'link'	=>	X_Env::completeUrl($urlHelper->url(array(
						'action'	=>	'mode',
						'pid'		=>	null,
						$this->getId() => $output->getId() // set this plugin selection as profileId
					), 'default', false)
				),
				'highlight' => ($currentOutput == $output->getId()),
				__CLASS__.':output' => $output->getId()
			);
		}
	
	
		
		// general profiles are in the bottom of array
		return $return;
	}
	
	/**
	 * Return the link -go-to-stream-
	 * @param string $provider id of the plugin that should handle request
	 * @param string $location to stream
	 * @param Zend_Controller_Action $controller the controller who handle the request
	 * @return array 
	 */
	public function getStreamItems($provider, $location, Zend_Controller_Action $controller) {
		
		X_Debug::i('Plugin triggered');
		
		$outputId = $controller->getRequest()->getParam($this->getId(), false);
		$urlHelper = $controller->getHelper('url');
		
		$output = new Application_Model_Output();
		// i store the default link, so if i don't find the proper output
		// i will have a valid link for -go-to-stream- button
		//$output->setLink($this->config('default.link', "http://{$_SERVER['SERVER_ADDR']}:8081"));
		
		if ( $outputId !== false ) {
			Application_Model_OutputsMapper::i()->find($outputId, $output);
		} else {
			// if no params is provided, i will try to
			// get the best output for this condition
			$output = $this->getBest($this->helpers()->devices()->getDeviceType());
		}
		
		
		$outputLink = $output->getLink();
		$outputLink = str_replace(
			array(
				'{%SERVER_IP%}',
				'{%SERVER_NAME%}'
			),array(
				$_SERVER['SERVER_ADDR'],
				$_SERVER['HTTP_HOST']
			), $outputLink
		);
		
		return array(
			array(
				'label'	=>	X_Env::_('p_outputs_gotostream'),
				'link'	=>	$outputLink,
				'type'	=>	X_Plx_Item::TYPE_VIDEO
			)
		);
		
	}
	
	/**
	 * Add output arg in vlc args
	 * 
	 * @param X_Vlc $vlc vlc wrapper object
	 * @param string $provider id of the plugin that should handle request
	 * @param string $location to stream
	 * @param Zend_Controller_Action $controller the controller who handle the request
	 */
	public function postRegisterVlcArgs(X_Vlc $vlc, $provider, $location, Zend_Controller_Action $controller) {
		
		X_Debug::i('Plugin triggered');
		
		$outputId = $controller->getRequest()->getParam($this->getId(), false);
		
		if ( $outputId !== false ) {
			$output = new Application_Model_Output();
			Application_Model_OutputsMapper::i()->find($outputId, $output);
		} else {
			// if no params is provided, i will try to
			// get the best output for this condition
			$output = $this->getBest($this->helpers()->devices()->getDeviceType());
		}
		
		if ( $output->getArg() !== null ) {

			$vlc->registerArg('output', $output->getArg());			
			
			if ( $this->config('store.session', false) ) {
				// store the link in session for future use
			}
			
		} else {
			X_Debug::e("No output arg for vlc");
		}
	}
	
	public function preSpawnVlc(X_Vlc $vlc, $provider, $location, Zend_Controller_Action $controller) {
		X_Debug::i(var_export($vlc->getArgs(), true));
	}
	
	/**
	 * Find the best output type for the current device
	 * @param int $device
	 */
	private function getBest($device) {
		
		$output = new Application_Model_Output();
		Application_Model_OutputsMapper::i()->findBest($device, $output);
		return $output;
		
	}
	
}