<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 4/18/18
 * Time: 10:46 AM
 */

class Brizy_Editor_Asset_AssetProxyProcessor implements Brizy_Editor_Asset_ProcessorInterface {

	/**
	 * @var Brizy_Editor_Asset_Storage
	 */
	private $storage;

	/**
	 * Brizy_Editor_Asset_HtmlAssetProcessor constructor.
	 *
	 * @param Brizy_Editor_Asset_AbstractStorage $storage
	 */
	public function __construct( $storage ) {
		$this->storage = $storage;
	}

	/**
	 * Find and cache all assets and replace the urls with new local ones.
	 *
	 * @param $content
	 *
	 * @return string
	 */
	public function process( $content ) {

		preg_match_all( '/"(.[^"\?]*\?brizy=(.[^"]*))"/im', $content, $matches );

		if ( ! isset( $matches[2] ) ) {
			return $content;
		}

		foreach ( $matches[2] as $i => $url ) {
			$hash_matches = array();
			preg_match( "/^.[^#]*(#.*)$/", $url, $hash_matches );

			$url = preg_replace( "/^(.[^#]*)#.*$/", '$1', $url );

			if ( $url ) {
				// store and replace $url
				$new_url = $this->storage->store( $url );

				if ( $new_url == $url ) {
					continue;
				}

				if ( isset( $hash_matches[1] ) && $hash_matches[1] != '' ) {
					$new_url .= $hash_matches[1];
				}

				$content = str_replace( $matches[1][ $i ], $new_url, $content );
			}
		}

		return $content;
	}


}