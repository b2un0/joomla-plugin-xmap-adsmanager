<?php

/**
 * @author     Branko Wilhelm <branko.wilhelm@gmail.com>
 * @link       http://www.z-index.net
 * @copyright  (c) 2013 Branko Wilhelm
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
 
defined('_JEXEC') or die;

final class xmap_com_adsmanager {

	private static $views = array('front', 'list');
	
	private static $enabled = false;
	
	public function __construct() {
		self::$enabled = JComponentHelper::isEnabled('com_adsmanager');
	}
	
	public static function getTree(XmapDisplayer &$xmap, stdClass &$parent, array &$params) {
		$uri = new JUri($parent->link);
		
		if(!self::$enabled || !in_array($uri->getVar('view'), self::$views)) {
			return;
		}
		
		$params['include_entries'] = JArrayHelper::getValue($params, 'include_entries', 1);
		$params['include_entries'] = ($params['include_entries'] == 1 || ($params['include_entries'] == 2 && $xmap->view == 'xml') || ($params['include_entries'] == 3 && $xmap->view == 'html'));
		
		$params['include_expired_entries'] = JArrayHelper::getValue($params, 'include_expired_entries', 0);
		$params['include_expired_entries'] = ($params['include_expired_entries'] == 1 || ($params['include_expired_entries'] == 2 && $xmap->view == 'xml') || ($params['include_expired_entries'] == 3 && $xmap->view == 'html'));
		
		$params['category_priority'] = JArrayHelper::getValue($params, 'category_priority', $parent->priority);
		$params['category_changefreq'] = JArrayHelper::getValue($params, 'category_changefreq', $parent->changefreq);
		
		if($params['category_priority'] == -1) {
			$params['category_priority'] = $parent->priority;
		}
		
		if($params['category_changefreq'] == -1) {
			$params['category_changefreq'] = $parent->changefreq;
		}
		
		$params['entry_priority'] = JArrayHelper::getValue($params, 'entry_priority', $parent->priority);
		$params['entry_changefreq'] = JArrayHelper::getValue($params, 'entry_changefreq', $parent->changefreq);
		
		if($params['entry_priority'] == -1) {
			$params['entry_priority'] = $parent->priority;
		}
		
		if($params['entry_changefreq'] == -1) {
			$params['entry_changefreq'] = $parent->changefreq;
		}
		
		switch($uri->getVar('view')) {
			case 'front':
				self::getCategoryTree($xmap, $parent, $params, 0);
			break;
			
			case 'list':
				self::getEntries($xmap, $parent, $params, $uri->getVar('catid'));
			break;
		}
	}

	private static function getCategoryTree(XmapDisplayer &$xmap, stdClass &$parent, array &$params, $parent_id) {
		$db = JFactory::getDbo();
		
		$query = $db->getQuery(true)
				->select(array('c.id', 'c.name'))
				->from('#__adsmanager_categories AS c')
				->where('c.parent = ' . $db->quote($parent_id))
				->where('c.published = 1')
				->order('c.ordering');
		
		$db->setQuery($query);
		$rows = $db->loadObjectList();

		$xmap->changeLevel(1);
		
		foreach($rows as $row){
			$node = new stdclass;
			$node->id = $parent->id;
			$node->name = $row->name;
			$node->uid = $parent->uid . '_cid_' . $row->id;
			$node->browserNav = $parent->browserNav;
			$node->priority = $params['category_priority'];
			$node->changefreq = $params['category_changefreq'];
			$node->link = 'index.php?option=com_adsmanager&view=list&catid=' . $row->id . '&Itemid=' . $parent->id;
			
			if ($xmap->printNode($node) !== false) {
				self::getCategoryTree($xmap, $parent, $params, $row->id);
				if ($params['include_entries']) {
					self::getEntries($xmap, $parent, $params, $row->id);
				}
			}		
		}
		
		$xmap->changeLevel(-1);
	}

	private static function getEntries(XmapDisplayer &$xmap, stdClass &$parent, array &$params, $catid) {
		$db = JFactory::getDbo();
		$now = JFactory::getDate('now', 'UTC')->toSql();
		
		$query = $db->getQuery(true)
				->select(array('a.id', 'a.ad_headline'))
				->from('#__adsmanager_adcat AS c')
				->join('INNER', '#__adsmanager_ads AS a ON (c.adid = a.id)')
				->where('c.catid = ' . $db->Quote($catid))
				->where('a.published = 1')
				->order('a.id');
				
		if(!$params['include_expired_entries']) {
			$query->where('a.date_created <= ' . $db->quote($now));
			$query->where('a.expiration_date >= ' .$db->quote(date('Y-m-d')));
		}
		
		$db->setQuery($query);
		$rows = $db->loadObjectList();
		
		if(empty($rows)) {
			return;
		}
		
		$xmap->changeLevel(1);
		
		foreach($rows as $row) {
			$node = new stdclass;
			$node->id = $parent->id;
			$node->name = $row->ad_headline;
			$node->uid = $parent->uid . '_' . $row->id;
			$node->browserNav = $parent->browserNav;
			$node->priority = $params['entry_priority'];
			$node->changefreq = $params['entry_changefreq'];
			$node->link = 'index.php?option=com_adsmanager&view=details&id=' . $row->id . '&catid=' . $catid . '&Itemid=' . $parent->id;
			
			$xmap->printNode($node);
		}
		
		$xmap->changeLevel(-1);
	}
}