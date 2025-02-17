<?php
/*
Plugin Name: Transactium Gravity Forms AddOn
Description: A simple add-on to demonstrate the use of the Add-On Framework
Version: 1.3
Author: Transactium Ltd
Author URI: http://www.transactium.com
------------------------------------------------------------------------
Copyright 2012-2017 Transactium Inc.
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/

define('TRANSACTIUM_GF_ADDON_VERSION', '2.1');
add_action('gform_loaded', array(
    'Transactium_GF_AddOn_Bootstrap',
    'load'
) , 5);
class Transactium_GF_AddOn_Bootstrap
{
    public static function load()
    {
        if (!method_exists('GFForms', 'include_addon_framework'))
        {
            return;
        }
        require_once ('class-transactium-gf-addon.php');
        GFAddOn::register('TransactiumGFAddon');
    }
}
function transactium_gf_addon()
{
    return TransactiumGFAddon::get_instance();
}

// Add Settings action link
add_filter('plugin_action_links_' . plugin_basename(__FILE__) , function ($links)
{
    $plugin_links = array(
        '<a href="' . admin_url('admin.php?page=gf_settings&subview=Transactium') . '">' . __('Settings', 'transactium-gravity-forms-addon') . '</a>'
    );
    // Merge our new link with the default ones
    return array_merge($plugin_links, $links);
});

