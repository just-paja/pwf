<?

namespace System\User\Setup
{
	class Variable extends \System\Model\Basic
	{
		protected static $attrs = array(
			"int"      => array('id_user_setup_category'),
			"string"   => array('name', 'type'),
			"json"     => array('options'),
			"datetime" => array('created_at', 'updated_at'),
			"bool"     => array('use_select', 'use_multi'),
		);

		protected static $belongs_to = array(
			"category" => array("model" => '\Core\User\Setup\Category'),
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
