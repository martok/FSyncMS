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
# The Original Code is Weave Minimal Server
#
# The Initial Developer of the Original Code is
# Mozilla Labs.
# Portions created by the Initial Developer are Copyright (C) 2008
# the Initial Developer. All Rights Reserved.
#
# Contributor(s):
#	Toby Elliott (telliott@mozilla.com)
#	Luca Tettamanti
#	Martin-Jan Sklorz (m.skl@lemsgbr.de)
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

require_once 'site_utils.php';

if (!file_exists(FSYNCMS_CONFIG) && file_exists('setup.php')) {
	require_once 'setup.php';
	exit;

} else if (!file_exists(FSYNCMS_CONFIG)) {
	echo '<hr><h2>Maybe the setup is not completed, missing settings.php!</h2><hr>';
	exit;

} else if (file_exists('setup.php')) {
	echo '<hr><h2>Maybe the setup is not completed, else please delete setup.php!</h2><hr>';
	exit;
}

require_once FSYNCMS_CONFIG;

require_once 'weave_storage.php';
require_once 'weave_basic_object.php';
require_once 'weave_utils.php';
require_once 'weave_hash.php';

require_once 'WBOJsonOutput.php';

$server_time = round(microtime(1), 2);
header('X-Weave-Timestamp: ' . $server_time);

$path = get_path();

log_error(
	get_remote_ip() . ' ' .
	(is_null(get_http_auth()) ? '-':'Authenticated') . "\t" .
	$_SERVER['REQUEST_METHOD'] . ' '.
	'/' . $path
);

// ensure that we got a valid request
if (!$path) {
	report_problem('Invalid request, this was not a firefox sync request!', 400);
}

header('Content-type: application/json');
define('INDEX_INCLUDE', true);

$requestParts = explode('/', $path);
$requestMethod = $_SERVER['REQUEST_METHOD'];

$api = array_shift($requestParts);

switch ($api) {
	case 'user':
		require 'api_user.php';
		break;
	case 'misc':
		require 'api_misc.php';
		break;
	case '1.0':
	case '1.1':
		require 'api_weave.php';
		break;
	default:
		report_problem('Function not found', 404);
}
?>
