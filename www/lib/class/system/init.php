<?

namespace System
{
	class Init
	{
		public static function full()
		{
			self::bind_error_handlers();
			Flow::init();
			Settings::init();
			Locales::init();
			Cache::init();
			Database::init();
			Output::init();
		}

		
		public static function basic()
		{
			self::bind_error_handlers();
			Input::init();
			Flow::init();
			Settings::init();
			Locales::init();
		}


		public static function session()
		{
			session_start();
		}


		public static function cli()
		{
			global $argv;
			$last = end($argv);
			$_SERVER['REQUEST_URI'] = $last == 'index.php' ? '/':$last;

			php_sapi_name() != 'cli' && give_up("This program can be run only via PHP CLI !!");

			!class_exists("CLIOptions")  && give_up("Missing class 'CLIOptions' !!");
			!class_exists("CLICommands") && give_up("Missing class 'CLICommands'!!");

			require_once ROOT."/lib/include/constants.cli.php";
			require_once ROOT."/lib/include/functions.cli.php";

			\CLIOptions::init();
			\CLIOptions::parse_options();
			define("YACMS_ENV", \CLIOptions::get_env());

			require_once ROOT."/etc/init.d/core.php";

			Output::set_format('cli');

			$cmd = \CLIOptions::get('command');
			\CLICommands::$cmd();
		}


		public static function bind_error_handlers()
		{
			set_exception_handler(array("System\Status", "catch_exception"));

			ini_set('log_errors',     true);
			ini_set('display_errors', true);
			ini_set('html_errors',    false);
		}

	}
}