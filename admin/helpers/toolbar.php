<?php

/**
 * @package     Static Content Component
 * @author      Julio Pontes - juliopfneto at gmail.com - juliopontes
 * @copyright   Copyright (C) 2005 - 2011 Open Source Matters. All rights reserved.
 * @license     GNU General Public License version 3. See license.txt
 */
defined('_JEXEC') or die('Restricted access');

class ComStaticContentHelperToolbar extends JToolBarHelper {

    /**
     *
     * @param int $height
     * @param int $width
     * @param string $onClose 
     * @return void
     */
    public static function customExport($height = 550, $width = 875, $onClose = '') {
        $top = 0;
        $left = 0;
        $bar = JToolBar::getInstance('toolbar');
        // Add a configuration button.
        $bar->appendButton('Popup', 'options', JText::_('COM_STATICCONTENT_TOOLBAR_CUSTOM_EXPORT'), 'index.php?option=com_staticcontent&amp;view=menus&amp;tmpl=component', $width, $height, $top, $left, $onClose);
    }

    /**
     * @return void
     */
    public static function completeExport() {
        $bar = JToolBar::getInstance('toolbar');
        $bar->appendButton('Link', 'refresh', JText::_('COM_STATICCONTENT_TOOLBAR_CREATE'), 'javascript:requestAllItems();');
    }

}