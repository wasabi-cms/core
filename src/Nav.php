<?php
/**
 * Nav
 *
 * Wasabi CMS
 * Copyright (c) Frank Förster (http://frankfoerster.com)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Frank Förster (http://frankfoerster.com)
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Wasabi\Core;

use Cake\Core\Exception\Exception;

class Nav
{
    /**
     * Holds all menus.
     *
     * @var Menu[]
     */
    protected static $_menus = [];

    /**
     * Create a new menu.
     *
     * @param string $alias the alias of the menu
     * @return Menu
     * @throws Exception
     */
    public static function createMenu($alias)
    {
        if (isset(self::$_menus[$alias])) {
            throw new Exception(__d('core', 'A Menu with alias "' . $alias . '" already exists.'));
        }
        self::$_menus[$alias] = new Menu($alias);

        return self::$_menus[$alias];
    }

    /**
     * Get a Menu instance or an ordered array
     * of menu items of a menu.
     *
     * @param string $alias the alias of the menu
     * @param boolean $ordered true: return array of priority ordered menu items, false: return WasabiMenu instance
     * @return array|Menu
     * @throws Exception
     */
    public static function getMenu($alias, $ordered = false)
    {
        if (!isset(self::$_menus[$alias])) {
            throw new Exception(__d('core', 'No menu with alias "' . $alias . '" does exist.'));
        }
        if (!$ordered) {
            return self::$_menus[$alias];
        }

        return self::$_menus[$alias]->getOrderedArray();
    }

    /**
     * Clear all menus.
     *
     * @return void
     */
    public static function clear()
    {
        self::$_menus = [];
    }
}