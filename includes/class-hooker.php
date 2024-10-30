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

use ReflectionClass;
use ReflectionMethod;

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( __NAMESPACE__ . '\Hooker' ) ) {
	class Hooker extends Singleton {
		protected function __construct() {
			$class = new ReflectionClass( $this );
			foreach ( $class->getMethods( ReflectionMethod::IS_PUBLIC ) as $method ) {
				if ( $this->is_hook( $method->getName() ) ) {
					$hook     = $this->get_hook( $method->getName() );
					$priority = $this->get_priority( $method->getName() );
					$args     = $method->getNumberOfParameters();

					add_filter( $hook, array( $this, $method->getName() ), $priority, $args );
				}
			}
		}

		protected function get_priority( $method ) {
			$priority = substr( strrchr( $method, '_' ), 1 );

			return is_numeric( $priority ) ? (int) $priority : 10;
		}

		protected function has_priority( $method ) {
			$priority = substr( strrchr( $method, '_' ), 1 );

			return is_numeric( $priority ) ? true : false;
		}

		protected function get_hook( $method ) {
			if ( $this->has_priority( $method ) ) {
				$method = substr( $method, 0, strlen( $method ) - strlen( $this->get_priority( $method ) ) - 1 );
			}
			if ( $this->is_hook( $method ) ) {
				$method = substr( $method, 7 );
			}

			return $method;
		}

		protected function is_hook( $method ) {
			foreach ( array( 'filter_', 'action_' ) as $hook ) {
				if ( 0 === strpos( $method, $hook ) ) {
					return true;
				}
			}

			return false;
		}
	}
}
