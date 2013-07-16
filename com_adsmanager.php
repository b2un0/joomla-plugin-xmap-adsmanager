<?php

/**
 * @author     Branko Wilhelm <branko.wilhelm@gmail.com>
 * @link       http://www.z-index.net
 * @copyright  (c) 2013 Branko Wilhelm
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
 
defined('_JEXEC') or die;

require_once JPATH_SITE . '/components/com_adsmanager/lib/route.php';

final class xmap_com_adsmanager {				

	public function getTree(&$xmap, &$parent, &$params) {
		$include_entries = JArrayHelper::getValue($params, 'include_entries', 1);
		$include_entries = ($include_entries == 1 || ($include_entries == 2 && $xmap->view == 'xml') || ($include_entries == 3 && $xmap->view == 'html'));
		
		$params['include_entries'] = $include_entries;
		
		$priority = JArrayHelper::getValue($params, 'category_priority', $parent->priority);
		$changefreq = JArrayHelper::getValue($params, 'category_changefreq', $parent->changefreq);
		
		if($priority == -1) {
			$priority = $parent->priority;
		}
		
		if($changefreq == -1) {
			$changefreq = $parent->changefreq;
		}
		
		$params['category_priority'] = $priority;
		$params['category_changefreq'] = $changefreq;
		
		$priority = JArrayHelper::getValue($params, 'entry_priority', $parent->priority);
		$changefreq = JArrayHelper::getValue($params, 'entry_changefreq', $parent->changefreq);
		
		if($priority == -1) {
			$priority = $parent->priority;
		}
		
		if($changefreq == -1) {
			$changefreq = $parent->changefreq;
		}
		
		$params['entry_priority'] = $priority;
		$params['entry_changefreq'] = $changefreq;
		
		self::getCategoryTree($xmap, $parent, $params, 0);
		return true;
	}

	private static function getCategoryTree(&$xmap, &$parent, &$params, $parent_id) {
		$db = JFactory::getDbo();
		
		$query = $db->getQuery(true)
				->select(array('id', 'name', 'parent'))
				->from('#__adsmanager_categories')
				->where('parent = ' . $db->quote($parent_id))
				->where('published = 1')
				->order('ordering');
		
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
			$node->link = TRoute::_('index.php?option=com_adsmanager&view=list&catid='.$row->id, false);
			$node->pid = $row->parent;	
			
			if ($xmap->printNode($node) !== false) {
				self::getCategoryTree($xmap, $parent, $params, $row->id);
				if ($params['include_entries']) {
					self::getEntries($xmap, $parent, $params, $row->id);
				}
			}		
		}
		
		$xmap->changeLevel(-1);
	}

	private static function getEntries(&$xmap, &$parent, &$params, $catid) {
		
		$db = JFactory::getDbo();
		
		$query = $db->getQuery(true)
		->select(array('a.id', 'a.ad_headline'))
		->from('#__adsmanager_adcat AS c')
		->join('INNER', '#__adsmanager_ads AS a ON (c.adid = a.id)')
		->where('c.catid = ' . $db->Quote($catid))
		->where('a.published = 1')
		->order('a.id');
		
		$db->setQuery($query);
		$rows = $db->loadObjectList();
		
		if(empty($rows)) {
			return;
		}
		
		$xmap->changeLevel(1);
		
		foreach($rows as $row){
			$node = new stdclass;
			$node->id = $parent->id;
			$node->name = $row->ad_headline;
			$node->uid = $parent->uid . '_' . $row->id;
			$node->browserNav = $parent->browserNav;
			$node->priority = $params['entry_priority'];
			$node->changefreq = $params['entry_changefreq'];
			$node->link = TRoute::_('index.php?option=com_adsmanager&view=details&id='.$row->id.'&catid='.$catid, false);

			$xmap->printNode($node);
		}
		
		$xmap->changeLevel(-1);
	}
}