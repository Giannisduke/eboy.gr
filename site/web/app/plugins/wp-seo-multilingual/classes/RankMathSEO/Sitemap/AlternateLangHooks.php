<?php

namespace WPML\WPSEO\RankMathSEO\Sitemap;

use WPML\WPSEO\RankMathSEO\Utils;
use WPML\WPSEO\Shared\Sitemap\BaseAlternateLangHooks;
use RankMath\Sitemap\Generator;

class AlternateLangHooks extends BaseAlternateLangHooks {

	public function add_hooks() {
		add_action(
			'parse_request',
			function ( $wp ) {
				if ( isset( $wp->query_vars['sitemap'] ) ) {
					$this->add_sitemap_hooks( $wp->query_vars['sitemap'] );
				}
			}
		);
	}

	/**
	 * @param string $type
	 */
	public function add_sitemap_hooks( $type ) {
		add_filter( 'rank_math/sitemap/' . $type . '_urlset', [ $this, 'addNamespace' ] );
		add_filter( 'rank_math/sitemap/' . $type . '_sitemap_url', [ $this, 'addAlternateLangDataToFirstLinks' ], 10, 2 );
		add_filter( 'rank_math/sitemap/entry', [ $this, 'addAlternateLangData' ], 10, 3 );
		add_filter( 'rank_math/sitemap/url', [ $this, 'insertAlternateLinks' ], 10, 2 );
	}

	/**
	 * @return string
	 */
	protected function getUtils() {
		return Utils::class;
	}

	/**
	 * @param array     $url
	 * @param Generator $generator
	 *
	 * @return string
	 */
	public function addAlternateLangDataToFirstLinks( $url, $generator ) {
		$url = $this->addAlternateLangDataToFirstLink( $url );

		return $generator->sitemap_url( $url );
	}
}
