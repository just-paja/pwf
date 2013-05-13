<?

namespace System\Http
{
	class Response extends \System\Model\Attr
	{
		const NO_RESPONSE           = 0;
		const OK                    = 200;
		const NO_CONTENT            = 204;
		const MOVED_PERMANENTLY     = 301;
		const FOUND                 = 302;
		const SEE_OTHER             = 303;
		const TEMPORARY_REDIRECT    = 307;
		const FORBIDDEN             = 403;
		const PAGE_NOT_FOUND        = 404;
		const INTERNAL_SERVER_ERROR = 500;


		private static $states = array(
			self::OK                    => "HTTP/1.1 200 OK",
			self::NO_CONTENT            => "HTTP/1.1 204 No Content",
			self::MOVED_PERMANENTLY     => "HTTP/1.1 301 Moved Permanently",
			self::FOUND                 => "HTTP/1.1 302 Found",
			self::SEE_OTHER             => "HTTP/1.1 303 See Other",
			self::TEMPORARY_REDIRECT    => "HTTP/1.1 307 Temporary Redirect",
			self::FORBIDDEN             => "HTTP/1.1 403 Forbidden",
			self::PAGE_NOT_FOUND        => "HTTP/1.1 404 Page Not Found",
			self::INTERNAL_SERVER_ERROR => "HTTP/1.1 500 Internal Server Error",
		);

		protected static $attrs = array(
			"format"     => array('varchar'),
			"lang"       => array('varchar'),
			"title"      => array('varchar'),
			"layout"     => array('array'),
			"no_debug"   => array('bool'),
			"start_time" => array('float'),
		);

		private $page;
		private $templates = array();
		private $layout    = array();
		private $request;
		private $renderer;
		private $flow;
		private $status = self::OK;
		private $headers = array();
		private $content = null;


		public static function get_status($num)
		{
			if (isset(self::$states[$num])) {
				return self::$states[$num];
			} else throw new \System\Error\Argument(sprintf('Requested http header "%s" does not exist.', $num));
		}


		public function redirect($url, $code = self::FOUND)
		{
			if (!$this->request->cli) {
				session_write_close();

				header(self::get_status($code));
				header("Location: ".$url);
			} else throw new \System\Error\Format(stprintf('Cannot redirect to "%s" while on console.', $r['url']));

			exit(0);
		}


		/** Create response from request
		 * @param \System\Http\Request $request
		 * @return self
		 */
		public static function from_request(\System\Http\Request $request)
		{
			$response = new self(array(
				"format"     => cfg("output", 'format_default'),
				"lang"       => \System\Locales::get_lang(),
				"start_time" => microtime(true),
			));

			$response->request = $request;
			return $response;
		}


		/** Create response from page and request
		 * @param \System\Http\Request $requset
		 * @param \System\Page         $page
		 * @return self
		 */
		public static function from_page(\System\Http\Request $request, \System\Page $page)
		{
			foreach ($page->get_meta() as $meta) {
				content_for("meta", $meta);
			}

			$response = self::from_request($request);
			$response->update_attrs($page->get_data());
			$response->page = $page;
			$response->title = $page->title;
			$response->layout = $page->layout;
			$response->flow = new \System\Http\Response\Flow($response, $page->modules);

			if ($request->cli) {
				$response->format = 'txt';
			}

			return $response;
		}


		/** Execute modules
		 * @return $this
		 */
		public function exec()
		{
			$this->flow->exec();
			return $this;
		}


		/** Render response content
		 * @return $this
		 */
		public function render()
		{
			$this->renderer = $this->renderer()->render();
			return $this;
		}


		/** Send HTTP headers
		 * @return void
		 */
		public function send_headers()
		{
			if (!\System\Status::on_cli()) {
				$mime = \System\Output::get_mime($this->format);

				if ($this->status == self::OK && empty($this->content)) {
					$this->status(self::NO_CONTENT);
				}

				header(self::get_status($this->status));

				foreach ($this->headers as $name => $content) {
					if (is_numeric($name)) {
						header($content);
					} else {
						header(ucfirst($name).": ".$content);
					}
				}

				header("Content-Type: ".$mime.";charset=utf-8");
				header("Content-Encoding: gz");
			}

			return $this;
		}


		/** Send response content to output
		 * @return void
		 */
		public function display()
		{
			echo $this->content;
		}


		/** Get renderer object
		 * @return \System\Http\Response\Renderer
		 */
		public function renderer()
		{
			if (!$this->renderer) {
				$this->renderer = \System\Http\Response\Renderer::from_response($this);
			}

			return $this->renderer;
		}


		/** Add template into queue
		 * @param string $template
		 * @param string $slot
		 * @return void
		 */
		public function partial($template, array $locals = array(), $slot = \System\Template::DEFAULT_SLOT)
		{
			if (!isset($this->templates[$slot])) {
				$this->templates[$slot] = array();
			}

			$this->templates[$slot][] = array(
				"name"   => $template,
				"locals" => $locals,
			);
		}


		/** Clear response content
		 * @return $this
		 */
		public function flush()
		{
			$this->content['output'] = array();
			return $this;
		}


		/** Get render data
		 * @return array
		 */
		public function get_render_data()
		{
			return array(
				"templates" => $this->templates,
				"layout"    => $this->layout,
			);
		}


		/** Get title
		 * @return string|array
		 */
		public function get_title()
		{
			return $this->title;
		}


		/** Get flow object
		 * @return \System\Http\Response\Flow
		 */
		public function flow()
		{
			return $this->flow;
		}


		/** Get request object
		 * @return \System\Http\Request
		 */
		public function request()
		{
			return $this->request;
		}


		/** Get full path including query string
		 * @return string
		 */
		public function path()
		{
			return $this->request()->path.($this->request()->query ? '?'.$this->request()->query:'');
		}


		/** Get execution time of rendering. Not returning definite value, since the response will be sent after that.
		 * @return float
		 */
		public function get_exec_time()
		{
			return microtime(true) - $this->start_time;
		}


		public function status($status)
		{
			if (isset(self::$states[$status])) {
				$this->status = $status;
			} else throw new \System\Error\Argument(sprintf("HTTP status '%s' was not found.", $status));
		}


		public function set_content($content)
		{
			if (is_string($content)) {
				$this->content = $content;
				return $this;
			} else throw new \System\Error\Argument(sprintf("HTTP Response must be string! '%s' given.", gettype($content)));
		}
	}
}
