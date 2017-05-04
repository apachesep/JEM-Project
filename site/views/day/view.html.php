<?php
/**
 * @version 2.2.1
 * @package JEM
 * @copyright (C) 2013-2017 joomlaeventmanager.net
 * @copyright (C) 2005-2009 Christoph Lukes
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */
defined('_JEXEC') or die;

require JPATH_COMPONENT_SITE.'/classes/view.class.php';

/**
 * Day-View
 */
class JemViewDay extends JEMView
{
	function __construct($config = array())
	{
		parent::__construct($config);

		// additional path for common templates + corresponding override path
		$this->addCommonTemplatePath();
	}

	/**
	 * Creates the Day View
	 */
	function display($tpl = null)
	{
		// Initialize variables
		$app 			= JFactory::getApplication();
		$jemsettings 	= JemHelper::config();
		$settings 		= JemHelper::globalattribs();
		$menu 			= $app->getMenu();
		$menuitem 		= $menu->getActive();
		$document 		= JFactory::getDocument();
		$params 		= $app->getParams();
		$uri 			= JFactory::getURI();
		$jinput 		= $app->input;
		$task 			= $jinput->getCmd('task', '');
		$print			= $jinput->getBool('print', false);
		$pathway 		= $app->getPathWay();
		$user			= JemFactory::getUser();
		$itemid 		= $jinput->getInt('id', 0) . ':' . $jinput->getInt('Itemid', 0);

		// Decide which parameters should take priority
		$useMenuItemParams = ($menuitem && $menuitem->query['option'] == 'com_jem'
		                                && $menuitem->query['view'] == 'day'
		                                && !isset($menuitem->query['id']));

		// Retrieving data
		$requestVenueId = $jinput->getInt('locid', 0);
		$requestCategoryId = $jinput->getInt('catid', 0);
		$requestDate = $jinput->getInt('id', 0);

		// Load css
		JemHelper::loadCss('jem');
		JemHelper::loadCustomCss();
		JemHelper::loadCustomTag();

		if ($print) {
			JemHelper::loadCss('print');
			$document->setMetaData('robots', 'noindex, nofollow');
		}

		// get variables
		$filter_order     = $app->getUserStateFromRequest('com_jem.day.'.$itemid.'.filter_order', 'filter_order', 'a.dates', 'cmd');
		$filter_order_Dir = $app->getUserStateFromRequest('com_jem.day.'.$itemid.'.filter_order_Dir', 'filter_order_Dir', '', 'word');
		$filter_type      = $app->getUserStateFromRequest('com_jem.day.'.$itemid.'.filter_type', 'filter_type', 0, 'int');
		$search           = $app->getUserStateFromRequest('com_jem.day.'.$itemid.'.filter_search', 'filter_search', '', 'string');

		// table ordering
		$lists['order_Dir'] = $filter_order_Dir;
		$lists['order']     = $filter_order;

		// Get data from model
		$rows = $this->get('Items');
		$day  = $this->get('Day');

		$daydate     = JemOutput::formatdate($day);
		$showdaydate = true; // show by default

		// Are events available?
		$noevents = (!$rows) ? 1 : 0;

		// Show page heading specified on menu item or TODAY as heading - idea taken from com_content.
		if ($useMenuItemParams) {
			$pagetitle   = $params->get('page_title', $menuitem->title);
			$params->def('page_heading', $pagetitle);
			$pathway->setItemName(1, $menuitem->title);
		} else {
			// TODO: If we can integrate $daydate into page_heading we should set $showdaydate to false.
			$pagetitle   = JText::_('COM_JEM_DEFAULT_PAGE_TITLE_DAY');
			$params->set('page_heading', $pagetitle);
			$pathway->addItem($pagetitle);
		}
		$pageclass_sfx = $params->get('pageclass_sfx');

		// Add site name to title if param is set
		if ($app->getCfg('sitename_pagetitles', 0) == 1) {
			$pagetitle = JText::sprintf('JPAGETITLE', $app->getCfg('sitename'), $pagetitle);
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 2) {
			$pagetitle = JText::sprintf('JPAGETITLE', $pagetitle, $app->getCfg('sitename'));
		}

		// Set Page title
		$document->setTitle($pagetitle);

		if ($requestVenueId){
			$print_link = JRoute::_('index.php?option=com_jem&view=day&tmpl=component&print=1&locid='.$requestVenueId.'&id='.$requestDate);
		}
		elseif ($requestCategoryId){
			$print_link = JRoute::_('index.php?option=com_jem&view=day&tmpl=component&print=1&catid='.$requestCategoryId.'&id='.$requestDate);
		}
		else /*(!$requestCategoryId && !$requestVenueId)*/ {
			$print_link = JRoute::_('index.php?option=com_jem&view=day&tmpl=component&print=1&id='.$requestDate);
		}

		// Check if the user has permission to add things
		$permissions = new stdClass();
		$permissions->canAddEvent = $user->can('add', 'event');
		$permissions->canAddVenue = $user->can('add', 'venue');

		// add alternate feed link (w/o specific date)
		$link    = 'index.php?option=com_jem&view=day&format=feed';
		$attribs = array('type' => 'application/rss+xml', 'title' => 'RSS 2.0');
		$document->addHeadLink(JRoute::_($link.'&type=rss'), 'alternate', 'rel', $attribs);
		$attribs = array('type' => 'application/atom+xml', 'title' => 'Atom 1.0');
		$document->addHeadLink(JRoute::_($link.'&type=atom'), 'alternate', 'rel', $attribs);

		// search filter
		$filters = array();

		if ($jemsettings->showtitle == 1) {
			$filters[] = JHtml::_('select.option', '1', JText::_('COM_JEM_TITLE'));
		}
		if ($jemsettings->showlocate == 1 && !($requestVenueId)) {
			$filters[] = JHtml::_('select.option', '2', JText::_('COM_JEM_VENUE'));
		}
		if ($jemsettings->showcity == 1 && !($requestVenueId)) {
			$filters[] = JHtml::_('select.option', '3', JText::_('COM_JEM_CITY'));
		}
		if ($jemsettings->showcat == 1 && !($requestCategoryId)) {
			$filters[] = JHtml::_('select.option', '4', JText::_('COM_JEM_CATEGORY'));
		}
		if ($jemsettings->showstate == 1 && !($requestVenueId)) {
			$filters[] = JHtml::_('select.option', '5', JText::_('COM_JEM_STATE'));
		}
		$lists['filter'] = JHtml::_('select.genericlist', $filters, 'filter_type', array('size'=>'1','class'=>'inputbox'), 'value', 'text', $filter_type);
		$lists['search'] = $search;

		// Create the pagination object
		$pagination = $this->get('Pagination');

		$this->lists			= $lists;
		$this->rows				= $rows;
		$this->noevents			= $noevents;
		$this->print_link		= $print_link;
		$this->params			= $params;
		$this->dellink			= $permissions->canAddEvent; // deprecated
		$this->pagination		= $pagination;
		$this->action			= $uri->toString();
		$this->task				= $task;
		$this->jemsettings		= $jemsettings;
		$this->settings			= $settings;
		$this->permissions		= $permissions;
		$this->daydate			= $daydate;
		$this->showdaydate		= $showdaydate; // if true daydate will be shown as h2 sub heading
		$this->pageclass_sfx	= htmlspecialchars($pageclass_sfx);

		// Doesn't really help - each view has less or more specific needs.
		//$this->prepareDocument();

		parent::display($tpl);
	}
}
?>
