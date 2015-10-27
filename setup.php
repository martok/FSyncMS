<?php

# ***** BEGIN LICENSE BLOCK *****
# Version: MPL 1.1/GPL 2.0/LGPL 2.1
# 
# The contents of this file are subject to the Mozilla Public License Version 
# 1.1 (the "License"); you may not use this file except in compliance with 
# the License. You may obtain a copy of the License at 
# http://www.mozilla.org/MPL/
# 
# Software distributed under the License is distributed on an "AS IS" basis,
# WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
# for the specific language governing rights and limitations under the
# License.
# 
# The Original Code is Weave Minimal Server
# 
# The Initial Developer of the Original Code is
#   Stefan Fischer
# Portions created by the Initial Developer are Copyright (C) 2012
# the Initial Developer. All Rights Reserved.
# 
# Contributor(s):
#   Daniel Triendl <daniel@pew.cc>
#   balu
#   Tobias Hollerung (tobias@hollerung.eu)
#   Martin-Jan Sklorz (m.skl@lemsgbr.de)
#
# Alternatively, the contents of this file may be used under the terms of
# either the GNU General Public License Version 2 or later (the "GPL"), or
# the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
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

// --------------------------------------------
// variables start
// --------------------------------------------
$action = null;
$db_type = null;

$db_user = null;
$db_name = null;
$db_pass = null;
$db_host = null;
$db_table_prefix = null;
// --------------------------------------------
// variables end
// --------------------------------------------


// --------------------------------------------
// post handling start
// --------------------------------------------
if (isset($_POST['action'])) {
	$action = check_input($_POST['action']);
}

if (isset($_POST['dbtype'])) {
	$db_type = check_input($_POST['dbtype']);
}

if (isset($_POST['dbhost'])) {
	$db_host = check_input($_POST['dbhost']);
}

if (isset($_POST['dbname'])) {
	$db_name = check_input($_POST['dbname']);
}

if (isset($_POST['dbuser'])) {
	$db_user = check_input($_POST['dbuser']);
}

if (isset($_POST['dbpass'])) {
	$db_pass = check_input($_POST['dbpass']);
}

if (isset($_POST['dbtableprefix'])) {
	$db_table_prefix = check_input($_POST['dbtableprefix']);
}
// --------------------------------------------
// post handling end
// --------------------------------------------


// --------------------------------------------
// functions start
// --------------------------------------------

/*
    ensure that the input is not total waste
*/
function check_input($data)
{
	$data = trim($data);
	$data = stripslashes($data);
	$data = htmlspecialchars($data);
	return $data;
}


/*
    create the config file with the database type
    and the given connection credentials
*/
function write_config_file($db_type, $db_host, $db_name, $db_user, $db_pass, $fsRoot, $db_table_prefix)
{
	// construct the name of config file
	$cfg_file_name = implode(DIRECTORY_SEPARATOR, array(__DIR__, 'conf', 'settings.php'));
	$def_log_file = implode(DIRECTORY_SEPARATOR, array(__DIR__, 'log', 'fsyncms.log'));

	if (file_exists($cfg_file_name) && filesize($cfg_file_name) > 0) {
		echo '<h2>The configuration file "' . $cfg_file_name . '" is already present!</h2>';
		return;
	}

	echo 'Creating configuration file...<br/>';

	// create content
	$cfg_content = <<<CFG
<?php
// you can disable registration to the firefox sync server here
// by setting ENABLE_REGISTER to false
define("ENABLE_REGISTER", true);

// enable / disable error logging
define("LOG_THE_ERROR", true);

// log file location
define("LOG_FILE_NAME", "$def_log_file");

// firefox sync server url, this should end with a /
// e.g. https://YourDomain.de/Folder_und_ggf_/index.php/
//
define("FSYNCMS_ROOT", "$fsRoot");

// database system you want to use
// e.g. MYSQL, PGSQL, SQLITE
//
define("DATABASE_ENGINE", "{strtoupper($db_type)}");

define("DATABASE_HOST", "{$db_host}");
define("DATABASE_DB", "{$db_name}");
define("DATABASE_USER", "{$db_user}");
define("DATABASE_PASSWORD", "{$db_pass}");

define("DATABASE_TABLE_PREFIX", "{$db_table_prefix}");

// Use bcrypt instead of MD5 for password hashing
define("BCRYPT", true);
define("BCRYPT_ROUNDS", 12);
?>
CFG;

	// write to disk
	// @note: catch does not seem to work
	try {
		$cfg_file = fopen($cfg_file_name, 'a');
		fputs($cfg_file, $cfg_content);
		fclose($cfg_file);
	} catch (Exception $e) {
		echo '<h2>Configuration file "' . $cfg_file_name . '" is not accessable!</h2><br/>
			<h4>Create it manually:</h4><br/><hr>
			<p>' . $cfg_content . '</p>';
	}
}


