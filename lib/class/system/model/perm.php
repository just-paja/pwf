<?

/** Database data model handling */
namespace System\Model
{
	abstract class Perm extends Filter
	{
		const VIEW_SCHEMA = 'schema';
		const CREATE      = 'create';
		const BROWSE      = 'browse';
		const UPDATE      = 'update';
		const DROP        = 'drop';
		const VIEW        = 'view';


		/** Get default config for this action
		 * @param string $method One of permission method constants
		 * @return bool
		 */
		public static function get_default_for($method)
		{
			try {
				$res = cfg('api', 'allow', $method);
			} catch (\System\Error\Config $e) {
				throw new \System\Error\Argument('Unknown permission method type.', $method);
			}

			return !!$res;
		}


		/** Ask if user has right to do this
		 * @param string      $method One of created, browsed
		 * @param System\User $user   User to get perms for
		 * @return bool
		 */
		public static function can_user($method, \System\User $user)
		{
			if ($user->is_root()) {
				return true;
			}

			$cname = get_called_class();
			$conds = array();

			if (isset($cname::$access) && isset($cname::$access[$method]) && !!$cname::$access[$method]) {
				return true;
			}

			if ($user->is_guest()) {
				$conds['public'] = true;
			} else {
				$groups = $user->groups->fetch();

				if (any($groups)) {
					$conds[] = 'group_id IN ('.collect_ids($groups).')';
				}
			}

			$conds['type']    = $cname;
			$conds['trigger'] = 'model';

			$perm = get_first('System\User\Perm')->where($conds)->fetch();
			return $perm ? $perm->allow:self::get_default_for($method);
		}


		/** Ask if user has right to do this
		 * @param string      $method One of viewed, updated, dropped
		 * @param System\User $user   User to get perms for
		 * @return bool
		 */
		public function can_be($method, \System\User $user)
		{
			return self::can_user($method, $user);
		}


		public function to_object_with_perms(\System\User $user)
		{
			return array_merge($this->to_object_with_id_and_perms($user), $this->get_rels_to_object_with_perms($user));
		}


		public function to_object_with_id_and_perms(\System\User $user)
		{
			$data  = parent::to_object_with_id();
			$model = get_class($this);
			$attrs = \System\Model\Database::get_model_attr_list($model, false, true);

			foreach ($attrs as $attr_name) {
				if (self::is_rel($model, $attr_name)) {
					$def = self::get_attr($model, $attr_name);
					$rel_cname = $def['model'];
					$is_subclass = is_subclass_of($rel_cname, '\System\Model\Perm');
					$is_allowed  = $is_subclass && $rel_cname::can_user(self::BROWSE, $user);

					if (!$is_allowed) {
						unset($data[$attr_name]);

						if ($def[0] == self::REL_BELONGS_TO) {
							unset($data[self::get_belongs_to_id($model, $attr_name)]);
						}
					}
				}
			}

			return $data;
		}


		public function get_rels_to_object_with_perms(\System\User $user)
		{
			$data  = array();
			$model = get_class($this);
			$attrs = \System\Model\Database::get_model_attr_list($model, false, true);

			foreach ($attrs as $attr_name) {
				if (self::is_rel($model, $attr_name)) {
					$def = self::get_attr($model, $attr_name);
					$rel_cname = $def['model'];
					$is_subclass = is_subclass_of($rel_cname, '\System\Model\Perm');
					$is_allowed  = $is_subclass && $rel_cname::can_user(self::BROWSE, $user);

					if ($is_allowed) {
						if ($def[0] == self::REL_HAS_MANY) {
							$data[$attr_name] = $this->get_rel_has_many_ids($attr_name);
						} else if ($def[0] == self::REL_BELONGS_TO) {
							$bid = self::get_belongs_to_id($model, $attr_name);

							if ($this->$bid) {
								$data[$attr_name] = $this->$bid;
							}
						}
					}
				}
			}

			return $data;
		}
	}
}
