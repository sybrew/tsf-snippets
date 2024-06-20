<?php
/**
 * Plugin Name: The SEO Framework - Add image to Schema.org/WebPage
 * Plugin URI: https://theseoframework.com/
 * Description: Adds image data to The SEO Framework's Schema.org graph.
 * Version: 1.0.0
 * Author: Sybre Waaijer
 * Author URI: https://theseoframework.com/
 * License: GPLv3
 * Requires at least: 5.9
 * Requires PHP: 7.4.0
 *
 * @package My_The_SEO_Framework\SchemaImage
 */

namespace My_The_SEO_Framework\SchemaImage;

\add_filter(
	'the_seo_framework_schema_graph_data', // should be 'the_seo_framework_schema_queued_graph_data' for TSF v5.0.7+ to make references work.
	function ( $graph, $args ) {

		foreach ( $graph as &$data ) {
			if ( 'WebPage' === $data['@type'] ) {
				$data['primaryImageOfPage'] = My_Graph_Image::get_instant_ref( $args );
				$data['image']              = &My_Graph_Image::get_dynamic_ref( $args );
			}
		}

		return $graph;
	},
	10,
	2,
);

/**
 * Holds ImageObject generator for Schema.org structured data.
 */
class My_Graph_Image extends \The_SEO_Framework\Meta\Schema\Entities\Reference {

	/**
	 * @var string|string[] $type The Schema @type.
	 */
	public static $type = 'ImageObject';

	/**
	 * @var int $image_id The current image iterated. 0 is the PrimaryImage.
	 */
	public static $image_id = 0;

	/**
	 * @param array|null $args The query arguments. Accepts 'id', 'tax', 'pta', and 'uid'.
	 *                         Leave null to autodetermine query.
	 * @return string The entity ID for $args.
	 */
	public static function get_id( $args = null ) { // phpcs:ignore, VariableAnalysis.CodeAnalysis.VariableAnalysis -- abstract ref.
		return \tsf()->uri()->get_bare_front_page_url()
			. '#/schema/' . current( (array) static::$type ) . '/' . ( static::$image_id ?: 'PrimaryImage' );
	}

	/**
	 * @param array|null $args The query arguments. Accepts 'id', 'tax', 'pta', and 'uid'.
	 *                         Leave null to autodetermine query.
	 * @return ?array $entity The Schema.org graph entity. Null on failure.
	 */
	public static function build( $args = null ) {

		$entity = [];

		// TODO: We'll probably turn 'social' into 'search' when this gets implemented.
		foreach ( \tsf()->image()->get_image_details( $args, false, 'social' ) as $image ) {
			$details = [
				'@id'   => static::get_id( $args ),
				'@type' => &static::$type,
				'url'   => $image['url'],
			];

			// Don't report dimensions if 0; 0 doesn't get scrubbed.
			if ( $image['width'] && $image['height'] ) {
				$details += [
					'width'  => $image['width'],
					'height' => $image['height'],
				];
			}

			if ( $image['caption'] ) {
				$details += [
					// We could store this call, but it's not needed once embedded in TSF.
					'inLanguage' => \tsf()->data()->blog()->get_language(),
					'caption'    => $image['caption'],
				];
			}
			// Don't report filesize if 0; 0 doesn't get scrubbed.
			if ( $image['filesize'] )
				$details += [ 'contentSize' => (string) $image['filesize'] ];

			$entity[] = $details;

			static::$image_id++;
		}

		// Reset counter.
		static::$image_id = 0;

		return $entity ?: null;
	}
}
