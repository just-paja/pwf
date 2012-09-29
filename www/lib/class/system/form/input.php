<?

namespace System\Form
{
	class Input extends \System\Form\Element
	{
		protected static $attrs = array(
			"string" => array('name', 'type', 'id', 'label', 'kind', 'content'),
			"int"    => array('maxlen'),
			"float"  => array(),
			"bool"   => array('required', 'checked'),
			"mixed"  => array('value'),
			"array"  => array('options'),
		);
		
		protected static $required = array(
			'name', 'kind', 
		);

		protected static $kinds = array('input', 'textarea', 'select', 'button');
		protected static $kinds_content_value = array('textarea');
		protected static $kinds_no_label = array('button');
		protected static $types = array(
			'textarea',
			'select',
			'text',
			'number',
			'date',
			'datetime',
			'file',
			'range',
			'url',
			'email',
			'hidden',
			'button',
			'submit',
		);


		protected function construct()
		{
			!$this->type && self::get_default_type();
			!$this->kind && self::get_default_kind();
			$this->kind = in_array($this->type, self::$kinds) ? $this->type:self::get_default_kind();
			!$this->id && $this->id = $this->name;

			$this->type == 'submit' && $this->kind = 'button';

			if (!$this->name) {
				throw new \MissingArgumentException('You must enter input name!', $this->type);
			}
		}


		public static function is_allowed_type($type)
		{
			return in_array($type, self::$types);
		}


		public static function is_allowed_kind($kind)
		{
			return in_array($kinds, self::$kinds);
		}


		public static function get_default_type()
		{
			return self::$types[0];
		}


		public static function get_default_kind()
		{
			return self::$kinds[0];
		}
		
		
		public function is_value_content()
		{
			return in_array($this->kind, self::$kinds_content_value);
		}
		
		
		public function has_label()
		{
			return !in_array($this->kind, self::$kinds_no_label);
		}
	}
}