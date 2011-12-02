<?php

// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.modellist');

/**
 * Method for suporting export html
 *
 * @package		Joomla.Site
 * @subpackage	com_staticcontent
 * @since		1.7
 */
class StaticContentModelExport extends JModel {

    /**
     * Array of menu items
     *
     * @var    Array JObject
     * @since  11.1
     */
    protected $items = null;

    /**
     * Array of all pages to export
     *
     * @var    Array JObject
     * @since  11.1
     */
    protected $_links = Array(
        'menu' => array(),
        'print' => array(),
        'pages' => array()
    );

    /**
     * JCache object
     *
     * @var    JCache 
     * @since  11.1
     */
    protected $_cache = null;

    /**
     * Constructor.
     *
     * @param	array	An optional associative array of configuration settings.
     * @see		JController
     * @since	1.6
     */
    public function __construct($config = array()) {
        parent::__construct($config);

        //initialize vars
        $this->option = JRequest::getCmd('option');
        $this->_comParams = JComponentHelper::getParams($this->option);

        //cache callbacks
        $this->_cache = JCache::getInstance('callback', array(
                    'defaultgroup' => $this->option,
                    'lifetime' => $this->_comParams->get('callback_lifetime', 86400) //1 day duration
                ));
        $this->_cache->setCaching(true);

        //cache request
        $this->_cachePage = JCache::getInstance('output', array(
                    'defaultgroup' => $this->option,
                    'lifetime' => $this->_comParams->get('pages_lifetime', 86400) //1 day duration
                ));
        $this->_cachePage->setCaching(true);

        //load helpers
        $this->loadHelper('document');
        $this->loadHelper('menu');
        $this->loadHelper('url');
    }

    /**
     * Set menu items
     * 
     * @param array $items
     */
    public function setItems(array $items) {
        $this->items = $items;
    }

    /**
     * check if links is alredy in registry
     * 
     * @param string $link
     * @return boolean true if exists
     */
    public function existsLink($link) {
        $menuLinks = JArrayHelper::getColumn($this->tmpLinks, 'relative');
        return (array_search($link, $menuLinks) !== false) ? true : false;
    }

    /**
     * Default method to proccess html export
     * 
     * @return Boolean TRUE if success
     */
    public function createPages() {
        $result = false;

        if (empty($this->items)) {
            return false;
        }

        //$this->_links['menu'] = $this->_requestPageItems($this->items);
        $cache_id = md5(get_class($this) . '_requestPageItems_' . count($this->items));
        $this->_links['menu'] = $this->_cache->get(array($this, '_requestPageItems'), array($this->items), $cache_id);

        //items not necessary anymore
        unset($this->items);

        //register menu links in class
        StaticContentHelperMenu::setLinks(JArrayHelper::getColumn($this->_links['menu'], 'full'));

        //$this->_links['internal'] = $this->_discoverInteralLinks();
        $cache_id = md5(get_class($this) . '_discoverInteralLinks' . count($this->_links['menu']));
        $arrData = $this->_cache->get(array($this, '_discoverInteralLinks'), array(), $cache_id);

        $this->_links = array_merge($this->_links, $arrData);

        $return = $this->_writePages();
        
        JApplication::getInstance('site')->set('force_sef',false);

        echo ($return) ? JText::_('COM_STATICCONTENT_MSG_SUCCESS_CREATED_SITE') : JText::_('COM_STATICCONTENT_MSG_FAILURE_CREATED_SITE');
    }

    /**
     * Request menu items and crete cache pages
     * 
     */
    public function _requestPageItems($items) {
        $this->tmpLinks = array();

        foreach ($items as $menuItem) {
            //build menu link
            StaticContentHelperMenu::buildLink($menuItem);

            //check if guest user has access
            if (!StaticContentHelperMenu::guestCanAccess($menuItem) || !StaticContentHelperMenu::validType($menuItem)) {
                continue;
            }

            $relative_link = StaticContentHelperUrl::getRelativeLink($menuItem->flink);
            $full_link = StaticContentHelperUrl::getFullLink($menuItem->flink);
            $pageName = StaticContentHelperUrl::createPageName($full_link);

            if (!$this->existsLink($relative_link)) {
                $content = $this->_requestPage($full_link);

                $link = new JObject();
                $link->set('file', $pageName);
                $link->set('full', $full_link);
                $link->set('relative', $relative_link);
                $link->set('content', $content);

                //add a link
                array_push($this->tmpLinks, $link);
            }
        }

        return $this->tmpLinks;
    }

