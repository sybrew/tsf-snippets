<?php
/**
 * Plugin Name: The SEO Framework - Add image to Schema.org/WebPage
 * Plugin URI: https://theseoframework.com/
 * Description: Adds image data to The SEO Framework's Schema.org graph.
 * Version: 1.1.0
 * Author: Sybre Waaijer
 * Author URI: https://theseoframework.com/
 * License: GPLv3
 * Requires at least: 5.9
 * Requires PHP: 7.4.0
 * Requires Plugins: autodescription
 *
 * @package My_The_SEO_Framework\SchemaImage
 */

namespace My_The_SEO_Framework\SchemaImage;

\add_filter(
	'the_seo_framework_schema_queued_graph_data',
	/**
	 * @since 1.0.0
	 * @param array[]    $graph A sequential list of graph entities.
	 * @param array|null $args  The query arguments. Accepts 'id', 'tax', 'pta', and 'uid'.
	 *                          Is null when the query is autodetermined.
	 * @return array[] The adjusted graph.
	 */
	function ( $graph, $args ) {

		foreach ( $graph as &$data ) {
			if ( 'WebPage' === $data['@type'] ) {
				$data['image']              = &My_Graph_Image::get_dynamic_ref( $args );
				$data['primaryImageOfPage'] = My_Graph_Image::get_instant_ref( $args );
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
	 * @since 1.0.0
	 * @var string|string[] $type The Schema @type.
	 */
	public static $type = 'ImageObject';

	/**
	 * @since 1.0.0
	 * @var int $image_id The current image iterated. 0 is the PrimaryImage.
	 */
	public static $image_id = 0;

	/**
	 * @since 1.0.0
	 * @param array|null $args The query arguments. Accepts 'id', 'tax', 'pta', and 'uid'.
	 *                         Leave null to autodetermine query.
	 * @return string The entity ID for $args.
	 */
	public static function get_id( $args = null ) { // phpcs:ignore, VariableAnalysis.CodeAnalysis.VariableAnalysis -- abstract ref.
		return \tsf()->uri()->get_bare_front_page_url()
			. '#/schema/' . current( (array) static::$type ) . '/' . ( static::$image_id ?: 'PrimaryImage' );
	}

	/**
	 * @since 1.0.0
	 * @param array|null $args The query arguments. Accepts 'id', 'tax', 'pta', and 'uid'.
	 *                         Leave null to autodetermine query.
	 * @return ?array $entity The Schema.org graph entity. Null on failure.
	 */
	public static function build( $args = null ) {

		$entity = [];

		// We should also decouple this from the My_Graph_Image object since static::$type needs to become iterably changed.
		// Hence, this will probably be the first graph item that is iterable.
		foreach ( \tsf()->image()->get_image_details( $args, false, 'schema' ) as $image ) {
			$details = [
				'@id'        => static::get_id( $args ),
				'@type'      => &static::$type,
				'url'        => $image['url'],
				'contentUrl' => $image['url'],
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
