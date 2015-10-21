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

require_once 'settings.php';

$username = array_shift($requestParts);

if (!validate_username($username)) {
	report_problem(WEAVE_ERROR_INVALID_USERNAME, 400);
}

try {
	$db = new WeaveStorage($username);

	// Auth the user
	verify_user($username, $db);

	// user passes preliminaries, connections made, onto actually getting the data
	$function = array_shift($requestParts);
	switch ($function) {
		case 'info': {
			if ($requestMethod !== 'GET') {
				report_problem(WEAVE_ERROR_INVALID_PROTOCOL, 400);
			}

			$about = array_shift($requestParts);
			switch ($about) {
				case 'quota':
					exit(json_encode(array($db->get_storage_total())));
				case 'collections':
					exit(json_encode($db->get_collection_list_with_timestamps()));
				case 'collection_counts':
					exit(json_encode($db->get_collection_list_with_counts()));
				case 'collection_usage':
					$results = $db->get_collection_storage_totals();
					foreach (array_keys($results) as $collection) {
						$results[$collection] = ceil($results[$collection] / 1024); //converting to k from bytes
					}
					exit(json_encode($results));
				default:
					report_problem(WEAVE_ERROR_INVALID_PROTOCOL, 400);
			}
			break;
		}
		case 'storage': {
			$collection = array_shift($requestParts);

			if (is_null($collection)) {
				if ($requestMethod !== 'DELETE') {
					report_problem(WEAVE_ERROR_INVALID_PROTOCOL, 400);
				}

				// Deletes all records for the user.
				if (!array_key_exists('HTTP_X_CONFIRM_DELETE', $_SERVER)) {
					report_problem(WEAVE_ERROR_NO_OVERWRITE, 412);
				}
				$db->delete_storage($username);
				exit(json_encode($server_time));
			}

			if (!validate_collection($collection)) {
				report_problem(WEAVE_ERROR_INVALID_COLLECTION, 400);
			}

			$id = array_shift($requestParts);

			if (is_null($id)) {
				switch ($requestMethod) {
					case 'GET': {
						// retrieve a batch of records. Sadly, due to potential record sizes, have the storage object stream the output...
						log_error('retrieve a batch');
						$full = array_key_exists('full', $_GET) && $_GET['full'];

						$outputter = new WBOJsonOutput($full);

						$params = validate_search_params();

						$ids = $db->retrieve_objects($collection, null, $full, $outputter,
							$params['parentid'], $params['predecessorid'],
							$params['newer'], $params['older'],
							$params['sort'],
							$params['limit'], $params['offset'],
							$params['ids'],
							$params['index_above'], $params['index_below'], $params['depth']
						);
						exit();
					}
					case 'POST': {
						$json = get_json();

						check_quota($db);
						check_timestamp($collection, $db);

						$success_ids = array();
						$failed_ids = array();

						$db->begin_transaction();

						foreach ($json as $wbo_data) {
							$wbo = new wbo();

							if (!$wbo->extract_json($wbo_data)) {
								$failed_ids[$wbo->id()] = $wbo->get_error();
								continue;
							}

							$wbo->collection($collection);
							$wbo->modified($server_time);


							if ($wbo->validate()) {
								// if there's no payload (as opposed to blank), then update the metadata
								if ($wbo->payload_exists()) {
									$db->store_object($wbo);
								} else {
									$db->update_object($wbo);
								}
								$success_ids[] = $wbo->id();
							} else {
								$failed_ids[$wbo->id()] = $wbo->get_error();
							}
						}
						$db->commit_transaction();

						exit(json_encode(array('modified' => $server_time, 'success' => $success_ids, 'failed' => $failed_ids)));
					}
					case 'DELETE': {
						$params = validate_search_params();

						$db->delete_objects($collection, null,
							$params['parentid'], $params['predecessorid'],
							$params['newer'], $params['older'],
							$params['sort'],
							$params['limit'], $params['offset'],
							$params['ids'],
							$params['index_above'], $params['index_below']
						);

						exit(json_encode($server_time));
					}

					default:
						report_problem(WEAVE_ERROR_INVALID_PROTOCOL, 400);
				}
			} else {
				switch ($requestMethod) {
					case 'GET': {
						//get the full contents of one record
						$wbo = $db->retrieve_objects($collection, $id, 1);
						if (count($wbo) > 0) {
							$item = array_shift($wbo);
							exit($item->json());
						} else {
							report_problem('record not found', 404);
						}
					}
					case 'PUT': {
						$wbo = new wbo();
						if (!$wbo->extract_json(get_json())) {
							report_problem(WEAVE_ERROR_JSON_PARSE, 400);
						}

						check_quota($db);
						check_timestamp($collection, $db);

						// use the url if the json object doesn't have an id
						if (!$wbo->id() && $id) {
							$wbo->id($id);
						}

						$wbo->collection($collection);
						$wbo->modified($server_time); // current microtime

						if ($wbo->validate()) {
							// if there's no payload (as opposed to blank), then update the metadata
							if ($wbo->payload_exists()) {
								$db->store_object($wbo);
							} else {
								$db->update_object($wbo);
							}
						} else {
							report_problem(WEAVE_ERROR_INVALID_WBO, 400);
						}
						exit(json_encode($server_time));
					}
					case 'DELETE': {
						$db->delete_object($collection, $id);

						exit(json_encode($server_time));
					}

					default:
						report_problem(WEAVE_ERROR_INVALID_PROTOCOL, 400);
				}
			}
		}
		default:
			report_problem(WEAVE_ERROR_FUNCTION_NOT_SUPPORTED, 400);
	}
} catch (Exception $e) {
	report_problem($e->getMessage(), $e->getCode());
}