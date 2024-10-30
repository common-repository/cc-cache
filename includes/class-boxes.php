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
if ( ! class_exists( __NAMESPACE__ . '\Boxes' ) ) {
	class Boxes extends Hooker {
		public function action_admin_init() {
			if ( current_user_can( 'manage_options' ) ) // if is admin
				add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		}

		public function add_meta_boxes() {
			foreach ( Settings::instance()->post_types as $post_type ) {
				add_meta_box( Cache::get( 'slug' ) . '\meta_box',
					Cache::__( 'Cache' ),
					array( $this, 'meta_box' ),
					$post_type,
					'side'
				);
			}
		}

		public function meta_box( $post ) {
			wp_nonce_field( Cache::get( 'slug' ), Cache::get( 'slug' ) );

			$meta = '_' . Cache::get( 'slug' );
			echo Settings::input( 'checkbox',
				$meta,
				$meta,
				(string)true,
				ucfirst( Cache::__( 'disable' ) ),
				checked( get_post_meta( $post->ID, $meta, true ), true, false )
			);
		}

		public function action_current_screen( $screen ) {
			if ( 'post' != $screen->base ) return;
			add_action( 'save_post', array( $this, 'save_post' ), 0 );
		}

		public function save_post( $post_id ) {
			$post = get_post( $post_id );
			if ( ! in_array( $post->post_type, Settings::instance()->post_types ) ) return;

			// nonce is set
			if ( ! isset( $_REQUEST[Cache::get( 'slug' )] ) ) return;

			// nonce is valid
			if ( ! check_admin_referer( Cache::get( 'slug' ), Cache::get( 'slug' ) ) ) return;

			// is autosave
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )  return;

			// user can
			$post_type = get_post_type_object( $post->post_type );
			if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ) return;

			// save
			$meta = '_' . Cache::get( 'slug' );
			if ( empty( $_REQUEST[$meta] ) ) delete_post_meta( $post_id, wp_slash( $meta ) );
			else add_post_meta( $post_id, wp_slash( $meta ), true );
		}
	}
}