function echo_header($title)
{
	if (!isset($title)) {
		$title = '';
	}
	echo '<!DOCTYPE html>
		<html lang="en">
    		<head>
    			<title>' . $title . '</title>
    		</head>
    		
    		<body>
    			<h1>Setup FSyncMS</h1>';
}

function echo_form_header()
{
	echo '<form action="setup.php" method="post">';
}

function echo_form_footer()
{
	echo '</form>';
}

function echo_footer()
{
	echo '	</body>
    	</html>';
}

// --------------------------------------------
// functions end
// --------------------------------------------


// check if we have no configuration at the moment
if (file_exists('settings.php') && filesize('settings.php') > 0) {
	echo '<hr><h2>The setup looks completed, please finish it by deleting setup.php!</h2><hr>';
	exit;
}


// step 1 - select the database engine
if (!$action) {
	// first check if we have pdo installed (untested)
	if (!extension_loaded('PDO')) {
		echo 'ERROR - PDO is missing in the php installation!';
		exit();
	}

	echo_header('Setup FSyncMS - DB engine selection');

	echo '<p>Which database type should be used?</p><br/>';

	echo_form_header();

	$drivers = array(
		'sqlite' => 'SQLite',
		'pgsql' => 'PostgreSQL',
		'mysql' => 'MySQL'
	);

	$valid_pdo_driver = 0;
	$checkfirst = ' checked="checked"';
	foreach ($drivers as $driver => $caption) {
		if (extension_loaded('pdo_' . $driver)) {
			echo '<input type="radio" name="dbtype" value="' . $driver . '"' . $checkfirst . ' />' . $caption . '<br/>';
			$checkfirst = '';
			$valid_pdo_driver++;
		} else {
			echo $caption . ' not possible (driver missing)!<br/>';
		}
	}

	if ($valid_pdo_driver < 1) {
		echo '<hr> No valid pdo driver found! Please install a valid pdo driver first <hr>';
	} else {
		echo '<input type="hidden" name="action" value="step2">
		    <p><input type="submit" value="OK" /></p>';
		echo_form_footer();
	}

	echo_footer();
};


// step 2 - database details
if ($action == 'step2') {
	// first check if we have valid data
	if (!extension_loaded('PDO')) {
		echo 'ERROR - This type of database (' . $db_type . ') is not supported at the moment!';
		exit();
	}

	echo_header('Setup FSyncMS - DB settings: ' . $db_type);

	echo_form_header();

	echo '<table>
					<tr>
						<td>Database name</td>
						<td><input type="text" name="dbname" /></td>
					</tr>
					<tr>
						<td>Table Prefix</td>
						<td><input type="text" name="dbtableprefix" /></td>
					</tr>';

	if ($db_type != 'sqlite') {
		echo '	<tr>
						<td>Host</td>
						<td><input type="text" name="dbhost" /></td>
					</tr>
					<tr>
						<td>Username</td>
						<td><input type="text" name="dbuser" /></td>
					</tr>
					<tr>
						<td>Password</td>
						<td><input type="password" name="dbpass" /></td>
					</tr>';
	}

	echo '	</table>

				<input type="hidden" name="action" value="step3">
				<input type="hidden" name="dbtype" value="' . $db_type . '">
				<p><input type="submit" value="OK"></p>';

	echo_form_footer();

	echo_footer();
}


