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

namespace Clearcode;

use Clearcode\Cache\Plugin;
use Clearcode\Cache\Settings;
use Clearcode\Cache\Boxes;
use Minify_HTML;

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( __NAMESPACE__ . '\Cache' ) ) {
	class Cache extends Plugin {
		protected $path = null;

		public function __construct() {
			$this->path = self::apply_filters( 'path', WP_CONTENT_DIR . '/cache/' );

			Settings::instance();
			Boxes::instance();

			parent::__construct();
		}

		public function __get( $name ) {
			if ( isset( $this->$name ) ) return $this->$name;
		}

		public function activation() {
			delete_option( self::get( 'slug' ) );
			add_option(    self::get( 'slug' ), array(
				'post_types' => Settings::instance()->post_types,
				'archives'   => Settings::instance()->archives,
				'status'     => Settings::instance()->status,
				'minify'     => Settings::instance()->minify
			) );
		}

		public function deactivation() {
			delete_option( self::get( 'slug' ) );
		}

		/**
		 * Echo filesystem notice displayed near the top of admin pages.
		 */
		public function filesystem_notice() {
			echo self::get_template( 'filesystem', array(
					'name'    => self::apply_filters( 'template\filesystem\name', self::__( 'Cache' ) ),
					'message' => self::apply_filters( 'template\filesystem\message', self::__( 'You do not have write access to' ) ),
					'path'    => self::apply_filters( 'template\filesystem\path', $this->path )
				)
			);
		}

		/**
		 * Echo mod_rewrite notice displayed near the top of admin pages.
		 */
		public function rewrite_notice() {
			echo self::get_template( 'rewrite', array(
					'name'    => self::apply_filters( 'template\rewrite\name', self::__( 'Cache' ) ),
					'message' => self::apply_filters( 'template\rewrite\message', self::__( 'You do not have Apache server and/or the mod_rewrite module loaded.' ) )
				)
			);
		}

		protected function set_filesystem() {
			// TODO add parameter filters
			switch ( get_filesystem_method( array(), $this->path ) ) {
				case 'direct':
					$credentials = request_filesystem_credentials( site_url() . '/wp-admin/', '', false, $this->path );
					if ( WP_Filesystem( $credentials, $this->path ) ) return true;
				case 'ftpext':
				case 'ssh2':
				case 'ftpsockets':
				default:
					return false;
			}
		}

		static public function get_template( $template, $vars = array() ) {
			return parent::get_template( self::get( 'path' ) . '/templates/' . $template . '-template.php', $vars );
		}

		static public function get_permalink() {
			$permalink = is_archive() ? get_post_type_archive_link( get_post_type() ) : get_permalink( get_post() );
			if ( is_home() && $post_id = get_option( 'page_for_posts' ) )
				$permalink = ! get_option( 'page_on_front' ) ? get_home_url() : get_permalink( $post_id );
			if ( is_home() && is_front_page() ) $permalink = get_home_url();

			return $permalink;
		}

		protected function get_path() {
			return is_multisite() ? $this->path . get_current_site()->id . '/' . get_current_blog_id() . '/' : $this->path;
		}

		protected function is_cacheable() {
			if ( ! get_option( 'permalink_structure' ) ) return false;
			if ( ! Settings::instance()->status )        return false;
			if ( isset( $_REQUEST['cache'] )     && 'false' == $_REQUEST['cache'] )     return false;
			if ( isset( $_SERVER['HTTP_CACHE'] ) && 'false' == $_SERVER['HTTP_CACHE'] ) return false;

			if ( in_array( get_post_type(), Settings::instance()->post_types ) && is_admin()    ) return true;
			if ( in_array( get_post_type(), Settings::instance()->archives   ) && is_archive()  ) return true;

			// https://codex.wordpress.org/Function_Reference/is_home
			// https://codex.wordpress.org/Function_Reference/is_front_page
			if ( is_home() && is_front_page() ) return in_array( 'post', Settings::instance()->archives ) ? true : false;

			$post_id = is_home() ? get_option( 'page_for_posts' ) : get_the_ID();
			$status  = ! get_post_meta( $post_id, '_' . self::get( 'slug' ), true ) ? true : false;
			if ( in_array( get_post_type(), Settings::instance()->post_types ) && is_singular() && $status ) return true;
			if ( in_array( get_post_type(), Settings::instance()->archives   ) && is_home()     && $status ) return true;

			return false;
		}

		protected function minify( $content ) {
			foreach( array( 'HTML' => 'Minify_HTML', 'CSS' => 'Minify_CSS', 'JSMin' => 'JSMin' ) as $file => $class )
				if ( ! class_exists( $class ) ) require_once( self::get( 'path' ) . '/vendor/Minify/' . $file . '.php' );

			try {
				return Minify_HTML::minify( $content, array(
					'cssMinifier' => array( 'Minify_CSS', 'minify' ),
					'jsMinifier'  => array( 'JSMin',      'minify' )
				) );
			} catch ( Exception $exception ) {
				if ( WP_DEBUG && WP_DEBUG_DISPLAY )
					return $exception->getMessage();
			}

			return $content;
		}

		public function filter_template_include_0( $template ) {
			if ( is_user_logged_in() ) return $template;
			if ( $this->is_cacheable() && $this->set_filesystem() ) ob_start();
			return $template;
		}

		public function filter_shutdown_0() {
			global $wp_filesystem;
			if ( ! $wp_filesystem )        return;
			if ( ! $this->is_cacheable() ) return;
			if ( is_user_logged_in() )     return;

			$permalink = self::get_permalink();

			$content = ob_get_clean();
			if ( Settings::instance()->minify ) $content = $this->minify( $content );

			echo $body = wp_remote_retrieve_body( $this->regenerate( $permalink, array( 'blocking' => true, 'headers' => array( 'cache' => 'false' ) ) ) );
			if ( Settings::instance()->minify ) $body = $this->minify( $body );

			if ( ! empty( $content ) && ! empty( $body ) && $content == $body ) {
				$directory = str_replace( get_home_url(), '', $permalink );
				$directory = $this->get_path() . $directory;
				$directory = str_replace( $this->path, '', $directory );

				$directories = explode( '/', $directory );
				array_unshift( $directories, '' );
				$path = $this->path;
				foreach ( $directories as $directory ) {
					$wp_filesystem->mkdir( $path .= '/' . $directory );
				}

				$file = $path . '/index.html';
				$wp_filesystem->touch( $file );

				$time  = $wp_filesystem->mtime( $file );
				$stamp = self::get_template( 'stamp', array(
					'time' => self::apply_filters( 'template\stamp\time', date( 'Y-m-d H:i:s', $time ) ),
				) );

				$wp_filesystem->put_contents(
					$file,
					substr_replace( $content, "\n$stamp\n", stripos( $content, '</body>' ), 0 ), // Notice! if HTML
					FS_CHMOD_FILE
				);
			}

			echo $content;
		}

		protected function clear( $permalink ) {
			global $wp_filesystem;

			$dir = $this->get_path() . trim( str_replace( get_home_url(), '', $permalink ), '/' );
			if ( $dir != $this->get_path() ) $wp_filesystem->delete( $dir );
			return $wp_filesystem->delete( $dir . '/index.html' );
		}

		protected function regenerate( $permalink, $args = array() ) {
			return wp_safe_remote_get( $permalink, wp_parse_args( $args, array(
				'blocking' => false,
				'sslverify' => false
			) ) );
		}

		public function action_update_option() {
			$this->set_filesystem();
		}

		public function action_updated_option( $option, $old_value, $new_value ) {
			if ( ! $this->set_filesystem() ) return;
			if ( ! in_array( $option, array( 'page_for_posts', 'page_on_front' ) ) ) return;

			foreach ( array( get_permalink( $old_value ), get_permalink( $new_value ) ) as $permalink ) {
				$this->clear( $permalink );
				$this->regenerate( $permalink );
			}
		}

		/**
		 * Save post metadata when a post is saved.
		 *
		 * @param int $post_id The post ID.
		 * @param post $post The post object.
		 * @param bool $update Whether this is an existing post being updated or not.
		 */
		public function action_save_post( $post_id, $post, $update ) {
			if ( ! $this->set_filesystem() ) return;
			if ( ! $this->is_cacheable()   ) return;

			if ( ! $update ) return;
			if ( wp_is_post_revision( $post_id ) ) return;

			$permalinks = array( get_permalink( $post_id ) );
			if ( $permalink = get_post_type_archive_link( get_post_type( $post_id ) ) ) $permalinks[] = $permalink;
			if ( get_post_type( $post_id ) === 'post' && $post_id = get_option( 'page_for_posts' ) ) $permalinks[] = get_permalink( $post_id );
			foreach( $permalinks as $permalink ) {
				$this->clear( $permalink );
				$this->regenerate( $permalink );
			}
		}

		public function action_current_screen( $current_screen ) {
			if ( 'settings_page_cache' !== $current_screen->id ) return;

			if ( ! $this->set_filesystem() ) add_action( 'admin_notices', array( $this, 'filesystem_notice' ) );
			if ( ! got_mod_rewrite() )       add_action( 'admin_notices', array( $this, 'rewrite_notice' ) );
		}
	}
}
