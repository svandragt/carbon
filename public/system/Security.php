<?php

class Security {

	function login_redirect() {
		if (!$this->is_loggedin()) {
			$url = new Url();
			header('Location: ' . $url->index('/admin')->abs()->url);
		};
	}

	function is_loggedin() {
		return !is_null(Http::session('admin'));
	}

	function login() {
		Log::info(sprintf("Login attempt from %s", $_SERVER['REMOTE_ADDR']));

		if (is_null(Configuration::ADMIN_PASSWORD)) {
			echo "Please set an admin password under Configuration::ADMIN_PASSWORD.<br>";
		} else {
			$password = HTTP::post('password');
			if (is_null($password)) {
				echo "<form method='post'><input type='password' name='password'><input type='submit'></form>";
			} elseif ($password == Configuration::ADMIN_PASSWORD) {
				Http::set_session(array(
					'admin' => true,
				));	
				echo "logged in.<br>";
				Log::info("Login attempt successful");
			}
			else {
				Log::warn("Login attempt unsuccessful.");
			}
		}
	}

	function logout() {
		session_destroy();
		echo "logged out.<br>";
	}
}