// step 3 - create the database
if ($action == 'step3') {
	echo_header('Setup FSyncMS - DB setup: ' . $db_type);

	$db_installed = false;
	$db_handle = null;
	try {
		switch ($db_type) {
			case 'sqlite':
				$path = explode('/', $_SERVER['SCRIPT_FILENAME']);
				array_pop($path);
				array_push($path, $db_name);
				$db_name = implode('/', $path);

				if (file_exists($db_name) && filesize($db_name) > 0) {
					$db_installed = true;
				} else {
					echo('Creating sqlite weave storage: ' . $db_name . '<br/>');
					$db_handle = new PDO('sqlite:' . $db_name);
					$db_handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				}
				break;
			case 'pgsql':
				$db_handle = new PDO('pgsql:host=' . $db_host . ';dbname=' . $db_name, $db_user, $db_pass);

				$sth = $db_handle->prepare('SELECT table_name FROM information_schema.tables WHERE table_schema = \'public\' AND table_name = \'' . $db_table_prefix . '_wbo\';');
				$sth->execute();

				$count = $sth->rowCount();
				if ($count > 0) {
					$db_installed = true;
				}
				break;
			case 'mysql':
				$db_handle = new PDO('mysql:host=' . $db_host . ';dbname=' . $db_name, $db_user, $db_pass);

				$sth = $db_handle->prepare('SHOW TABLES LIKE "' . $db_table_prefix . '_wbo";');
				$sth->execute();

				$count = $sth->rowCount();
				if ($count > 0) {
					$db_installed = true;
				}
				break;
		}
	} catch (PDOException $exception) {
		echo('Database unavailable ' . $exception->getMessage());
		throw new Exception('Database unavailable ' . $exception->getMessage(), 503);
	}

	if ($db_installed) {
		echo 'DB is already installed!<br/>';
	} else {
		echo 'Now going to install the new database! Type is: ' . $dbType . '<br>';

		try {
			$create_statement = 'CREATE TABLE ' . $db_table_prefix . '_wbo (username varchar(100), id varchar(65), collection varchar(100),
		             parentid  varchar(65), predecessorid int, modified double precision, sortindex int,
		             payload text, payload_size int, ttl int, PRIMARY KEY (username,collection,id));';
			$create_statement2 = 'CREATE TABLE ' . $db_table_prefix . '_users (username varchar(255), md5 varchar(124), primary key (username));';
			$index1 = 'CREATE INDEX parentindex ON ' . $db_table_prefix . '_wbo (username, parentid);';
			$index2 = 'CREATE INDEX predecessorindex ON ' . $db_table_prefix . '_wbo (username, predecessorid);';
			$index3 = 'CREATE INDEX modifiedindex ON ' . $db_table_prefix . '_wbo (username, collection, modified);';

			$sth = $db_handle->prepare($create_statement);
			$sth->execute();
			$sth = $db_handle->prepare($create_statement2);
			$sth->execute();
			$sth = $db_handle->prepare($index1);
			$sth->execute();
			$sth = $db_handle->prepare($index2);
			$sth->execute();
			$sth = $db_handle->prepare($index3);
			$sth->execute();
			echo 'Database created...<br/>';
		} catch (PDOException $exception) {
			throw new Exception('Database unavailable', 503);
		}

	}

	// get the FSYNC_ROOT url
	$fsRoot = 'https://';
	if (!isset($_SERVER['HTTPS'])) {
		$fsRoot = 'http://';
	}
	$fsRoot .= $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']) . '/';
	if (strpos($_SERVER['REQUEST_URI'], 'index.php') !== 0) {
		$fsRoot .= 'index.php/';
	}

	// write settings.php, if not possible, display the needed content
	write_config_file($db_type, $db_host, $db_name, $db_user, $db_pass, $fsRoot, $db_table_prefix);

	echo '<hr><h2> Finished setup, please delete setup.php!</h2><hr>
			<h4>This script has guessed the Address of your installation, this might not be accurate.<br/>
		    Please check if this script can be reached by <a href="' . $fsRoot . '">' . $fsRoot . '</a> and also make sure to allow any self signed SSL certificate.<br/>
		    If thats not the case you have to ajust the settings.php manually!<br/></h4>';

	echo_footer();
}

?>
