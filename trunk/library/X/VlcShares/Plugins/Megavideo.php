<?php

require_once 'X/VlcShares/Plugins/Abstract.php';
require_once 'Megavideo.php'; // megavideo wrapper
require_once 'X/VlcShares/Plugins/BackuppableInterface.php';


/**
 * Megavideo plugin
 * @author ximarx
 *
 */
class X_VlcShares_Plugins_Megavideo extends X_VlcShares_Plugins_Abstract implements X_VlcShares_Plugins_ResolverInterface, X_VlcShares_Plugins_BackuppableInterface {
	
	public function __construct() {
		$this->setPriority('getCollectionsItems')
			->setPriority('preRegisterVlcArgs')
			->setPriority('getShareItems')
			->setPriority('preGetModeItems')
			->setPriority('getIndexActionLinks')
			->setPriority('getIndexStatistics')
			->setPriority('getIndexManageLinks');
	}
	
	/**
	 * Add the main link for megavideo library
	 * @param Zend_Controller_Action $controller
	 * @return X_Page_ItemList_PItem
	 */
	public function getCollectionsItems(Zend_Controller_Action $controller) {
		
		X_Debug::i("Plugin triggered");

		$link = new X_Page_Item_PItem($this->getId(), X_Env::_('p_megavideo_collectionindex'));
		$link->setIcon('/images/megavideo/logo.png')
			->setDescription(X_Env::_('p_megavideo_collectionindex_desc'))
			->setType(X_Page_Item_PItem::TYPE_CONTAINER)
			->setLink(
				array(
					'controller' => 'browse',
					'action' => 'share',
					'p' => $this->getId(),
				), 'default', true
			);
		return new X_Page_ItemList_PItem(array($link));
		
	}
	
	/**
	 * Get category/video list
	 * @param unknown_type $provider
	 * @param unknown_type $location
	 * @param Zend_Controller_Action $controller
	 * @return X_Page_ItemList_PItem
	 */
	public function getShareItems($provider, $location, Zend_Controller_Action $controller) {
		// this plugin add items only if it is the provider
		if ( $provider != $this->getId() ) return;
		
		X_Debug::i("Plugin triggered");
		
		$urlHelper = $controller->getHelper('url');
		
		$items = new X_Page_ItemList_PItem();
		
		if ( $location != '' ) {
			
			//list($shareType, $linkId) = explode(':', $location, 2);
			// $location is the categoryName

			$videos = Application_Model_MegavideoMapper::i()->fetchByCategory($location);
			
			foreach ($videos as $video) {
				/* @var $video Application_Model_Megavideo */
				$item = new X_Page_Item_PItem($this->getId().'-'.$video->getId(), $video->getLabel());
				$item->setIcon('/images/icons/file_32.png')
					->setType(X_Page_Item_PItem::TYPE_ELEMENT)
					->setCustom(__CLASS__.':location', $video->getId())
					->setLink(array(
						'action' => 'mode',
						'l'	=>	base64_encode($video->getId())
					), 'default', false);
				$items->append($item);
			}
			
		} else {
			// if location is not specified,
			// show collections
			$categories = Application_Model_MegavideoMapper::i()->fetchCategories();
			foreach ( $categories as $share ) {
				/* @var $share Application_Model_FilesystemShare */
				$item = new X_Page_Item_PItem($this->getId().'-'.$share['category'], "{$share['category']} ({$share['links']})");
				$item->setIcon('/images/icons/folder_32.png')
					->setType(X_Page_Item_PItem::TYPE_CONTAINER)
					->setCustom(__CLASS__.':location', $share['category'])
					->setLink(array(
						'l'	=>	base64_encode($share['category'])
					), 'default', false);
				$items->append($item);
			}
		}
		
		return $items;
	}
	
	/**
	 * This hook can be used to add low priority args in vlc stack
	 * 
	 * @param X_Vlc $vlc vlc wrapper object
	 * @param string $provider id of the plugin that should handle request
	 * @param string $location to stream
	 * @param Zend_Controller_Action $controller the controller who handle the request
	 */
	public function preRegisterVlcArgs(X_Vlc $vlc, $provider, $location, Zend_Controller_Action $controller) {
	
		// this plugin inject params only if this is the provider
		if ( $provider != $this->getId() ) return;

		// i need to register source as first, because subtitles plugin use source
		// for create subfile
		
		X_Debug::i('Plugin triggered');
		
		$location = $this->resolveLocation($location);
		
		if ( $location !== null ) {
			// TODO adapt to newer api when ready
			$vlc->registerArg('source', "\"$location\"");			
		} else {
			X_Debug::e("No source o_O");
		}
	
	}
	
	/**
	 *	Add button -watch megavideo stream directly-
	 * 
	 * @param string $provider
	 * @param string $location
	 * @param Zend_Controller_Action $controller
	 */
	public function preGetModeItems($provider, $location, Zend_Controller_Action $controller) {

		if ( $provider != $this->getId()) return;
		
		X_Debug::i("Plugin triggered");
		
		$video = new Application_Model_Megavideo();
		Application_Model_MegavideoMapper::i()->find($location, $video);
		
		if ( $video->getId() != null ) {
			$megavideo = new Megavideo($video->getIdVideo());
			
			$link = new X_Page_Item_PItem('core-directwatch', X_Env::_('p_megavideo_watchdirectly'));
			$link->setIcon('/images/icons/play.png')
				->setType(X_Page_Item_PItem::TYPE_PLAYABLE)
				->setLink($megavideo->get('URL'));
			return new X_Page_ItemList_PItem(array($link));
		}
		
	}
	
	
	/**
	 * @see X_VlcShares_Plugins_ResolverInterface::resolveLocation
	 * @param string $location
	 * @return string real address of a resource
	 */
	function resolveLocation($location = null) {

		// prevent no-location-given error
		if ( $location === null ) return false;
		if ( (int) $location < 0 ) return false; // megavideo_model id > 0
		
		$video = new Application_Model_Megavideo();
		Application_Model_MegavideoMapper::i()->find((int) $location, $video);
		
		// TODO prevent ../
		if ( $video->getId() == null ) return false;
		
		$megavideo = new Megavideo($video->getIdVideo());
		
		return $megavideo->get('URL');
	}
	
