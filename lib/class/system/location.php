<?

/** Locations, GPS application
 * @package system
 * @subpackage media
 */
namespace System
{
	/** Saves address, gps coordinates and provides Google Map functions
	 * @package system
	 * @subpackage media
	 */
	class Location extends \System\Model\Database
	{
		/** Model attributes */
		protected static $attrs = array(
			"name"          => array("varchar", "is_unique" => true),
			"addr"          => array("varchar"),
			"street"        => array("varchar"),
			"street_number" => array("varchar"),
			"city"          => array("varchar"),
			"country"       => array("varchar"),
			"gps"           => array("point"),
			"desc"          => array("text", "default" => ''),
			"site"          => array("url", "default" => ''),
			"author"        => array('belongs_to', "model" => '\System\User'),
		);


		/** Returns URL to the Google Static Maps
		 * @param int $w Width
		 * @param int $h Height
		 * @param string $type
		 * @return string
		 */
		public function map($w = \System\Gps::MAP_WIDTH_DEFAULT, $h = \System\Gps::MAP_HEIGHT_DEFAULT, $type = \System\Gps::GMAP_TYPE_ROADMAP)
		{
			return 'http://maps.googleapis.com/maps/api/staticmap?sensor=false&amp;zoom=14&amp;size='.$w.'x'.$h.'&amp;maptype='.$type.'&amp;markers='.$this->gps->gps();
		}


		/** Get link to the Google Maps
		 * @return string
		 */
		public function map_link()
		{
			return 'http://maps.google.com/maps?q='.$this->gps->gps();
		}


		/** Get HTML representation of location
		 * @param int $w Width
		 * @param int $h Height
		 * @param string $type
		 * @return string
		 */
		public function map_html(\System\Template\Renderer $ren, $w = \System\Gps::MAP_WIDTH_DEFAULT, $h = \System\Gps::MAP_HEIGHT_DEFAULT, $type = \System\Gps::GMAP_TYPE_ROADMAP)
		{
			return \Tag::a(array(
				"output"  => false,
				"class"   => 'location_map',
				"href"    => $this->map_link(),
				"content" => $this->to_html($ren, $w, $h, $type)
			));
		}


		/** Convert location to html
		 * @param int $w
		 * @param int $h
		 * @param int $type
		 * @return string
		 */
		public function to_html(\System\Template\Renderer $ren, $w = \System\Gps::MAP_WIDTH_DEFAULT, $h = \System\Gps::MAP_HEIGHT_DEFAULT, $type = \System\Gps::GMAP_TYPE_ROADMAP)
		{
			return $this->gps->to_html($ren, $w, $h, $type);
		}
	}
}