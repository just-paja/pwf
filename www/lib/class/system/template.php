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

		private static $default_time_format = 'D, d M Y G:i:s e';
		private static $heading_level = 1;
		private static $heading_section_level = 1;

		private static $styles = array(
			array("name" => 'default', "type" => 'text/css'),
		);

		private static $units = array(
			"information" => array("B","kiB", "MiB", "GiB", "TiB", "PiB"),
		);


		public static function icon($icon, $size='32', array $attrs = array())
		{
			@list($width, $height) = explode('x', $size, 2);
			!$height && $height = $width;
			$icon = ($icon instanceof Image) ? $icon->thumb(intval($width), intval($height), !empty($attrs['crop'])):self::DIR_ICONS.'/'.$size.'/'.$icon;

			return '<span class="icon isize-'.$size.'" '.\Tag::html_attrs('span', $attrs).'style="background-image:url('.$icon.'); width:'.$width.'px; height:'.$height.'px"></span>';
		}


		public static function get_filename($name, $format = null, $lang = null)
		{
			$format == 'xhtml' && $format = 'html';
			return $name.($lang ? '.'.$lang.'.':'').($format ? '.'.$format:'').'.php';
		}


		public static function get_name($name)
		{
			$base = ROOT.self::PARTIALS_DIR.'/';
			$f = '';

			file_exists($f = $base.self::get_filename($name, Output::get_format(), \System\Locales::get_lang())) ||
			file_exists($f = $base.self::get_filename($name, Output::get_format())) ||
			file_exists($f = $base.self::get_filename($name)) ||
			$f = '';

			return $f;
		}



		public static function link_for($label, $url, $object = array())
		{
			if (!is_array($object)) {
				$object = array("no-tag" => !!$object);
			}

			!isset($object['no-tag']) && $object['no-tag'] = false;
			!isset($object['strict']) && $object['strict'] = false;
			!isset($object['no-activation']) && $object['no-activation'] = false;
			!isset($object['class']) && $object['class'] = '';

			$path = Page::get_path();
			clear_this_url($url); clear_this_url($path);
			$object['class'] = explode(' ', $object['class']);

			$is_root     = $url == '/' && $path == '/';
			$is_selected = $url && !$object['no-activation'] && (($object['strict'] && ($url == $path || $url == $path.'/')) || (!$object['strict'] && strpos($path, $url) === 0));

			if ($is_root || ($url != '/' && $is_selected)) {
				$object['class'][] = 'link-selected';
			}

			$object['class'] = implode(' ', $object['class']);

			return (($object['no-tag'] && $path == $url) ?
					'<span class="link'.($object['class'] ? ' '.$object['class']:NULL).'">':
					'<a href="'.$url.'"'.\Tag::html_attrs('a', $object).'>'
				).$label.
				(($object['no-tag'] && $path == $url) ? '</span>':'</a>');
		}


		public static function icon_for($icon, $size=32, $url, $label = NULL, $object = array())
		{
			def($object['label'], '');
			def($object['label_left'], false);

			$object['title'] = $label;
			return self::link_for(
				($object['label_left'] && $object['label'] ? self::label_text($object['label']):'').
				self::icon($icon, $size).
				(!$object['label_left'] && $object['label'] ? self::label_text($object['label']):''), $url, $object);
		}


		public static function label_for($icon, $size=32, $label, $url, $object = array())
		{
			$object['label'] = $label;
			return \System\Template::icon_for($icon, $size, $url, $label, $object);
		}


		public static function label_right_for($icon, $size=32, $label, $url, $object = array())
		{
			$object['label'] = $label;
			$object['label_left'] = true;
			return \System\Template::icon_for($icon, $size, $url, $label, $object);
		}


		public static function label_text($label)
		{
			return span('lt', $label);
		}


		/** Format and translate datetime format
		 * @param mixed  $date
		 * @param string $format Format name
		 * @return string
		 */
		public static function format_date($date, $format = 'std')
		{
			if (is_null($date)) {
				$date = new \DateTime();
			}

			if ($date instanceof \DateTime) {
				$d = $date->format(\System\Locales::get('date:'.$format));
				return strpos($format, 'html5') === 0 ? $d:\System\Locales::translate_date($d);
			} elseif(is_numeric($date)) {
				$d = date(\System\Locales::get('date:'.$format), $date);
				return strpos($format, 'html5') === 0 ? $d:\System\Locales::translate_date($d);
			} else {
				return $date;
			}
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
		public static function to_html($value)
		{
			if (is_object($value) && method_exists($value, 'to_html')) {
				return $value->to_html();
			}

			if ($value instanceof \DateTime) {
				return format_date($value, 'human');
			}

			if (gettype($value) == 'boolean') {
				return span($value = $value ? 'yes':'no', l($value));
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
					return format_date($value, 'sql');
				}

				return $value;
			}
		}



		/** Get template full path
		 * @param string $type
		 * @param string $name
		 * @param bool $force
		 */
		public static function find($name, $type = self::TYPE_LAYOUT, $format = null)
		{
			$base = ROOT;
			$temp = null;

			switch ($type)
			{
				case 'layout': $base .= self::DIR_TEMPLATE.'/'; break;
				case 'partial': $base .= self::DIR_PARTIAL.'/'; break;
			}

			file_exists($temp = $base.self::get_filename($name, $format, \System\Locales::get_lang())) ||
			file_exists($temp = $base.self::get_filename($name, $format)) ||
			file_exists($temp = $base.self::get_filename($name)) ||
			$temp = false;

			return $temp;
		}

	}
}
