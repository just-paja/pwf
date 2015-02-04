<?

namespace System
{
	abstract class Status
	{
		const DIR_LOGS = '/var/log';

		private static $log_files = array();


		/** Write error into log file
		 * @param string $type
		 * @param string $msg
		 */
		public static function report($type, $msg)
		{
			try {
				$debug = \System\Settings::get('dev', 'debug', 'backend');
			} catch(\System\Error\Config $e) {
				$debug = false;
			}

			while(ob_get_level() > 0) {
				ob_end_clean();
			}

			if (!isset(self::$log_files[$type]) || !is_resource(self::$log_files[$type])) {
				try {
					\System\Directory::check(BASE_DIR.self::DIR_LOGS);
					self::$log_files[$type] = @fopen(BASE_DIR.self::DIR_LOGS.'/'.$type.'.log', 'a+');
				} catch(\System\Error $e) {
					self::error($e, false);
				}
			}

			if (is_resource(self::$log_files[$type])) {
				try {
					$report = @date('[Y-m-d H:i:s]');
				} catch(\Exception $e) {
					$report = time();
				}

				!self::on_cli() && $report .= ' '.$_SERVER['SERVER_NAME'].NL;
				self::append_msg_info($msg, $report);

				if (self::on_cli()) {
					$report .= "> Run from console".NL;
				} else {
					$report .= "> Request: ".$_SERVER['REQUEST_METHOD'].' '.$_SERVER['SERVER_PROTOCOL'].' '.$_SERVER['REQUEST_URI']."'".NL;
				}

				$report .= NL;

				if (!$debug && $type == 'error') {
					try {
						$rcpt = \System\Settings::get('dev', 'mailing', 'errors');
					} catch(\System\Error\Config $e) {
						$rcpt = array();
					}

					\System\Offcom\Mail::create('[Fudjan] Server error', $report, $rcpt)->send();
				}

				fwrite(self::$log_files[$type], $report);
			}
		}


		/** Add message info to report
		 * @param mixed  $msg
		 * @param string $report
		 */
		private static function append_msg_info($msg, &$report)
		{
			foreach ((array) $msg as $line) {
				if ($line) {
					if (is_array($line)) {
						if (isset($line[0])) {
							self::append_msg_info($line, $report);
						} else {
							$data = @json_encode($line);

							if (json_last_error()) {
								$data .= "Can't encode data!";
							}

							$report .= "> ".$data.NL;
						}
					} else {
						$report .= "> ".$line.NL;
					}
				}
			}
		}


		/** General exception handler - Catches exception and displays error
		 * @param \Exception $e
		 * @param bool $ignore_next Don't inwoke another call of catch_exception from within
		 */
		public static function catch_exception(\Exception $e, $ignore_next = false)
		{
			// Kill output buffer
			while (ob_get_level() > 0) {
				ob_end_clean();
			}

			// Get error display definition
			try {
				$errors = \System\Settings::get('output', 'errors');
				$cfg_ok = true;
			} catch(\System\Error\Config $exc) {
				$errors = array();
				$cfg_ok = false;
			}

			// See if debug is on
			try {
				$debug = \System\Settings::get('dev', 'debug', 'backend');
			} catch(\System\Error\Config $exc) {
				$debug = true;
			}

			// Convert to standart class error if necessary
			if (!($e instanceof \System\Error)) {
				$e = \System\Error::from_exception($e);
			}

			// Try saving error into logfile
			try {
				self::report('error', $e);
			} catch (\Exception $err) {
				$e = $err;
			}

			if ($e instanceof \System\Error\Request && $e::REDIRECTABLE && $e->location) {
				header('Location: '. $e->location);
				exit(0);
			} else {
				// Find error display template
				if (array_key_exists($e->get_name(), $errors)) {
					$error_page = $errors[$e->get_name()];
				} else {
					$error_page = array(
						"title"    => 'Error occurred!',
						"layout"   => array('system/layout/error'),
						"partial"  => 'system/error/bug',
					);
				}

				// Setup output format for error page
				$error_page['format'] = 'html';
				$error_page['render_with'] = 'basic';

				try {
					$request = \System\Http\Request::from_hit();
					$response = $request->create_response($error_page);
					$response->renderer()->format = 'html';

					if (self::on_cli()) {
						$response->renderer()->format = 'txt';
					} else {
						$response->status($e->get_http_status());
					}

					if (!isset($error_page['partial'])) {
						$error_page['partial'] = array('system/error/bug');
					}

					if (!is_array($error_page['partial'])) {
						$error_page['partial'] = array($error_page['partial']);
					}

					if ($debug && !in_array('system/error/bug', $error_page['partial'])) {
						$error_page['partial'][] = 'system/error/bug';
					}

					foreach ($error_page['partial'] as $partial) {
						$response->renderer()->partial($partial, array("desc" => $e));
					}

					$response->render()->send_headers()->send_content();

				} catch (\Exception $exc) {
					echo "Fatal error when rendering exception details";
					v($exc);
					exit(1);
				}
			}

			exit(1);
		}


		public static function catch_error($number, $string, $file = null, $line = null, $context = array())
		{
			if (error_reporting()) {
				self::catch_exception(new \System\Error\Code($string.' in "'.$file.':'.$line.'"'));
			}
		}


		public static function catch_fatal_error()
		{
			$err = error_get_last();

			if (!is_null($err)) {
				if (any($err['message'])) {
					if (strpos($err['message'], 'var_export does not handle circular') !== false) {
						return;
					}
				}

				self::catch_error(def($err['number']), def($err['message']), def($err['file']), def($err['line']));
			}
		}


		public static function on_cli()
		{
			return php_sapi_name() == 'cli';
		}


		/** Introduce pwf name and version
		 * @return string
		 */
		public static function introduce()
		{
			return 'fudjan';
		}


		public static function init()
		{
			set_exception_handler(array("System\Status", "catch_exception"));
			set_error_handler(array("System\Status", "catch_error"));
			register_shutdown_function(array("System\Status", "catch_fatal_error"));

			ini_set('log_errors',     true);
			ini_set('display_errors', false);
			ini_set('html_errors',    false);
		}
	}
}
