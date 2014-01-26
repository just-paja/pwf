<?

namespace System
{
	class Template
	{
		const DIR_TEMPLATE = '/lib/template';
		const DIR_PARTIAL  = '/lib/template/partial';
		const DIR_ICONS = '/share/icons';
		const DEFAULT_SLOT = 'zzTop';
		const DEFAULT_ICON_THEME = 'default';

		const TYPE_LAYOUT  = 'layout';
		const TYPE_PARTIAL = 'partial';

		const CASE_UPPER = MB_CASE_UPPER;
		const CASE_LOWER = MB_CASE_LOWER;

		private static $lookup_time = 0;

		private static $default_time_format = 'D, d M Y G:i:s e';
		private static $heading_level = 1;
		private static $heading_section_level = 1;

		private static $styles = array(
			array("name" => 'default', "type" => 'text/css'),
		);

		private static $units = array(
			"information" => array("B","kiB", "MiB", "GiB", "TiB", "PiB"),
		);


		public static function get_filename($name, $format = null, $lang = null)
		{
			$format == 'xhtml' && $format = 'html';
			return $name.($lang ? '.'.$lang.'.':'').($format ? '.'.$format:'').'.php';
		}


		public static function is_date($arg)
		{
			return
				$arg instanceof \DateTime ||
				is_numeric($arg) ||
				is_null($arg);
		}


		public static function get_css_color($color)
		{
			if ($color instanceof ColorModel) {
				$c = $color->get_color();
			} elseif (is_array($color)) {
				$c = $color;
			} else {
				throw new \System\Error\Argument("Argument 0 must be instance of System\Model\Color or set of color composition");
			}

			return is_null($c[3]) ?
				'rgb('.$c[0].','.$c[1].','.$c[2].')':
				'rgba('.$c[0].','.$c[1].','.$c[2].','.str_replace(",", ".", floatval($c[3])).')';
		}


		public static function get_color_container($color)
		{
			if ($color instanceof ColorModel) {
				$c = $color->get_color();
			} elseif (is_array($color)) {
				$c = $color;
			} else {
				throw new \System\Error\Argument("Argument 0 must be instance of System\Model\Color or set of color composition");
			}

			return '<span class="color-container" style="background-color:'.self::get_css_color($c).'"></span>';
		}



		static function convert_value($type, $value)
		{
			switch($type){
				case 'information':
					$step = 1024;
					break;
				default:
					$step = 1000;
					break;
			}

			for($i=0; $value>=1024; $i++){
				$value /= 1024;
			}
			return round($value, 2)." ".self::$units[$type][$i];
		}


		/** Get configured or default icon theme
		 * @return string
		 */
		static function get_icon_theme()
		{
			$theme = cfg('icons', 'theme');
			return $theme ? $theme:self::DEFAULT_ICON_THEME;
		}


		/** Convert value to HTML parseable format
		 * @param mixed $value
		 * @return string
		 */
		public static function to_html(\System\Template\Renderer $ren, $value)
		{
			if (is_object($value) && method_exists($value, 'to_html')) {
				return $value->to_html($ren);
			}

			if (is_array($value)) {
				$content = array();

				foreach ($value as $val) {
					$content[] = li(self::to_html($ren, $val));
				}

				return ul('plain inline', $content);
			}

			if ($value instanceof \DateTime) {
				return $ren->format_date($value, 'human');
			}

			if (gettype($value) == 'boolean') {
				return span($value = $value ? 'yes':'no', $ren->trans($value));
			}

			if (gettype($value) == 'float') {
				return number_format($value, 5);
			}

			if (gettype($value) == 'string') {
				return htmlspecialchars_decode($value);
			}

			return $value;
		}


		/** Convert known objects to JSON
		 * @param mixed $value
		 * @param bool [true] Encode into JSON string
		 * @return array|string
		 */
		public static function to_json($value, $encode=true)
		{
			if (is_array($value)) {
				$values = array();

				foreach ($value as $key=>$item) {
					$values[$key] = self::to_json($item, false);
				}

				return $encode ? json_encode($values):$values;
			} else {
				if (is_object($value) && method_exists($value, 'to_json')) {
					return $value->to_json(false);
				}

				if ($value instanceof \DateTime) {
					return $value->format('Y-m-d H:i:s');
				}

				return $value;
			}
		}


		/** Get template full path
		 * @param string $type
		 * @param string $name
		 * @param bool $force
		 */
		public static function find($name, $type = self::TYPE_LAYOUT, $format = null, $locale = null)
		{
			$time = microtime(true);
			$base = self::DIR_TEMPLATE;
			$temp = null;

			if ($type == 'partial') {
				$base = self::DIR_PARTIAL;
			}

			$dirs = \System\Composer::list_dirs($base);
			$path = explode('/', $name);
			$name = array_pop($path);
			$path = implode('/', $path);

			$dirs = \System\Composer::list_dirs($base.($path ? '/'.$path:''));

			foreach ($dirs as $dir) {
				file_exists($temp = $dir.'/'.self::get_filename($name, $format, $locale)) ||
				file_exists($temp = $dir.'/'.self::get_filename($name, $format)) ||
				file_exists($temp = $dir.'/'.self::get_filename($name)) ||
				$temp = null;

				if ($temp) {
					break;
				}
			}

			self::$lookup_time += (microtime(true) - $time);
			return $temp;
		}


		public static function get_lookup_time()
		{
			return self::$lookup_time;
		}

	}
}
