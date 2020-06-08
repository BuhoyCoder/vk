<?php

namespace BuhoyCoder\VK;

class Callback
{
	private $ctx;
	
	private $data = [];
	
	private $debug_mode;
	
	public function __construct(string $api_token, string $confirm_str = '', bool $debug_mode = false)
	{
		$this->debug_mode = $debug_mode;
		$this->data = json_decode(file_get_contents('php://input'), true);

		if (!isset($this->data)) {
			exit('No callback request.');
		}

		if ($this->data['type'] === EventType::CONFIRMATION) {
			exit($confirm_str);
		}
		
		$this->closeRequest();
		
		$this->ctx = new Context(new VkApi($api_token), $this->data);
	}
	
	private function closeRequest()
	{
		set_time_limit(0);

		if ($this->debug_mode) {
			error_reporting(E_ALL);
			ini_set('display_errors', 'on');
			ini_set('display_startup_errors', 'on');
			echo 'ok';
		} else {
			ini_set('display_errors', 'off');

			// для Nginx
			if (is_callable('fastcgi_finish_request')) {
				echo 'ok';
				session_write_close();
				fastcgi_finish_request();
			} else {
				// для Apache
				ignore_user_abort(true);

				ob_start();
				header('Content-Encoding: none');
				header('Content-Length: 2');
				header('Connection: close');
				echo 'ok';
				ob_end_flush();
				flush();
			}
		}
	}
	
	public function on(string $name, callable $handler)
	{
		if ($this->data['type'] === $name) {
			$handler($this->ctx);
		}
	}
}
