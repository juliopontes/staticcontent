<?php

/**
 * @package     Static Content Component
 * @author      Julio Pontes - juliopfneto at gmail.com - juliopontes
 * @copyright   Copyright (C) 2005 - 2011 Open Source Matters. All rights reserved.
 * @license     GNU General Public License version 3. See license.txt
 */
defined('_JEXEC') or die('Restricted access');

abstract class StaticContentHelperUrl {

    /**
     * Return URL without GET parameters
     * 
     * @param string $link
     * @return formated string
     */
    static public function stripParameter($link) {
        return substr($link, 0, strpos($link, '?'));
    }

    /**
     * Create a page name based on URL
     * 
     * @param string $url
     * @return string filename
     */
    static public function createPageName($url) {
        if ($url == JURI::root()) {
            $url = 'index';
        } else {
            $url = self::getPath($url);
            if (self::hasParameters($url)) {
                if (self::isPrintLink($url)) {
                    $url = self::stripParameter($url);
                    $url .= '-print';
                } else {
                    $url = self::stripParameter($url);
                }
            }
        }

        $foundIndex = strpos($url, 'index.php/');
        if ($foundIndex !== false) {
            $url = substr($url, $foundIndex);
        }

        $url = self::addFormat($url);

        return $url;
    }

    /**
     * Return a replative link
     * 
     * @param string $url
     * @return string $url formated string
     */
    static public function getRelativeLink($url) {
        if (!JURI::isInternal($url)) {
            return $url;
        }

        //remove root url
        $url = str_replace(JURI::root(), '', $url);
        //remove base
        $url = str_replace(JURI::base(true) . '/', '', $url);
        $url = str_replace(JURI::base(true), '', $url);

        return $url;
    }

    /**
     * Return full link url
     * 
     * @param string $url
     * @return string $url formated string
     */
    static public function getFullLink($url) {
        if (!JURI::isInternal($url)) {
            return $url;
        }

        //remove root url
        $url = str_replace(JURI::root(), '', $url);
        //remove base
        $url = str_replace(JURI::base(true) . '/', '', $url);
        //add root
        $url = JURI::base() . $url;
        //strip format
        $url = self::stripFormat($url);

        return $url;
    }

    /**
     * Count page item level
     * 
     * @param string $url
     * @return INT	item level from root
     */
    static public function getUrlLevel($url) {
        $url = self::getRelativeLink($url);

        $itemLevel = count(explode('/', $url)) - 1;

        return $itemLevel;
    }

    /**
     * return URI path form url
     * 
     * @param string $url
     * @return string $url
     */
    static public function getPath($url) {
        $url = self::getRelativeLink($url);
        $url = str_replace('index.php/', '', $url);

        return $url;
    }

    /**
     * Check if url is a print page
     * 
     * @param string $url
     * @return boolean TRUE if its a print page
     */
    static public function isPrintLink($url) {
        return (strpos($url, 'print=1') !== false) ? true : false;
    }

    /**
     * Check for GET parameters URL
     * 
     * @param string $url
     * @return boolean TRUE if url has ?
     */
    static public function hasParameters($url) {
        return (strpos($url, '?') !== false) ? true : false;
    }

    /**
     * remove .html from url
     *
     * @param string $url
     * @return formated string
     */
    static public function stripFormat($url) {
        $format = '.html';
        $url = str_replace($format, '', $url);

        return $url;
    }

    /**
     * Add a .html in end of url
     *
     * @param string $url
     * @return formated string
     */
    static public function addFormat($url) {
        $format = '.html';
        $url = self::stripFormat($url);

        return $url . $format;
    }

}