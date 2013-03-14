<?

namespace System\Offcom
{
	abstract class Request
	{
		/** Make a get request to URL
		 * @param string $url Requested URL
		 * @return array
		 */
		public static function get($url)
		{
			if (function_exists('curl_init')) {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_USERAGENT, \System\Output::introduce());
				curl_setopt($ch, CURLOPT_HEADER, 1);
				$content = curl_exec($ch);
				$content = explode("\r\n\r\n", $content, 2);

				$dataray = array(
					"headers" => null,
					"content" => null,
					"status"  => null,
				);

				$dataray['status'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				curl_close($ch);

				if ($dataray['status'] === 403) throw new \System\Error\Offcom(sprintf('Access to URL "%s" was denied', $url]));
				if ($dataray['status'] === 404) throw new \System\Error\Offcom(sprintf('Requested URL "%s" was not found', $url]));

				isset($content[0]) && $dataray['headers'] = $content[0];
				isset($content[1]) && $dataray['content'] = $content[1];

				return new Response($dataray);

			} else throw new \System\Error\Internal('Please allow CURL extension for System\Offcom\Request class');
		}


		static function json($url) {
			return json_decode(self::get($url), true);
		}
	}
}
