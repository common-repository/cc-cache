<?php

/*
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

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( __NAMESPACE__ . '\Plugin' ) ) {
	class Plugin extends Hooker {
		static protected $data = null;

		static public function get( $name = null ) {
			$path = WP_PLUGIN_DIR . '/cc-cache';
			$file = $path . '/plugin.php';
			$dir  = basename( $path );
			$url  = plugins_url( '', $file );

			if ( null === self::$data ) self::$data = get_plugin_data( $file );

			switch ( strtolower( $name ) ) {
				case 'file':
					return $file;
				case 'dir':
					return $dir;
				case 'path':
					return $path;
				case 'url':
					return $url;
				case 'slug':
					return __NAMESPACE__;
				case null:
					return self::$data;
				default:
					if ( ! empty( self::$data[ $name ] ) ) {
						return self::$data[ $name ];
					}

					return null;
			}
		}

		static public function autoload( $name ) {
			if ( 0 !== strpos( $name, __NAMESPACE__ ) ) return;

			$name = strtolower( $name );
			$name = str_replace( '\\', '/', $name );
			$name = str_replace( '_', '-', $name );
			$name = basename( $name );
			$name = sprintf( 'class-%s.php', $name );
			if ( is_file( $file = self::get( 'path' ) . '/includes/' . $name ) ) require_once( $file );
		}

		static public function load( $paths ) {
			if ( ! is_array( $paths ) ) $paths = array( (string)$paths );

			foreach( $paths as $path )
				if ( is_file( $path = locate_template( $path ) ) ) require_once $path;
				elseif ( is_dir( $path ) )
					foreach ( glob( trailingslashit( $path ) . '*.php' ) as $file ) require_once $file;
		}

		protected function __construct() {
			register_activation_hook(   self::get( 'file' ), array( $this, 'activation'   ) );
			register_deactivation_hook( self::get( 'file' ), array( $this, 'deactivation' ) );

			add_action( 'activated_plugin',   array( $this, 'switch_plugin_hook' ), 10, 2 );
			add_action( 'deactivated_plugin', array( $this, 'switch_plugin_hook' ), 10, 2 );

			// Add an action link pointing to the options page.
			add_filter( 'plugin_action_links_' . plugin_basename( self::get( 'file' ) ), array( $this, 'plugin_action_links' ) );

			// Add an action link pointing to the network options page.
			// add_filter( 'network_admin_plugin_action_links_' . self::get( 'file' ), array( $this, 'network_admin_plugin_action_links' ) );

			parent::__construct();
		}

		public function activation() {}

		public function deactivation() {}

		static public function __( $text ) {
			return __( $text, self::get( 'TextDomain' ) );
		}

		static public function apply_filters( $tag, $value ) {
			$args    = func_get_args();
			$args[0] = self::get( 'slug' ) . '\\' . $args[0];

			return call_user_func_array( 'apply_filters', $args );
		}

		static public function get_template( $template, $vars = array() ) {
			$template = self::apply_filters( 'template', $template, $vars );
			if ( ! is_file( $template ) ) return false;

			$vars = self::apply_filters( 'vars', $vars, $template );
			if ( is_array( $vars ) ) extract( $vars, EXTR_SKIP );

			ob_start();
			include $template;

			return ob_get_clean();
		}

		/**
		 * Add settings action link to the plugins page.
		 *
		 * @since    1.0.0
		 */
		public function plugin_action_links( $links ) {
			array_unshift( $links, self::get_template( self::get( 'path' ) . '/templates/link-template.php', array(
				'url'   => self::apply_filters( 'template\link\url', get_admin_url( null, 'options-general.php?page=cache' ) ),
				'link'  => self::apply_filters( 'template\link\link', self::__( 'Settings' ) )
			) ) );
			return $links;
		}

		public function switch_plugin_hook( $plugin, $network_wide = null ) {
			if ( ! $network_wide ) return;

			list( $hook ) = explode( '_', current_filter(), 2 );
			$hook = str_replace( 'activated', 'activate_', $hook );
			$hook .= plugin_basename( self::get( 'file' ) );

			$this->call_user_func_array( 'do_action', array( $hook, false ) );
		}

		protected function call_user_func_array( $function, $args = array() ) {
			if ( is_multisite() ) {
				$blogs = function_exists( 'get_sites' ) ? get_sites( array( 'public' => 1 ) ) : wp_get_sites( array( 'public' => 1 ) );

				foreach ( $blogs as $blog ) {
					$blog = (array)$blog;
					switch_to_blog( $blog['blog_id'] );
					call_user_func_array( $function, $args );
				}

				restore_current_blog();
			} else $function( $args );
		}
	}
}
