<?php

/*
	Plugin Name: CC-Cache
	Plugin URI: https://wordpress.org/plugins/cc-cache
	Description: A simple and fast cache plugin based on static-rendered html files served by Apache with mod_rewrite.
	Version: 1.3.1
	Author: Clearcode | Piotr Niewiadomski
	Author URI: http://clearcode.cc
	Text Domain: cc-cache
	Domain Path: /languages/
	License: GPLv3
	License URI: http://www.gnu.org/licenses/gpl-3.0.txt

	Copyright (C) 2016 by Clearcode <http://clearcode.cc>
	and associates (see AUTHORS.txt file).

	This file is part of CC-Cache.

	CC-Cache is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	CC-Cache is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with CC-Cache; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

namespace Clearcode\Cache;

use Clearcode\Cache;

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'get_plugin_data' ) ) require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if ( ! function_exists( 'get_filesystem_method' ) ) require_once( ABSPATH . 'wp-admin/includes/file.php' );

foreach ( array( 'singleton', 'hooker', 'plugin' ) as $class )
	require_once( plugin_dir_path( __FILE__ ) . sprintf( 'includes/class-%s.php', $class ) );

spl_autoload_register( __NAMESPACE__ . '\Plugin::autoload' );

if ( ! has_action( __NAMESPACE__ ) ) do_action( __NAMESPACE__, Cache::instance() );
