<?php

# ***** BEGIN LICENSE BLOCK *****
# Version: MPL 1.1/GPL 2.0/LGPL 2.1
#
# The contents of this file are subject to the Mozilla Public License Version
# 1.1 (the 'License'); you may not use this file except in compliance with
# the License. You may obtain a copy of the License at
# http://www.mozilla.org/MPL/
#
# Software distributed under the License is distributed on an 'AS IS' basis,
# WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
# for the specific language governing rights and limitations under the
# License.
#
# The Initial Developer of the Original Code is balu
#
# Portions created by the Initial Developer are Copyright (C) 2012
# the Initial Developer. All Rights Reserved.
#
# Contributor(s):
#	Martok
#
# Alternatively, the contents of this file may be used under the terms of
# either the GNU General Public License Version 2 or later (the 'GPL'), or
# the GNU Lesser General Public License Version 2.1 or later (the 'LGPL'),
# in which case the provisions of the GPL or the LGPL are applicable instead
# of those above. If you wish to allow use of your version of this file only
# under the terms of either the GPL or the LGPL, and not to allow others to
# use your version of this file under the terms of the MPL, indicate your
# decision by deleting the provisions above and replace them with the notice
# and other provisions required by the GPL or the LGPL. If you do not delete
# the provisions above, a recipient may use your version of this file under
# the terms of any one of the MPL, the GPL or the LGPL.
#
# ***** END LICENSE BLOCK *****

require_once 'weave_utils.php';
if (!defined('INDEX_INCLUDE')) //file should only be used in context of index.php
{
	log_error('include error');
	report_problem('Function not found', 404);
}

$version = array_shift($requestParts);

switch ($version) {
	case '1.0': {
		$username = array_shift($requestParts);

		if (!validate_username($username)) {
			log_error('invalid user');
			report_problem(WEAVE_ERROR_INVALID_USERNAME, 400);
		}

		$db = new WeaveStorage($username);
		// user passes preliminaries, connections made, onto actually getting the data

		$function = array_shift($requestParts);
		if (is_null($function)) {
			switch ($requestMethod) {
				case 'GET': {
					// username exists?
					if (exists_user($db)) {
						exit(json_encode(1));
					} else {
						exit(json_encode(0));
					}
				}
				case 'PUT': {
					if (ENABLE_REGISTER) {
						$db = new WeaveStorage(null);
						// Requests that an account be created for username.
						/*
						The JSON payload should include
						Field   Description
						password    The password to be associated with the account.
						email   Email address associated with the account
						captcha-challenge   The challenge string from the captcha (see miscellaneous functions below)
						captcha-response    The response to the captcha. Only required if WEAVE_REGISTER_USE_CAPTCHA is set
						*/
						log_error('PUT');
						$data = get_json();
						log_error(print_r($data, true));
						//werte vorhanden
						if ($data == NULL) {
							report_problem(WEAVE_ERROR_JSON_PARSE, 400);
						}
						$name = $username;
						$pwd = fix_utf8_encoding($data['password']);
						$email = $data['email'];
						if ($email == '') {
							log_error('create user dataerror');
							report_problem(WEAVE_ERROR_NO_EMAIL, 400);
						} else if ($pwd == '') {
							log_error('create user dataerror');
							report_problem(WEAVE_ERROR_MISSING_PASSWORD, 400);
						}
						if ($name == '' || $pwd == '' || $email == '') {
							log_error('create user dataerror');
							report_problem(WEAVE_ERROR_JSON_PARSE, 400);
						}
						log_error('create user ' . $name . ' pw : ' . $pwd);

						try {
							if ($db->create_user($name, $pwd)) {
								log_error('successfully created user');
								exit(json_encode(strtolower($name)));
							} else {
								log_error('create user failed');
								report_problem(WEAVE_ERROR_NO_OVERWRITE, 503);
							}
						} catch (Exception $e) {
							log_error('db exception create user');
							header('X-Weave-Backoff: 1800');
							report_problem($e->getMessage(), $e->getCode());
						}
					} else {
						log_error('register not enabled');
						report_problem(WEAVE_ERROR_FUNCTION_NOT_SUPPORTED, 400);
					}

				}
				case 'DELETE': {
					//remove account and data from sync server

					// TODO: Fix & Test, this can't work in upstream's version

					// 1. verify user auth, needs $auth_pw passed to function.
					if ($auth_pw == '') {
						log_error("userapi : delete account, no auth password given");
						report_problem(WEAVE_ERROR_MISSING_PASSWORD, 400);
					}
					try {
						$existingHash = $db->get_password_hash(); //passes $username internally
						$hash = WeaveHashFactory::factory();

						if (!$hash->verify(fix_utf8_encoding($auth_pw), $existingHash)) {
							log_error('Auth failed 2 {');
							log_error(' User pw: ' . $auth_user . '|' . $auth_pw . '|md5:' . md5($auth_pw) . '|fix:' . fix_utf8_encoding($auth_pw) . '|fix md5 ' . md5(fix_utf8_encoding($auth_pw)));
							log_error(' Url_user: ' . $url_user);
							log_error(' Existing hash: ' . $existingHash);
							log_error('}');
							report_problem('Authentication failed', '401');
						} else {
							// 2. get collections + data and remove data
							// 3. remove user account
							// TODO: Implement
							// $db->delete_user($username);
						}
					} catch (Exception $e) {
						header("X-Weave-Backoff: 1800");
						log_error($e->getCode() . $e->getMessage());
						report_problem($e->getMessage(), $e->getCode());
					}
				}
			}
		} else switch ($function) {
			case 'node': {
				if ($requestMethod !== 'GET') {
					report_problem(WEAVE_ERROR_INVALID_PROTOCOL, 400);
				}
				$collection = array_shift($requestParts);
				if ($collection == 'weave') {
					// modification to support iPhone/iPod Touch devices
					// check http://www.rfkd.de/?p=974 for further details
					$node = get_https() ? 'https://' : 'http://';
					$node.= parse_url(FSYNCMS_URL, PHP_URL_HOST) . parse_url(FSYNCMS_URL, PHP_URL_PATH);
					header('Content-Type: text/plain', true);
					exit($node);
				}
				break;
			}
			case 'password_reset': {
				if ($requestMethod !== 'GET') {
					report_problem(WEAVE_ERROR_INVALID_PROTOCOL, 400);
				}

				// TODO: Implement
				report_problem(WEAVE_ERROR_NO_EMAIL, 400);
			}
			case 'password': {
				if ($requestMethod !== 'POST') {
					report_problem(WEAVE_ERROR_INVALID_PROTOCOL, 400);
				}

				// auth the user
				verify_user($username, $db);
				$new_pwd = get_phpinput();
				log_error('userapi: POST password ');

				// change pw in db
				$hash = WeaveHashFactory::factory();
				if ($db->change_password($hash->hash($new_pwd))) {
					exit('success');
				} else {
					report_problem(WEAVE_ERROR_INVALID_PROTOCOL, 503); //server db messed up somehow
					// return success
					// report_problem(7, 400);
				}
			}
			case 'email': {
				if ($requestMethod !== 'POST') {
					report_problem(WEAVE_ERROR_INVALID_PROTOCOL, 400);
				}

				// TODO: Implement
				report_problem(WEAVE_ERROR_NO_EMAIL, 400);
			}
			default:
				report_problem(WEAVE_ERROR_INVALID_PROTOCOL, 400);
		}
		break;
	}
	default:
		report_problem('Function not found', 404);
}