    /**
     * Discover internal links
     * 
     */
    public function _discoverInteralLinks() {
        $tmpLinks = array(
            'print' => array(),
            'pages' => array()
        );

        foreach ($this->_links['menu'] as $link) {
            $dom = new DomDocument();
            $dom->loadHTML($link->get('content'));

            $aElements = $dom->getElementsByTagName('a');

            foreach ($aElements as $aElement) {
                $href = $aElement->getAttribute('href');

                $full_link = StaticContentHelperUrl::getFullLink($href);

                if (!StaticContentHelperMenu::isSatisfatoryLink($full_link)) {
                    continue;
                }

                $relative_link = StaticContentHelperUrl::getRelativeLink($href);

                $pageName = StaticContentHelperUrl::createPageName($full_link);
                if (StaticContentHelperUrl::isPrintLink($full_link)) {
                    $tmpLinks['print'][$href] = $pageName;
                }

                if (!$this->existsLink($full_link)) {
                    $cache_id = md5($full_link);
                    $content = $this->_cachePage->get($cache_id);
                    if (empty($content)) {
                        $content = file_get_contents($full_link);
                        $this->_cachePage->store($content, $cache_id);
                    }

                    $link = new JObject();
                    $link->set('file', $pageName);
                    $link->set('full', $full_link);
                    $link->set('relative', $relative_link);
                    $link->set('content', $content);

                    array_push($tmpLinks['pages'], $link);
                }
            }

            unset($dom);
        }

        return $tmpLinks;
    }

    /**
     * Request a page and return response
     * 
     * @param string $url
     * @return string page source
     */
    private function _requestPage($url) {
        $cache_id = md5('page_' . $url);
        $content = $this->_cachePage->get($cache_id);
        if (empty($content)) {
            $content = file_get_contents($url);
            $this->_cachePage->store($content, $cache_id);
        }

        return $content;
    }

    /**
     * Method to write pages
     * 
     * @return booelan true if success
     */
    private function _writePages() {
        $base_directory = JPath::clean($this->_comParams->get('base_directory'));

        //create base folder if not exists
        if (!JFolder::exists($base_directory)) {
            if (!JFolder::create($base_directory))
                die(JText::sprintf('COM_STATICCONTENT_MSG_FAILURE_CREATE_BASE_DIRECTORY', $base_directory));
        }

        //write menus pages
        foreach ($this->_links['menu'] as $menuPage) {
            if (!$this->_writePage($menuPage))
                die(JText::sprintf('COM_STATICCONTENT_MSG_FAILURE_CREATE_FILE', $menuPage->file));
        }

        //write internal pages
        foreach ($this->_links['pages'] as $internalPage) {
            if (!$this->_writePage($internalPage))
                die(JText::sprintf('COM_STATICCONTENT_MSG_FAILURE_CREATE_FILE', $internalPage->file));
        }

        return true;
    }

    private function _writePage($page) {
        $basePath = JPath::clean($this->_comParams->get('base_directory') . DS);

        $fileDirectoryPath = JPath::clean(dirname($basePath . $page->file));
        $filePath = JPath::clean($basePath . $page->file);

        //distant level form root index
        $itemLevel = count(explode('/', $page->file)) - 1;

        $file_content = StaticContentHelperDocument::body($page, $this->_links, $itemLevel);
        if (!JFile::write($filePath, $file_content)) {
            return false;
        }

        return true;
    }

    /**
     * Method to load helper from component
     * 
     * @param string $name
     */
    public function loadHelper($name) {
        $key = 'helpers.' . $name;
        JLoader::import($key, JPATH_COMPONENT);
    }

}