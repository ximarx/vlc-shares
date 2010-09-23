<?php

require_once ('X/VlcShares/Plugins/Abstract.php');
require_once ('X/Plx.php');
require_once ('X/Plx/Item.php');

/**
 * 
 * @author ximarx
 *
 */
class X_VlcShares_Plugins_WiimcPlxRenderer extends X_VlcShares_Plugins_Abstract {

	function __construct() {
		$this->setPriority('gen_afterPageBuild');
	}
	
	public function gen_afterPageBuild(&$items, Zend_Controller_Action $controller) {
		if ( !((bool) $this->config('forced.enabled', false)) && !$this->helpers()->devices()->isWiimc() ) return;
		
		X_Debug::i("Plugin triggered");

		$request = $controller->getRequest();
		
		$plx = new X_Plx(
			X_Env::_('p_wiimcplxrenderer_plxtitle_'.$request->getControllerName().'_'.$request->getActionName()),
			X_Env::_('p_wiimcplxrenderer_plxdescription_'.$request->getControllerName().'_'.$request->getActionName())
		);
		
		foreach ( $items as $i => $item ) {
			$plxItemName = (@$item['highlight'] ? '-) ' : '' ). $item['label'];
			$plxItemType = (array_key_exists('type', $item) ? $item['type'] : X_Plx_Item::TYPE_PLAYLIST );
			$plx->addItem(new X_Plx_Item($plxItemName, $item['link'], $plxItemType));
		}
		
		$this->_render($plx, $controller);
	}
	
	/**
	 * Send to output plx playlist
	 * @param X_Plx $plx
	 */
	private function _render(X_Plx $plx, Zend_Controller_Action $controller) {
		$this->_disableRendering($controller);
		// if isn't wiimc, add a conversion filter
		if ( !$this->helpers()->devices()->isWiimc() && $this->config('forced.fancy', true)) {
			$showRaw = $this->config('forced.showRaw', false);
			$plxItems = $plx->getItems();
			$body = include(dirname(__FILE__).'/WiimcPlxRenderer.fancy.phtml');
		} else {
			$controller->getResponse()->setHeader('Content-type', 'text/plain', true);
			$body = (string) $plx;
		}
		$controller->getResponse()->setBody($body);
	}
	
	/**
	 * Disable layout and viewRenderer
	 * @param Zend_Controller_Action $controller
	 */
	private function _disableRendering(Zend_Controller_Action $controller) {
		try {
			$controller->getHelper('viewRenderer')->setNoRender(true);
			// disableLayout must be called at the end
			// i don't know if layout is enabled
			// and maybe an exception is raised if
			// i call e disableLayout without layout active
			$controller->getHelper('layout')->disableLayout();
		} catch (Exception $e) {
			X_Debug::w("Unable to disable viewRenderer or Layout: {$e->getMessage()}");
		}
	}
	
}


