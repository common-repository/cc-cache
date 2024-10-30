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

use Clearcode\Cache;

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( __NAMESPACE__ . '\Settings' ) ) {
	class Settings extends Hooker {
		protected $post_types = array( 'post', 'page' );
		protected $archives   = array( 'post' );
		protected $status     = true;
		protected $minify     = true;

		protected function __construct() {
			if ( $settings = get_option( Cache::get( 'slug' ) ) ) {
				$this->post_types = $settings['post_types'];
				$this->archives   = $settings['archives'];
				$this->status     = $settings['status'];
				$this->minify     = $settings['minify'];
			}

			parent::__construct();
		}

		public function __get( $name ) {
			if ( isset( $this->$name ) ) return $this->$name;
		}

		public function action_admin_menu_999() {
			add_options_page(
				Cache::__( 'Cache Settings' ),
				Cache::get_template( 'div', array(
					'id'      => Cache::apply_filters( 'template\div\id', 'cache' ),
					'class'   => Cache::apply_filters( 'template\div\class', 'dashicons-before dashicons-backup' ),
					'content' => Cache::apply_filters( 'template\div\content', Cache::__( 'Cache' ) ) ) ),
				'manage_options',
				'cache',
				array( $this, 'page' )
			);
		}

		public function action_admin_bar_menu_999( $wp_admin_bar ) {
			$wp_admin_bar->add_node( array(
				'id'    => 'cache',
				'title' => Cache::get_template( 'span', array(
					'class' => Cache::apply_filters( 'template\span\class', 'ab-icon' ) ) ) . Cache::__( 'Cache' ),
				'href'  => get_admin_url( null, 'options-general.php?page=cache' )
			) );
		}

		public function action_admin_enqueue_scripts() {
			$this->action_wp_enqueue_scripts();
		}

		public function action_wp_enqueue_scripts() {
			if ( ! is_admin_bar_showing() ) return;

			wp_register_style( 'cc-cache', Cache::get( 'url' ) . '/assets/css/style.css', array(), Cache::get( 'Version' ) );
			wp_enqueue_style( 'cc-cache' );
		}

		public function page() {
			echo Cache::get_template( 'page' );

			$blogs = array();
			if ( is_multisite() )
				$blogs = function_exists( 'get_sites' ) ? get_sites( array( 'public' => 1 ) ) : wp_get_sites( array( 'public' => 1 ) );

			$rules = Cache::get_template( 'rules', array(
				'blogs' => Cache::apply_filters( 'template\rules\blogs', (array)$blogs )
			) );

			if ( file_exists( $file = ABSPATH . '.htaccess' ) &&
				str_replace( "\r\n", "\n", $rules ) != implode( "\n", extract_from_markers( $file, $marker = 'Cache' ) ) )
				echo Cache::get_template( 'htaccess', array(
					'message' => Cache::apply_filters( 'template\htaccess\message', Cache::__( 'Add following rules to the beginning of' ) ),
					'file'    => Cache::apply_filters( 'template\htaccess\file', $file ),
					'marker'  => Cache::apply_filters( 'template\htaccess\marker', $marker ),
					'rules'   => Cache::apply_filters( 'template\htaccess\rules', htmlspecialchars( $rules ) )
				) );

			if ( ! defined( 'FS_METHOD' ) || 'direct' !== FS_METHOD ) {
				if ( file_exists( $file = ABSPATH . 'wp-config.php' ) ) :
				elseif ( file_exists( $file = dirname( ABSPATH ) . '/wp-config.php' ) ) :
				else : $file = 'wp-config.php';
				endif;
				echo Cache::get_template( 'config', array(
					'message' => Cache::apply_filters( 'template\config\message', Cache::__( 'Add following constant to' ) ),
					'file'    => Cache::apply_filters( 'template\config\file', $file )
				) );
			}
		}

		public function action_admin_init() {
			register_setting(     'cache', Cache::get( 'slug' ), array( $this, 'sanitize' ) );
			add_settings_section( 'cache', Cache::__( 'Cache' ), array( $this, 'section' ), 'cache' );

			add_settings_field( 'post_types', Cache::__( 'Post types' ), array( $this, 'post_types' ), 'cache', 'cache' );
			add_settings_field( 'archives',   Cache::__( 'Archives' ),   array( $this, 'archives' ),   'cache', 'cache' );
			add_settings_field( 'minify',     Cache::__( 'Minify' ),     array( $this, 'minify' ),     'cache', 'cache' );
			add_settings_field( 'status',     Cache::__( 'Status' ),     array( $this, 'status' ),     'cache', 'cache' );
			add_settings_field( 'clear',      Cache::__( 'Clear' ),      array( $this, 'clear' ),      'cache', 'cache' );
		}

		public function section() {
			echo Cache::get_template( 'section', array(
				'header' => Cache::apply_filters( 'template\settings\header', Cache::__( 'Cache Settings' ) ),
				'id'     => Cache::apply_filters( 'template\settings\id', 'cache' )
			) );
		}

		// TODO errors
		public function sanitize( $settings ) {
			foreach( array( 'post_types', 'archives', 'minify', 'status', 'clear' ) as $setting )
				if ( empty( $settings[$setting] ) ) $settings[$setting] = null;

			global $wp_filesystem;
			if ( (bool)$settings['clear'] && ! empty( $wp_filesystem ) )
				foreach( $wp_filesystem->dirlist( Cache::instance()->path ) as $directory )
					$wp_filesystem->delete( Cache::instance()->path . $directory['name'], true );

			return array(
				'post_types' => array_intersect( (array)$settings['post_types'], $this->get_post_types( 'names' ) ),
				'archives'   => array_intersect( (array)$settings['archives'],   $this->get_post_types( 'names', array( 'has_archive' => true ) ) ),
				'minify'     => (bool)$settings['minify'],
				'status'     => (bool)$settings['status'],
				'clear'      => false
			);
		}

		static public function input( $type, $id, $name, $value, $label = '', $checked = '' ) {
			return Cache::get_template( 'input', array(
				'type'    => Cache::apply_filters( 'template\input\type',    $type ),
				'id'      => Cache::apply_filters( 'template\input\id',      $id ),
				'name'    => Cache::apply_filters( 'template\input\name',    $name ),
				'value'   => Cache::apply_filters( 'template\input\value',   $value ),
				'label'   => Cache::apply_filters( 'template\input\label',   $label ),
				'checked' => Cache::apply_filters( 'template\input\checked', $checked )
			) );
		}

		protected function get_post_types( $output, $args = array() ) {
			$args = array_merge( array( 'public' => true, '_builtin' => false ), $args );
			switch( $output ) {
				case 'objects':
					$array = array( get_post_type_object( 'post' ), get_post_type_object( 'page' ) );
					return array_merge( $array, get_post_types( $args, 'objects' ) );
				case 'names':
				default:
					return array_merge( array( 'post', 'page' ), get_post_types( $args, 'names' ) );
			}
		}

		public function post_types() {
			foreach( $this->get_post_types( 'objects' ) as $post_type ) {
				$checked = checked( in_array( $post_type->name, $this->post_types ), true, false );
				echo self::input( 'checkbox',
					Cache::get( 'slug' ) . '\post_types\\' . $post_type->name,
					Cache::get( 'slug' ) . '[post_types][]',
					$post_type->name,
					$post_type->labels->name,
					$checked
				);
			}
		}

		public function archives() {
			foreach( $this->get_post_types( 'objects', array( 'has_archive' => true ) ) as $post_type ) {
				if ( 'page' == $post_type->name ) continue;
				$checked = checked( in_array( $post_type->name, $this->archives ), true, false );
				echo self::input( 'checkbox',
					Cache::get( 'slug' ) . '\archives\\' . $post_type->name,
					Cache::get( 'slug' ) . '[archives][]',
					$post_type->name,
					$post_type->labels->name,
					$checked
				);
			}
		}

		public function minify() {
			foreach( array_reverse( array( Cache::__( 'disable' ), Cache::__( 'enable' ) ), true ) as $key => $value )
				echo self::input( 'radio',
					Cache::get( 'slug' ) . '\minify\\' . $value,
					Cache::get( 'slug' ) . '[minify]',
					(string)$key,
					ucfirst( $value ),
					checked( $this->minify, (bool)$key, false )
				);
		}

		public function status() {
			foreach( array_reverse( array( Cache::__( 'disable' ), Cache::__( 'enable' ) ), true ) as $key => $value )
				echo self::input( 'radio',
					Cache::get( 'slug' ) . '\status\\' . $value,
					Cache::get( 'slug' ) . '[status]',
					(string)$key,
					ucfirst( $value ),
					checked( $this->status, (bool)$key, false )
				);
		}

		public function clear() {
			echo self::input( 'checkbox',
				Cache::get( 'slug' ) . '\clear',
				Cache::get( 'slug' ) . '[clear]',
				true,
				Cache::__( 'Delete all files from' ) . sprintf( ': <code>%s</code>', Cache::instance()->path )
			);
		}
	}
}