	/**
	 * @see X_VlcShares_Plugins_ResolverInterface::getParentLocation
	 * @param $location
	 */
	function getParentLocation($location = null) {
		if ($location == null || $location == '') return false;
		
		if ( is_numeric($location) && ((int) $location > 0 ) ) {
			// should be a video id.
			$model = new Application_Model_Megavideo();
			Application_Model_MegavideoMapper::i()->find((int) $location, $model);
			if ( $model->getId() !== null ) {
				return $model->getCategory();
			} else {
				return null;
			}
		} else {
			// should be a category name
			return null;
		}
	}
	
	/**
	 * Add the link Add megavideo link to actionLinks
	 * @param Zend_Controller_Action $this
	 * @return array The format of the array should be:
	 * 		array(
	 * 			array(
	 * 				'label' => ITEM LABEL,
	 * 				'link'	=> HREF,
	 * 				'highlight'	=> true|false,
	 * 				'icon'	=> ICON_HREF
	 * 			), ...
	 * 		)
	 */
	public function getIndexActionLinks(Zend_Controller_Action $controller) {

		$link = new X_Page_Item_ActionLink($this->getId(), X_Env::_('p_megavideo_actionaddvideo'));
		$link->setIcon('/images/plus.png')
			->setLink(array(
					'controller'	=>	'megavideo',
					'action'		=>	'add',
				), 'default', true);
		return new X_Page_ItemList_ActionLink(array($link));
	}
	
	/**
	 * Add the link for -manage-megavideo-
	 * @param Zend_Controller_Action $this
	 * @return X_Page_ItemList_ManageLink
	 */
	public function getIndexManageLinks(Zend_Controller_Action $controller) {

		$link = new X_Page_Item_ManageLink($this->getId(), X_Env::_('p_megavideo_mlink'));
		$link->setTitle(X_Env::_('p_megavideo_managetitle'))
			->setIcon('/images/megavideo/logo.png')
			->setLink(array(
					'controller'	=>	'megavideo',
					'action'		=>	'index',
			), 'default', true);
		return new X_Page_ItemList_ManageLink(array($link));
		
	}
	
	/**
	 * Retrieve statistic from plugins
	 * @param Zend_Controller_Action $this
	 * @return X_Page_ItemList_Statistic
	 */
	public function getIndexStatistics(Zend_Controller_Action $controller) {
		
		$categories = count(Application_Model_MegavideoMapper::i()->fetchCategories()); // FIXME create count functions
		$videos = count(Application_Model_MegavideoMapper::i()->fetchAll()); // FIXME create count functions
		
		$stat = new X_Page_Item_Statistic($this->getId(), X_Env::_('p_megavideo_statstitle'));
		$stat->setTitle(X_Env::_('p_megavideo_statstitle'))
			->appendStat(X_Env::_('p_megavideo_statcategories').": $categories")
			->appendStat(X_Env::_('p_megavideo_statvideos').": $videos");

		return new X_Page_ItemList_Statistic(array($stat));
		
	}
	
	
	/**
	 * Backup all videos in db
	 * This is not a trigger of plugin API. It's called by Backupper plugin
	 */
	function getBackupItems() {
		
		$return = array();
		$videos = Application_Model_MegavideoMapper::i()->fetchAll();
		
		foreach ($videos as $model) {
			/* @var $model Application_Model_Megavideo */
			$return['videos']['video-'.$model->getId()] = array(
				'id'			=> $model->getId(), 
	            'idVideo'   	=> $model->getIdVideo(),
	            'description'	=> $model->getDescription(),
	            'category'		=> $model->getCategory(),
	        	'label'			=> $model->getLabel(),
			);
		}
		
		return $return;
	}
	
	/**
	 * Restore backupped videos 
	 * This is not a trigger of plugin API. It's called by Backupper plugin
	 */
	function restoreItems($items) {

		//return parent::restoreItems($items);
		
		$models = Application_Model_MegavideoMapper::i()->fetchAll();
		// cleaning up all shares
		foreach ($models as $model) {
			Application_Model_MegavideoMapper::i()->delete($model->getId());
		}
	
		foreach (@$items['videos'] as $modelInfo) {
			$model = new Application_Model_Megavideo();
			$model->setIdVideo(@$modelInfo['idVideo']) 
				->setDescription(@$modelInfo['description'])
				->setCategory(@$modelInfo['category'])
				->setLabel(@$modelInfo['label'])
				;
			// i don't set id, or db adapter will try to update old data that i cleaned
			Application_Model_MegavideoMapper::i()->directSave($model);
		}
		
		return X_Env::_('p_megavideo_backupper_restoreditems'). ": " .count($items['videos']);
		
		
	}
	
	
}
