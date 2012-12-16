<?

namespace System\User\Setup
{
	class Variable extends \System\Model\Database
	{
		protected static $attrs = array(
			"name"       => array('varchar'),
			"type"       => array('varchar'),
			"options"    => array('json'),
			"use_select" => array('bool'),
			"use_multi"  => array('bool'),
		);

		protected static $belongs_to = array(
			"category" => array("model" => '\System\User\Setup\Category'),
		);

		private static $allowed_types = array();


		public static function get_allowed_types()
		{
			return self::$allowed_types;
		}


		public static function autoinit()
		{
			self::$allowed_types = array(
				"bool"   => _('Zaškrtávací políčko'),
				"int"    => _('Číslo'),
				"string" => _('Řetězec'),
				"set"    => _('Sada možností'),
				"enum"   => _('Výběr z možností'),
			);
		}
	}
}
