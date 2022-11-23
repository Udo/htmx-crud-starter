<?php

	class User
	{

		static $last_error = false;
		static $data = array();

		static function init()
		{
			if($_SESSION['uid'])
				self::$data = self::loadByUID($_SESSION['uid']);
			Profiler::log('User::init()');
		}

		static function is_logged_in()
		{
			return($_SESSION['uid']);
		}

		static function require_login()
		{
			if(!self::is_logged_in())
			{
				URL::redirect('account/login', array('whence' => URL::$route['l-path']));
			}
		}

		static function logout()
		{
			unset($_SESSION['uid']);
			self::$data = array();
		}

		static function try_login($nick, $password)
		{
			$uid = NV::make_hash($nick);
			$data = self::loadByUID($uid);
			if(sizeof($data) == 0)
			{
				self::$last_error = 'Account not found';
				return;
			}
			if(!password_verify($password, $data['password']))
			{
				self::$last_error = 'Credentials invalid';
				return;
			}
			if($data['banned'])
			{
				self::$last_error = 'This account is banned';
				return;
			}
			session_start();
			$_SESSION['uid'] = $data['uid'];
			self::$data = $data;
			return(true);
		}

		static function save()
		{
			if(sizeof(self::$data) == 0) return;
			NV::write_data('accounts', self::$data['uid'], self::$data);
		}

		static function loadByUID($uid)
		{
			$data = NV::read_data('accounts', $uid);
			if(!$data) $data = array();
			return($data);
		}

		static function try_create_account(&$nick, $password1, $password2)
		{
			if($password1 != $password2)
			{
				self::$last_error = 'The passwords you entered do not match';
				return;
			}
			$uid = NV::make_hash($nick);
			$existing_account = self::loadByUID($uid);
			if(sizeof($existing_account) != 0)
			{
				self::$last_error = 'This account already exists';
				return;
			}
			self::$data = array(
				'nick' => $nick,
				'uid' => $uid,
				'password' => password_hash($password1, PASSWORD_DEFAULT),
				'created_on' => time(),
				'created_info' => get_browser_info(),
			);
			self::save();
			return(self::$data);
		}

		static function try_change_password($password1, $password2)
		{
			if(!self::is_logged_in()) return;
			if($password1 != $password2)
			{
				self::$last_error = 'The passwords you entered do not match';
				return;
			}
			self::$data['previous_password'] = self::$data['password'];
			self::$data['password'] = password_hash($password1, PASSWORD_DEFAULT);
			self::$data['password_changed_on'] = time();
			self::save();
			return(true);
		}

	}
