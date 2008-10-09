<?
/*========================================================================
*   Open eClass 2.1
*   E-learning and Course Management System
* ========================================================================
*  Copyright(c) 2003-2008  Greek Universities Network - GUnet
*  A full copyright notice can be read in "/info/copyright.txt".
*
*  Developers Group:	Costas Tsibanis <k.tsibanis@noc.uoa.gr>
*			Yannis Exidaridis <jexi@noc.uoa.gr>
*			Alexandros Diamantidis <adia@noc.uoa.gr>
*			Tilemachos Raptis <traptis@noc.uoa.gr>
*
*  For a full list of contributors, see "credits.txt".
*
*  Open eClass is an open platform distributed in the hope that it will
*  be useful (without any warranty), under the terms of the GNU (General
*  Public License) as published by the Free Software Foundation.
*  The full license can be read in "/info/license/license_gpl.txt".
*
*  Contact address: 	GUnet Asynchronous eLearning Group,
*  			Network Operations Center, University of Athens,
*  			Panepistimiopolis Ilissia, 15784, Athens, Greece
*  			eMail: info@openeclass.org
* =========================================================================*/


// ----------------------------------------------------------------
// Functions used for upgrade
// ----------------------------------------------------------------

//function to update a field in a table
function update_field($table, $field, $field_name, $id_col, $id) {
	$id = quote($id);
	$sql = "UPDATE `$table` SET `$field` = '$field_name' WHERE `$id_col` = $id;";
	db_query($sql);
}

// Removes initial part of path from assignment_submit.file_path
function update_assignment_submit()
{
	global $langTable;

	$updated = FALSE;
	$q = db_query('SELECT id, file_path FROM assignment_submit');
	if ($q) {
		while ($i = mysql_fetch_array($q)) {
			$new = preg_replace('+^.*/work/+', '', $i['file_path']);
			if ($new != $i['file_path']) {
				db_query("UPDATE assignment_submit SET file_path = " .
				quote($new) . " WHERE id = $i[id]");
				$updated = TRUE;
			}
		}
	}
	if ($updated) {
		echo "$langTable assignment_submit: $GLOBALS[OK]<br>\n";
	}
}


// Adds field $field to table $table of current database, if it doesn't already exist
function add_field($table, $field, $type)
{
	global $langToTable, $langAddField, $BAD;

	$retString = "";
	$fields = db_query("SHOW COLUMNS FROM $table LIKE '$field'");
	if (mysql_num_rows($fields) == 0) {
		if (!db_query("ALTER TABLE `$table` ADD `$field` $type")) {
			$retString .= "$langAddField <b>$field</b> $langToTable <b>$table</b>: ";
			$retString .= " $BAD<br>";
		}
	} 
	return $retString;
}

function add_field_after_field($table, $field, $after_field, $type)
{
	global $langToTable, $langAddField, $langAfterField, $BAD;

	$retString = "";
	
	$fields = db_query("SHOW COLUMNS FROM $table LIKE '$field'");
	if (mysql_num_rows($fields) == 0) {
		if (!db_query("ALTER TABLE `$table` ADD COLUMN `$field` $type AFTER `$after_field`")) {
			$retString .= "$langAddField <b>$field</b> $langAfterField <b>$after_field</b> $langToTable <b>$table</b>: ";
			$retString .= " $BAD<br>";
		}
	} 
	return $retString;
}

function rename_field($table, $field, $new_field, $type)
{
	global $langToA, $langRenameField, $langToTable, $BAD;

	$retString = "";
	
	$fields = db_query("SHOW COLUMNS FROM $table LIKE '$new_field'");
	if (mysql_num_rows($fields) == 0) {
		if (!db_query("ALTER TABLE `$table` CHANGE  `$field` `$new_field` $type")) {
			$retString .= "$langRenameField <b>$field</b> $langToA <b>$new_field</b> $langToTable <b>$table</b>: ";
			$retString .= " $BAD<br>";
		} 
	} 
	return $retString;
}

function delete_field($table, $field) {
	global $langOfTable, $langDeleteField, $BAD;

	$retString = "";
	if (!db_query("ALTER TABLE `$table` DROP `$field`")) {
		$retString .= "$langDeleteField <b>$field</b> $langOfTable <b>$table</b>";	
		$retString .= " $BAD<br>";
	}
	return $retString;
}

function delete_table($table)
{
	global $langDeleteTable, $BAD;
	$retString = "";

	if (!db_query("DROP TABLE $table")) {
		$retString .= "$langDeleteTable <b>$table</b>: ";
		$retString .= " $BAD<br>";
	}
	return $retString;
}

function merge_tables($table_destination,$table_source,$fields_destination,$fields_source)
{
	global $langMergeTables, $BAD;

	$retString = "";
	
	$query = "INSERT INTO $table_destination (";
	foreach($fields_destination as $val)
	{
		$query.=$val.",";
	}
	$query=substr($query,0,-1).") SELECT ";
	foreach($fields_source as $val)
	{
		$query.=$val.",";
	}
	$query=substr($query,0,-1)." FROM ".$table_source;
	if (!db_query($query)) {
		$retString .= " $langMergeTables <b>$table_destination</b>,<b>$table_source</b>";
		$retString .= " $BAD<br>";
	}

	return $retString;
}

// checks if a mysql table exists
function mysql_table_exists($db, $table)
{
	$exists = db_query('SHOW TABLES FROM `'.$db.'` LIKE \''.$table.'\'');
	return mysql_num_rows($exists) == 1;
}

// checks if a mysql table field exists

function mysql_field_exists($db,$table,$field)
{
	$fields = db_query("SHOW COLUMNS from $table LIKE '$field'",$db);
	if (mysql_num_rows($fields) > 0)
	return TRUE;

}

// checks if admin user
function is_admin($username, $password, $mysqlMainDb) {

	mysql_select_db($mysqlMainDb);
	$r = mysql_query("SELECT * FROM user, admin WHERE admin.idUser = user.user_id
            AND user.username = '$username' AND user.password = '$password'");
	if (!$r or mysql_num_rows($r) == 0) {
		return FALSE;
	} else {
		$row = mysql_fetch_array($r);
		$_SESSION['uid'] = $row['user_id'];
		//we need to return the user id
		//or setup session UID with the admin's User ID so that it validates @ init.php
		return TRUE;
	}
}

// Check whether an entry with the specified $define_var exists in the accueil table
function accueil_tool_missing($define_var) {
        $r = mysql_query("SELECT id FROM accueil WHERE define_var = '$define_var'");
        if ($r and mysql_num_rows($r) > 0) {
                return false;
        } else {
                return true;
        }
}

// add indexes in specific columns/tables
function add_index($index, $column, $table)  {
	global $langIndexAdded, $langIndexExists, $langOfTable;

        $ind_sql = db_query("SHOW INDEX FROM $table");
        while ($i = mysql_fetch_array($ind_sql))  {
                if ($i['Key_name'] == $index) {
                        $retString = "<p>$langIndexExists $table</p>";
			return $retString;
                }
        }
        db_query("ALTER TABLE $table ADD INDEX $index($column)");
        $retString = "<p>$langIndexAdded $column $langOfTable $table</p>";
        return $retString;
}

// convert database and all tables to UTF-8
function convert_db_utf8($database)
{
	global $langNotTablesList;

        db_query("ALTER DATABASE `$database` DEFAULT CHARACTER SET=utf8");
        $result = mysql_list_tables($database);
        if (!$result) {
                echo "$langNotTablesList $database. ",
                     'MySQL Error: ', mysql_error();
        }
        while ($row = mysql_fetch_row($result)) {
                db_query("ALTER TABLE `$database`.`$row[0]` DEFAULT CHARACTER SET=utf8");
        }
        mysql_free_result($result);
}


// Upgrade course database and documents
function upgrade_course($code, $lang)
{
        global $webDir, $mysqlMainDb, $tool_content, $langTable,
               $langNotMovedDir, $langToDir, $OK, $BAD, $langMoveIntroText,
               $langCorrectTableEntries, $langEncodeDocuments,
               $langEncodeGroupDocuments, $langUpgIndex, $langUpgNotIndex,
               $langCheckPerm, $langUpgFileNotRead, $langUpgFileNotModify,
               $langUpgNotChDir, $langUpgCourse;

        include 'messages_accueil.php';

        mysql_select_db($code);

        // ****************************
        // modify course_code/index.php
        // ****************************

        echo "<hr><p>$langUpgIndex <b>$code</b><br />";
        flush();
        $course_base_dir = "{$webDir}courses/$code";
        if (!is_writable($course_base_dir)) {
                die ("$langUpgNotIndex \"$course_base_dir\"! $langCheckPerm.");
        }

        if (!file_exists("$course_base_dir/temp")) {
                mkdir("$course_base_dir/temp", 0777);
        }

        $filecontents = file_get_contents("$course_base_dir/index.php");
        if (!$filecontents) {
                die($langUpgFileNotRead);
        }
        $newfilecontents = preg_replace('#../claroline/#',
                                        '../../modules/',
                                        $filecontents);
        if (!($fp = @fopen("$course_base_dir/index.php","w"))) {
                die($langUpgFileNotRead);
        }
        if (!@fwrite($fp, $newfilecontents))
                die($langUpgFileNotModify);
        fclose($fp);

        echo "$langUpgCourse <b>$code</b><br>";
        flush();

        // *********************************
        // old upgrade queries
        // *********************************

        // upgrade queries from 1.2 --> 1.4
        if (!mysql_field_exists('$code','exercices','type'))
                echo add_field('exercices','type',"TINYINT( 4 ) UNSIGNED DEFAULT '1' NOT NULL AFTER `description`");
        if (!mysql_field_exists('$code','exercices','random'))
                echo add_field('exercices','random',"SMALLINT( 6 ) DEFAULT '0' NOT NULL AFTER `type`");
        if (!mysql_field_exists('$code','reponses','ponderation'))
                echo add_field('reponses','ponderation',"SMALLINT( 5 ) NOT NULL AFTER `comment`");
        $s = db_query("SELECT type FROM questions",$code);
        while ($f = mysql_fetch_row($s)) {
                if (empty($f[0]))  {
                        if (db_query("UPDATE `questions` SET type=1",$code)) {
                                echo "$langTable questions: $OK<br>";
                        } else {
                                echo "$langTable questions: $BAD<br>";
                        }
                }
        } // while

        if (!mysql_table_exists($code, 'assignments'))  {
                db_query("CREATE TABLE `assignments` (
                        `id` int(11) NOT NULL auto_increment,
                        `title` varchar(200) NOT NULL default '',
                        `description` text NOT NULL,
                        `comments` text NOT NULL,
                        `deadline` date NOT NULL default '0000-00-00',
                        `submission_date` date NOT NULL default '0000-00-00',
                        `active` char(1) NOT NULL default '1',
                        `secret_directory` varchar(30) NOT NULL,
                        `group_submissions` CHAR(1) DEFAULT '0' NOT NULL,
                        UNIQUE KEY `id` (`id`))", $code);
        }

        if (!mysql_table_exists($code, 'assignment_submit')) {
                db_query("CREATE TABLE `assignment_submit` (
                        `id` int(11) NOT NULL auto_increment,
                        `uid` int(11) NOT NULL default '0',
                        `assignment_id` int(11) NOT NULL default '0',
                        `submission_date` date NOT NULL default '0000-00-00',
                        `submission_ip` varchar(16) NOT NULL default '',
                        `file_path` varchar(200) NOT NULL default '',
                        `file_name` varchar(200) NOT NULL default '',
                        `comments` text NOT NULL,
                        `grade` varchar(50) NOT NULL default '',
                        `grade_comments` text NOT NULL,
                        `grade_submission_date` date NOT NULL default '0000-00-00',
                        `grade_submission_ip` varchar(16) NOT NULL default '',
                        `group_id` INT( 11 ) DEFAULT NULL,
                        UNIQUE KEY `id` (`id`))",$code);
        }
        update_assignment_submit();

        // upgrade queries for eClass 1.5
        if (!mysql_table_exists($code, 'videolinks'))  {
                db_query("CREATE TABLE videolinks (
                        id int(11) NOT NULL auto_increment,
                           url varchar(200),
                           titre varchar(200),
                           description text,
                           visibility CHAR(1) DEFAULT '1' NOT NULL,
                           PRIMARY KEY (id))", $code);
        }

        // upgrade queries for eClass 1.6
        echo add_field('liens','category',"INT(4) DEFAULT '0' NOT NULL");
        echo add_field('liens','ordre',"MEDIUMINT(8) DEFAULT '0' NOT NULL");
        if (!mysql_table_exists($code, 'link_categories'))  {
                db_query("CREATE TABLE `link_categories` (
                        `id` int(6) NOT NULL auto_increment,
                        `categoryname` varchar(255) default NULL,
                        `description` text,
                        `ordre` mediumint(8) NOT NULL default '0',
                        PRIMARY KEY  (`id`))",$code);
        }

        // correct link entries to correctly appear in a blank window
        $sql = db_query("SELECT url FROM `liens` WHERE url REGEXP '\"target=_blank$'");
        while ($u = mysql_fetch_row($sql))  {
                $temp = $u[0];
                $newurl = preg_replace('#\s*"target=_blank#','',$temp);
                db_query("UPDATE liens SET url='$newurl' WHERE url='$temp'");
        }

        // for dropbox
        if (!mysql_table_exists($code, 'dropbox_file'))  {
                db_query("INSERT INTO accueil VALUES (
                        16,
                        '$langDropbox[$lang]',
                        '../../modules/dropbox/index.php',
                        'dropbox',
                        '0',
                        '0',
                        '',
                        )", $code);

                db_query("CREATE TABLE dropbox_file (
                        id int(11) unsigned NOT NULL auto_increment,
                           uploaderId int(11) unsigned NOT NULL default '0',
                           filename varchar(250) NOT NULL default '',
                           filesize int(11) unsigned NOT NULL default '0',
                           title varchar(250) default '',
                           description varchar(250) default '',
                           author varchar(250) default '',
                           uploadDate datetime NOT NULL default '0000-00-00 00:00:00',
                           lastUploadDate datetime NOT NULL default '0000-00-00 00:00:00',
                           PRIMARY KEY (id),
                           UNIQUE KEY UN_filename (filename))", $code);
        }
        if (!mysql_table_exists($code, 'dropbox_person'))  {
                db_query("CREATE TABLE dropbox_person (
                        fileId int(11) unsigned NOT NULL default '0',
                               personId int(11) unsigned NOT NULL default '0',
                               PRIMARY KEY  (fileId,personId))", $code);
        }
        if (!mysql_table_exists($code, 'dropbox_post'))  {
                db_query("CREATE TABLE dropbox_post (
                        fileId int(11) unsigned NOT NULL default '0',
                               recipientId int(11) unsigned NOT NULL default '0',
                               PRIMARY KEY  (fileId,recipientId))", $code);
        }

        // ********************************************
        // new upgrade queries for eClass 2.0
        // ********************************************
        if (mysql_table_exists($code, 'work'))
                db_query("DROP TABLE `work`");
        if (mysql_table_exists($code, 'work_student'))
                db_query("DROP TABLE `work_student`");

        $sql = 'SELECT id, titre, contenu, day, hour, lasting
                FROM  agenda WHERE CONCAT(titre,contenu) != \'\'
                AND DATE_FORMAT(day,\'%Y %m %d\') >= \''.date("Y m d").'\'';

        //  Get all agenda events from each table & parse them to arrays
        $mysql_query_result = db_query($sql, $code);
        $event_counter=0;
        while ($myAgenda = mysql_fetch_array($mysql_query_result)) {
                $lesson_agenda[$event_counter]['id'] = $myAgenda[0];
                $lesson_agenda[$event_counter]['title'] = $myAgenda[1];
                $lesson_agenda[$event_counter]['content'] = $myAgenda[2];
                $lesson_agenda[$event_counter]['date'] = $myAgenda[3];
                $lesson_agenda[$event_counter]['time'] = $myAgenda[4];
                $lesson_agenda[$event_counter]['duree'] = $myAgenda[5];
                $lesson_agenda[$event_counter]['lesson_code'] = $code;
                $event_counter++;
        }

        for ($j=0; $j <$event_counter; $j++) {
                db_query("INSERT INTO agenda (lesson_event_id, titre, contenu, day, hour, lasting, lesson_code)
                                VALUES (".q($lesson_agenda[$j]['id']).",
                                        ".q($lesson_agenda[$j]['title']).",
                                        ".q($lesson_agenda[$j]['content']).",
                                        ".$lesson_agenda[$j]['date'].",
                                        ".$lesson_agenda[$j]['time'].",
                                        ".$lesson_agenda[$j]['duree'].",
                                        '".$lesson_agenda[$j]['lesson_code']."'
                                       )", $mysqlMainDb);

        }
        // end of agenda

        // Group table
        if (!mysql_table_exists($code, 'group_documents'))  {
                db_query("CREATE TABLE `group_documents` (
                        `id` INT(4) NOT NULL AUTO_INCREMENT,
                        `path` VARCHAR(255) default NULL ,
                        `filename` VARCHAR(255) default NULL,
                        PRIMARY KEY(id))", $code);
        }

        // Learning Path tables
        if (!mysql_table_exists($code, 'lp_module'))  {
                db_query("CREATE TABLE `lp_module` (
                        `module_id` int(11) NOT NULL auto_increment,
                        `name` varchar(255) NOT NULL default '',
                        `comment` text NOT NULL,
                        `accessibility` enum('PRIVATE','PUBLIC') NOT NULL default 'PRIVATE',
                        `startAsset_id` int(11) NOT NULL default '0',
                        `contentType` enum('CLARODOC','DOCUMENT','EXERCISE','HANDMADE','SCORM','LABEL','COURSE_DESCRIPTION','LINK') NOT NULL,
                        `launch_data` text NOT NULL,
                        PRIMARY KEY  (`module_id`)
                                ) ", $code); //TYPE=MyISAM COMMENT='List of available modules used in learning paths';
        }
        if (!mysql_table_exists($code, 'lp_learnPath'))  {
                db_query("CREATE TABLE `lp_learnPath` (
                        `learnPath_id` int(11) NOT NULL auto_increment,
                        `name` varchar(255) NOT NULL default '',
                        `comment` text NOT NULL,
                        `lock` enum('OPEN','CLOSE') NOT NULL default 'OPEN',
                        `visibility` enum('HIDE','SHOW') NOT NULL default 'SHOW',
                        `rank` int(11) NOT NULL default '0',
                        PRIMARY KEY  (`learnPath_id`),
                        UNIQUE KEY rank (`rank`)
                                ) ", $code); //TYPE=MyISAM COMMENT='List of learning Paths';
        }

        if (!mysql_table_exists($code, 'lp_rel_learnPath_module'))  {
                db_query("CREATE TABLE `lp_rel_learnPath_module` (
                        `learnPath_module_id` int(11) NOT NULL auto_increment,
                        `learnPath_id` int(11) NOT NULL default '0',
                        `module_id` int(11) NOT NULL default '0',
                        `lock` enum('OPEN','CLOSE') NOT NULL default 'OPEN',
                        `visibility` enum('HIDE','SHOW') NOT NULL default 'SHOW',
                        `specificComment` text NOT NULL,
                        `rank` int(11) NOT NULL default '0',
                        `parent` int(11) NOT NULL default '0',
                        `raw_to_pass` tinyint(4) NOT NULL default '50',
                        PRIMARY KEY  (`learnPath_module_id`)
                                ) ", $code);//TYPE=MyISAM COMMENT='This table links module to the learning path using them';
        }

        if (!mysql_table_exists($code, 'lp_asset'))  {
                db_query("CREATE TABLE `lp_asset` (
                        `asset_id` int(11) NOT NULL auto_increment,
                        `module_id` int(11) NOT NULL default '0',
                        `path` varchar(255) NOT NULL default '',
                        `comment` varchar(255) default NULL,
                        PRIMARY KEY  (`asset_id`)
                                ) ", $code); //TYPE=MyISAM COMMENT='List of resources of module of learning paths';
        }

        if (!mysql_table_exists($code, 'lp_user_module_progress'))  {
                db_query("CREATE TABLE `lp_user_module_progress` (
                        `user_module_progress_id` int(22) NOT NULL auto_increment,
                        `user_id` mediumint(9) NOT NULL default '0',
                        `learnPath_module_id` int(11) NOT NULL default '0',
                        `learnPath_id` int(11) NOT NULL default '0',
                        `lesson_location` varchar(255) NOT NULL default '',
                        `lesson_status` enum('NOT ATTEMPTED','PASSED','FAILED','COMPLETED','BROWSED','INCOMPLETE','UNKNOWN') NOT NULL default 'NOT ATTEMPTED',
                        `entry` enum('AB-INITIO','RESUME','') NOT NULL default 'AB-INITIO',
                        `raw` tinyint(4) NOT NULL default '-1',
                        `scoreMin` tinyint(4) NOT NULL default '-1',
                        `scoreMax` tinyint(4) NOT NULL default '-1',
                        `total_time` varchar(13) NOT NULL default '0000:00:00.00',
                        `session_time` varchar(13) NOT NULL default '0000:00:00.00',
                        `suspend_data` text NOT NULL,
                        `credit` enum('CREDIT','NO-CREDIT') NOT NULL default 'NO-CREDIT',
                        PRIMARY KEY  (`user_module_progress_id`)
                                ) ", $code); //TYPE=MyISAM COMMENT='Record the last known status of the user in the course';
        }

        // Wiki tables
        if (!mysql_table_exists($code, 'wiki_properties'))  {
                db_query("CREATE TABLE `wiki_properties` (
                        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                        `title` VARCHAR(255) NOT NULL DEFAULT '',
                        `description` TEXT NULL,
                        `group_id` INT(11) NOT NULL DEFAULT 0,
                        PRIMARY KEY(`id`)
                                ) ", $code);
        }

        if (!mysql_table_exists($code, 'wiki_acls'))  {
                db_query("CREATE TABLE `wiki_acls` (
                        `wiki_id` INT(11) UNSIGNED NOT NULL,
                        `flag` VARCHAR(255) NOT NULL,
                        `value` ENUM('false','true') NOT NULL DEFAULT 'false'
                                ) ", $code);
        }

        if (!mysql_table_exists($code, 'wiki_pages'))  {
                db_query("CREATE TABLE `wiki_pages` (
                        `id` int(11) unsigned NOT NULL auto_increment,
                        `wiki_id` int(11) unsigned NOT NULL default '0',
                        `owner_id` int(11) unsigned NOT NULL default '0',
                        `title` varchar(255) NOT NULL default '',
                        `ctime` datetime NOT NULL default '0000-00-00 00:00:00',
                        `last_version` int(11) unsigned NOT NULL default '0',
                        `last_mtime` datetime NOT NULL default '0000-00-00 00:00:00',
                        PRIMARY KEY  (`id`)
                                ) ", $code);
        }

        if (!mysql_table_exists($code, 'wiki_pages_content'))  {
                db_query("CREATE TABLE `wiki_pages_content` (
                        `id` int(11) unsigned NOT NULL auto_increment,
                        `pid` int(11) unsigned NOT NULL default '0',
                        `editor_id` int(11) NOT NULL default '0',
                        `mtime` datetime NOT NULL default '0000-00-00 00:00:00',
                        `content` text NOT NULL,
                        PRIMARY KEY  (`id`)
                                ) ", $code);
        }

        // questionnaire tables
        if (!mysql_table_exists($code, 'survey'))  {
                db_query("CREATE TABLE `survey` (
                        `sid` bigint(14) NOT NULL auto_increment,
                        `creator_id` mediumint(8) unsigned NOT NULL default '0',
                        `course_id` varchar(20) NOT NULL default '0',
                        `name` varchar(255) NOT NULL default '',
                        `creation_date` datetime NOT NULL default '0000-00-00 00:00:00',
                        `start_date` datetime NOT NULL default '0000-00-00 00:00:00',
                        `end_date` datetime NOT NULL default '0000-00-00 00:00:00',
                        `type` int(11) NOT NULL default '0',
                        `active` int(11) NOT NULL default '0',
                        PRIMARY KEY  (`sid`)
                                ) ", $code); //TYPE=MyISAM COMMENT='For the questionnaire module';
        }
        if (!mysql_table_exists($code, 'survey_answer'))  {
                db_query("CREATE TABLE `survey_answer` (
                        `aid` bigint(12) NOT NULL default '0',
                        `creator_id` mediumint(8) unsigned NOT NULL default '0',
                        `sid` bigint(12) NOT NULL default '0',
                        `date` datetime NOT NULL default '0000-00-00 00:00:00',
                        PRIMARY KEY  (`aid`)
                                ) ", $code); //TYPE=MyISAM COMMENT='For the questionnaire module';
        }
        if (!mysql_table_exists($code, 'survey_answer_record'))  {
                db_query("CREATE TABLE `survey_answer_record` (
                        `arid` int(11) NOT NULL auto_increment,
                        `aid` bigint(12) NOT NULL default '0',
                        `question_text` varchar(250) NOT NULL default '',
                        `question_answer` varchar(250) NOT NULL default '',
                        PRIMARY KEY  (`arid`)
                                ) ", $code); //TYPE=MyISAM COMMENT='For the questionnaire module';
        }
        if (!mysql_table_exists($code, 'survey_question'))  {
                db_query("CREATE TABLE `survey_question` (
                        `sqid` bigint(12) NOT NULL default '0',
                        `sid` bigint(12) NOT NULL default '0',
                        `question_text` varchar(250) NOT NULL default '',
                        PRIMARY KEY  (`sqid`)
                                ) ", $code); //TYPE=MyISAM COMMENT='For the questionnaire module';
        }
        if (!mysql_table_exists($code, 'survey_question_answer'))  {
                db_query("CREATE TABLE `survey_question_answer` (
                        `sqaid` int(11) NOT NULL auto_increment,
                        `sqid` bigint(12) NOT NULL default '0',
                        `answer_text` varchar(250) default NULL,
                        PRIMARY KEY  (`sqaid`)
                                ) ", $code); //TYPE=MyISAM COMMENT='For the questionnaire module';
        }

        // poll tables
        if (!mysql_table_exists($code, 'poll'))  {
                db_query("CREATE TABLE `poll` (
                        `pid` bigint(14) NOT NULL auto_increment,
                        `creator_id` mediumint(8) unsigned NOT NULL default '0',
                        `course_id` varchar(20) NOT NULL default '0',
                        `name` varchar(255) NOT NULL default '',
                        `creation_date` date NOT NULL default '0000-00-00',
                        `start_date` date NOT NULL default '0000-00-00',
                        `end_date` date NOT NULL default '0000-00-00',
                        `type` int(11) NOT NULL default '0',
                        `active` int(11) NOT NULL default '0',
                        PRIMARY KEY  (`pid`)
                                ) ", $code); //TYPE=MyISAM COMMENT='For the poll module';
        } else {
                db_query("ALTER TABLE `poll` CHANGE `creation_date` `creation_date` DATE NOT NULL DEFAULT '0000-00-00'", $code);
                db_query("ALTER TABLE `poll` CHANGE `start_date` `start_date` DATE NOT NULL DEFAULT '0000-00-00'", $code);
                db_query("ALTER TABLE `poll` CHANGE `end_date` `end_date` DATE NOT NULL DEFAULT '0000-00-00'", $code);
        }

        if (!mysql_table_exists($code, 'poll_answer'))  {
                db_query("CREATE TABLE `poll_answer` (
                        `aid` bigint(12) NOT NULL default '0',
                        `creator_id` mediumint(8) unsigned NOT NULL default '0',
                        `pid` bigint(12) NOT NULL default '0',
                        `date` datetime NOT NULL default '0000-00-00 00:00:00',
                        PRIMARY KEY  (`aid`)
                                ) ", $code); //TYPE=MyISAM COMMENT='For the poll module';
        }
        if (!mysql_table_exists($code, 'poll_answer_record'))  {
                db_query("CREATE TABLE `poll_answer_record` (
                        `arid` int(11) NOT NULL auto_increment,
                        `aid` bigint(12) NOT NULL default '0',
                        `question_text` varchar(250) NOT NULL default '',
                        `question_answer` varchar(250) NOT NULL default '',
                        PRIMARY KEY  (`arid`)
                                ) ", $code); //TYPE=MyISAM COMMENT='For the poll module';
        }

        if (!mysql_field_exists("$code",'poll_answer_record', 'qtype'))
                echo add_field_after_field('poll_answer_record', 'pid', 'arid', "INT(11) NOT NULL");
        if (!mysql_field_exists("$code",'poll_answer_record', 'pid'))
                echo add_field_after_field('poll_answer_record', 'pid', 'arid', "INT(11) NOT NULL DEFAULT '0'");
        if (!mysql_field_exists("$code",'poll_answer_record', 'qid'))
                echo add_field_after_field('poll_answer_record', 'qid', 'pid', "INT(11) NOT NULL DEFAULT '0'");
        if (mysql_field_exists("$code",'poll_answer_record', 'question_text'))
                echo delete_field('poll_answer_record', 'question_text');
        if (mysql_field_exists("$code",'poll_answer_record', 'question_answer'))
                echo delete_field('poll_answer_record', 'question_answer');
        if (!mysql_field_exists("$code",'poll_answer_record','answer_text'))
                echo add_field('poll_answer_record', 'answer_text', "VARCHAR(255) NOT NULL");
        if (!mysql_field_exists("$code",'poll_answer_record','user_id'))
                echo add_field('poll_answer_record', 'user_id', "INT(11) NOT NULL DEFAULT '0'");
        if (!mysql_field_exists("$code",'poll_answer_record', 'submit_date'))
                echo add_field('poll_answer_record', 'submit_date', "DATE NOT NULL DEFAULT '0000-00-00'");

        if (!mysql_table_exists($code, 'poll_question'))  {
                db_query("CREATE TABLE `poll_question` (
                        `pqid` bigint(12) NOT NULL default '0',
                        `pid` bigint(12) NOT NULL default '0',
                        `question_text` varchar(250) NOT NULL default '',
                        PRIMARY KEY  (`pqid`)
                                ) ", $code); //TYPE=MyISAM COMMENT='For the poll module';
        }

        if (!mysql_field_exists("$code",'poll_question','qtype'))
                echo add_field('poll_question', 'qtype', "ENUM('multiple', 'fill') NOT NULL");

        if (!mysql_table_exists($code, 'poll_question_answer'))  {
                db_query("CREATE TABLE `poll_question_answer` (
                        `pqaid` int(11) NOT NULL auto_increment,
                        `pqid` bigint(12) NOT NULL default '0',
                        `answer_text` varchar(250) default NULL,
                        PRIMARY KEY  (`pqaid`)
                                ) ", $code); //TYPE=MyISAM COMMENT='For the poll module';
        }


        //  usage tables
        if (!mysql_table_exists($code, 'actions')) {
                db_query("CREATE TABLE actions (
                        id int(11) NOT NULL auto_increment,
                           user_id int(11) NOT NULL,
                           module_id int(11) NOT NULL,
                           action_type_id int(11) NOT NULL,
                           date_time DATETIME NOT NULL default '0000-00-00 00:00:00',
                           duration int(11) NOT NULL,
                           PRIMARY KEY (id))", $code);
        }

        if (!mysql_table_exists($code, 'logins')) {
                db_query("CREATE TABLE logins (
                        id int(11) NOT NULL auto_increment,
                           user_id int(11) NOT NULL,
                           ip char(16) NOT NULL default '0.0.0.0',
                           date_time DATETIME NOT NULL default '0000-00-00 00:00:00',
                           PRIMARY KEY (id))", $code);
        }

        if (!mysql_table_exists($code, 'action_types')) {
                db_query("CREATE TABLE action_types (
                        id int(11) NOT NULL auto_increment,
                           name varchar(200),
                           PRIMARY KEY (id))", $code);
                db_query("INSERT INTO action_types VALUES ('1', 'access')", $code);
        }
        if (!mysql_table_exists($code, 'actions_summary')) {
                db_query("CREATE TABLE actions_summary (
                        id int(11) NOT NULL auto_increment,
                           module_id int(11) NOT NULL,
                           visits int(11) NOT NULL,
                           start_date DATETIME NOT NULL default '0000-00-00 00:00:00',
                           end_date DATETIME NOT NULL default '0000-00-00 00:00:00',
                           duration int(11) NOT NULL,
                           PRIMARY KEY (id))", $code);
        }

        // exercise tables
        if (!mysql_table_exists($code, 'exercise_user_record'))  {
                db_query("CREATE TABLE `exercise_user_record` (
                        `eurid` int(11) NOT NULL auto_increment,
                        `eid` tinyint(4) NOT NULL default '0',
                        `uid` mediumint(8) NOT NULL default '0',
                        `RecordStartDate` date NOT NULL default '0000-00-00',
                        `RecordEndDate` date NOT NULL default '0000-00-00',
                        `TotalScore` int(11) NOT NULL default '0',
                        `TotalWeighting` int(11) default '0',
                        `attempt` int(11) NOT NULL default '0',
                        PRIMARY KEY  (`eurid`)
                                ) ", $code); //TYPE=MyISAM COMMENT='For the exercise module';
        }

        // Upgrading EXERCICES table for new func of EXERCISE module
        if (!mysql_field_exists("$code",'exercices','StartDate'))
                echo add_field_after_field('exercices', 'StartDate', 'type', "DATE NOT NULL default '0000-00-00'");
        else
                db_query("ALTER TABLE `exercices` CHANGE `StartDate` `StartDate` DATE NULL DEFAULT NULL", $code);
        if (!mysql_field_exists("$code",'exercices','EndDate'))
                echo add_field_after_field('exercices', 'EndDate', 'StartDate', "DATE NOT NULL default '0000-00-00'");
        else
                db_query("ALTER TABLE `exercices` CHANGE `EndDate` `EndDate` DATE NULL DEFAULT NULL", $code);
        if (!mysql_field_exists("$code",'exercices','TimeConstrain'))
                echo add_field_after_field('exercices', 'TimeConstrain', 'EndDate', "INT(11)");
        if (!mysql_field_exists("$code",'exercices','AttemptsAllowed'))
                echo add_field_after_field('exercices', 'AttemptsAllowed', 'TimeConstrain', "INT(11)");

        // add new document fields
        if (!mysql_field_exists("$code",'document','filename'))
                echo add_field('document', 'filename', "TEXT");
        if (!mysql_field_exists("$code",'document','category'))
                echo add_field('document', 'category', "TEXT");
        if (!mysql_field_exists("$code",'document','title'))
                echo add_field('document', 'title', "TEXT");
        if (!mysql_field_exists("$code",'document','creator'))
                echo add_field('document', 'creator', "TEXT");
        if (!mysql_field_exists("$code",'document','date'))
                echo add_field('document', 'date', "DATETIME");
        if (!mysql_field_exists("$code",'document','date_modified'))
                echo add_field('document', 'date_modified', "DATETIME");
        if (!mysql_field_exists("$code",'document','subject'))
                echo add_field('document', 'subject', "TEXT");
        if (!mysql_field_exists("$code",'document','description'))
                echo add_field('document', 'description', "TEXT");
        if (!mysql_field_exists("$code",'document','author'))
                echo add_field('document', 'author', "TEXT");
        if (!mysql_field_exists("$code",'document','format'))
                echo add_field('document', 'format', "TEXT");
        if (!mysql_field_exists("$code",'document','language'))
                echo add_field('document', 'language', "TEXT");
        if (!mysql_field_exists("$code",'document','copyrighted'))
                echo add_field('document', 'copyrighted', "TEXT");


        // -------------- document upgrade ---------------------
        echo "$langEncodeDocuments<br>";
        flush();
        encode_documents($code);

        // -------------- group document upgrade ---------------
        echo "$langEncodeGroupDocuments<br>";
        flush();
        // find group secret directory
        $s = db_query("SELECT id, secretDirectory FROM student_group");
        while ($group = mysql_fetch_array($s)) {
                encode_group_documents($code, $group['id'], $group['secretDirectory']);
        }


        // -------------- move video files to new directory -----
        $course_video = "${webDir}courses/$code/video";
        if (is_dir($course_video)) {
                if (!rename($course_video, "${webDir}video/$code")) {
                        echo "$langNotMovedDir $course_video $langToDir video<br>";
                }
        }
        // upgrade video
        if (!mysql_field_exists("$code",'video','path')) {
                echo add_field_after_field('video', 'path', 'id', "VARCHAR(255)");
                $r = db_query("SELECT * FROM video", $code);
                while ($row = mysql_fetch_array($r)) {
                        upgrade_video($row['url'], $row['id'], $code);
                }
        }
        if (!mysql_field_exists("$code",'video','creator'))
                echo add_field_after_field('video', 'creator', 'description', "VARCHAR(255)");
        if (!mysql_field_exists("$code",'video','publisher'))
                echo add_field_after_field('video', 'publisher', 'creator',"VARCHAR(255)");
        if (!mysql_field_exists("$code",'video','date'))
                echo add_field_after_field('video', 'date', 'publisher',"DATETIME");

        // upgrade videolinks
        if (!mysql_field_exists("$code",'videolinks','creator'))
                echo add_field_after_field('videolinks', 'creator', 'description', "VARCHAR(255)");
        if (!mysql_field_exists("$code",'videolinks','publisher'))
                echo add_field_after_field('videolinks', 'publisher', 'creator',"VARCHAR(255)");
        if (!mysql_field_exists("$code",'videolinks','date'))
                echo add_field_after_field('videolinks', 'date', 'publisher',"DATETIME");

        // upgrading accueil table

        //  create new column (define_var)
        echo add_field("accueil","define_var", "VARCHAR(50) NOT NULL");

        // Move all external links to id > 100
        db_query("UPDATE `accueil`
                        SET `id` = `id` + 80
                        WHERE `id`>20 AND `id`<100
                        AND `define_var` <> 'MODULE_ID_QUESTIONNAIRE' AND `define_var` <> 'MODULE_ID_LP'
                        AND `define_var` <> 'MODULE_ID_USAGE' AND `define_var` <> 'MODULE_ID_TOOLADMIN'
                        AND `define_var` <> 'MODULE_ID_WIKI'", $code);

        // id νέων υποσυστημάτων
        if (accueil_tool_missing('MODULE_ID_QUESTIONNAIRE')) {
                db_query("INSERT IGNORE INTO accueil VALUES (
                        '21',
                        '$langQuestionnaire[$lang]',
                        '../../modules/questionnaire/questionnaire.php',
                        'questionnaire',
                        '0',
                        '0',
                        '',
                        'MODULE_ID_QUESTIONNAIRE'
                                )", $code);
        }

        if (accueil_tool_missing('MODULE_ID_LP')) {
                db_query("INSERT IGNORE INTO accueil VALUES (
                        '23',
                        '$langLearnPath[$lang]',
                        '../../modules/learnPath/learningPathList.php',
                        'lp',
                        '0',
                        '0',
                        '',
                        'MODULE_ID_LP'
                                )", $code);
        }

        if (accueil_tool_missing('MODULE_ID_USAGE')) {
                db_query("INSERT IGNORE INTO accueil VALUES (
                        '24',
                        '$langCourseStat[$lang]',
                        '../../modules/usage/usage.php',
                        'usage',
                        '0',
                        '1',
                        '',
                        'MODULE_ID_USAGE')", $code);
        }

        if (accueil_tool_missing('MODULE_ID_TOOLADMIN')) {
                db_query("INSERT IGNORE INTO accueil VALUES (
                        '25',
                        '$langToolManagement[$lang]',
                        '../../modules/course_tools/course_tools.php',
                        'tooladmin',
                        '0',
                        '1',
                        '',
                        'MODULE_ID_TOOLADMIN'
                                )", $code);
        }

        if (accueil_tool_missing('MODULE_ID_WIKI')) {
                db_query("INSERT IGNORE INTO accueil VALUES (
                        '26',
                        '$langWiki[$lang]',
                        '../../modules/wiki/wiki.php',
                        'wiki',
                        '0',
                        '0',
                        '',
                        'MODULE_ID_WIKI'
                                )", $code);
        }

        // table accueil
        echo "$langCorrectTableEntries accueil.<br>";

        /* compatibility update
           a) remove entries modules import, external, videolinks, old statistics
           b) correct agenda and video link
         */
        db_query("DELETE FROM accueil WHERE (id = 12 OR id = 13 OR id = 11 OR id=6)", $code);
        update_field("accueil", "lien", "../../modules/agenda/agenda.php", "id", 1);
        db_query("UPDATE accueil SET visible = '0', admin = '1' WHERE id = 8 LIMIT 1", $code);
        update_field("accueil", "lien", "../../modules/video/video.php", "id", 4);
        update_field("accueil", "lien", "../../modules/conference/conference.php", "id", 19);

        //set define string vars
        update_field("accueil", "define_var", "MODULE_ID_AGENDA", "id", 1);
        update_field("accueil", "define_var", "MODULE_ID_LINKS", "id",	2);
        update_field("accueil", "define_var", "MODULE_ID_DOCS", "id", 3);
        update_field("accueil", "define_var", "MODULE_ID_VIDEO", "id", 4);
        update_field("accueil", "define_var", "MODULE_ID_ASSIGN", "id", 5);
        update_field("accueil", "define_var", "MODULE_ID_ANNOUNCE", "id", 7);
        update_field("accueil", "define_var", "MODULE_ID_USERS", "id",	8);
        update_field("accueil", "define_var", "MODULE_ID_FORUM", "id", 9);
        update_field("accueil", "define_var", "MODULE_ID_EXERCISE", "id", 10);
        update_field("accueil", "define_var", "MODULE_ID_COURSEINFO", "id", 14);
        update_field("accueil", "define_var", "MODULE_ID_GROUPS", "id", 15);
        update_field("accueil", "define_var", "MODULE_ID_DROPBOX", "id", 16);
        update_field("accueil", "define_var", "MODULE_ID_CHAT", "id", 	19);
        update_field("accueil", "define_var", "MODULE_ID_DESCRIPTION","id", 20);

        $sql = db_query("SELECT id,lien,image,address FROM accueil");
        while ($u = mysql_fetch_row($sql))  {
                $oldlink_lien = $u[1];
                $newlink_lien = preg_replace('#../claroline/#','../../modules/',$oldlink_lien);
                $oldlink_image = $u[2];
                $newlink_image = preg_replace('#../claroline/image/#','../../images/',$oldlink_image);
                $oldlink_address = $u[3];
                $newlink_address = preg_replace('#../claroline/image/#','../../images/',$oldlink_address);
                db_query("UPDATE accueil SET lien='$newlink_lien',
                                image='$newlink_image', address='$newlink_address' WHERE id='$u[0]'");
        }

        //set the new images for the icons of lesson modules
        update_field("accueil", "image","calendar", "id", 1);
        update_field("accueil", "image","links", "id",	2);
        update_field("accueil", "image","docs", "id",	 3);
        update_field("accueil", "image","videos", "id",	4);
        update_field("accueil", "image","assignments", "id",5);
        update_field("accueil", "image","announcements", "id",7);
        update_field("accueil", "image","users", "id", 8);
        update_field("accueil", "image","forum", "id", 9);
        update_field("accueil", "image","exercise", "id", 10);
        update_field("accueil", "image","course_info", "id",	14);
        update_field("accueil", "image","groups", "id", 15);
        update_field("accueil", "image","dropbox", "id", 16);
        update_field("accueil", "image","conference", "id", 19);
        update_field("accueil", "image","description", "id",	20);

        $sql = db_query("SELECT image FROM accueil");
        while ($u = mysql_fetch_row($sql))  {
                if ($u[0] == '../../../images/npage.gif')  {
                        update_field("accueil", "image", "external_link", "image", "../../../images/npage.gif");
                }
                if ($u[0] == '../../../images/page.gif')  {
                        update_field("accueil", "image", "external_link", "image", "../../../images/page.gif");
                }
                if ($u[0] == 'travaux.png')  {
                        update_field("accueil", "image", "external_link", "image", "travaux.png");
                }
        }

        // update menu entries with new messages
        update_field("accueil", "rubrique", "$langWork[$lang]", "id", "5");
        update_field("accueil", "rubrique", "$langForums[$lang]", "id", "9");
        update_field("accueil", "rubrique", "$langUsers[$lang]", "id", "8");
        update_field("accueil", "visible", "0", "id", "8");
        update_field("accueil", "admin", "1", "id", "8");
        update_field("accueil", "rubrique", "$langCourseAdmin[$lang]", "id", "14");
        update_field("accueil", "rubrique", "$langDropBox[$lang]", "id", "16");

        // remove table 'introduction' entries and insert them in table 'cours' (field 'description') in eclass maindb
        // after that drop table introduction
        if (mysql_table_exists($code, 'introduction')) {
                $sql = db_query("SELECT texte_intro FROM introduction", $code);
                while ($text = mysql_fetch_array($sql)) {
                        $description = quote($text[0]);
                        if (db_query("UPDATE cours SET description=$description WHERE code='$code'", $mysqlMainDb)) {
                                db_query("DROP TABLE IF EXISTS introduction", $code);
                        } else {
                                echo "$langMoveIntroText <b>cours</b>: $BAD<br>";
                        }
                }
        } // end of table introduction

        $tool_content .= "<br><br></td></tr>";

        // add full text indexes for search operation (ginetai xrhsh @mysql_query(...) giati ean
        // yparxei hdh, to FULL INDEX den mporei na ksanadhmiourgithei. epipleon, den yparxei tropos
        // elegxou gia to an yparxei index, opote o monadikos tropos diekperaiwshs ths ergasias einai
        // dokimh-sfalma.
        @mysql_query("ALTER TABLE `agenda` ADD FULLTEXT `agenda` (`titre` ,`contenu`)");
        @mysql_query("ALTER TABLE `course_description` ADD FULLTEXT `course_description` (`title` ,`content`)");
        @mysql_query("ALTER TABLE `document` ADD FULLTEXT `document` (`filename` ,`comment` ,`title`,`creator`,`subject`,`description`,`author`,`language`)");
        @mysql_query("ALTER TABLE `exercices` ADD FULLTEXT `exercices` (`titre`,`description`)");
        @mysql_query("ALTER TABLE `posts_text` ADD FULLTEXT `posts_text` (`post_text`)");
	@mysql_query("ALTER TABLE `forums` ADD FULLTEXT `forums` (`forum_name`,`forum_desc`)");
        @mysql_query("ALTER TABLE `liens` ADD FULLTEXT `liens` (`url` ,`titre` ,`description`)");
        @mysql_query("ALTER TABLE `video` ADD FULLTEXT `video` (`url` ,`titre` ,`description`)");

        // bogart: Update code for phpbb functionality START
        // Remove tables banlist, disallow, headermetafooter, priv_msgs, ranks, sessions, themes, whosonline, words
        db_query("DROP TABLE IF EXISTS access");
        db_query("DROP TABLE IF EXISTS banlist");
        db_query("DROP TABLE IF EXISTS config");
        db_query("DROP TABLE IF EXISTS disallow");
        db_query("DROP TABLE IF EXISTS forum_access");
        db_query("DROP TABLE IF EXISTS forum_mods");
        db_query("DROP TABLE IF EXISTS headermetafooter");
        db_query("DROP TABLE IF EXISTS priv_msgs");
        db_query("DROP TABLE IF EXISTS ranks");
        db_query("DROP TABLE IF EXISTS sessions");
        db_query("DROP TABLE IF EXISTS themes");
        db_query("DROP TABLE IF EXISTS whosonline");
        db_query("DROP TABLE IF EXISTS words");
        // bogart: Update code for phpbb functionality END

        // remove tables liste_domains. Used for old statistics module
        db_query("DROP TABLE IF EXISTS liste_domaines");

        // convert to UTF-8
        convert_db_utf8($code);
}


// -----------------------------------
// functions for document ------------
// -----------------------------------

// Returns a random filename with the same extension as $filename
function random_filename($filename)  {
        $ext = get_file_extention($filename);
        if (!empty($ext)) {
                $ext = '.' . $ext;
        }
        return date("mdGi") . randomkeys('5') . $ext;
}

// Rename a file and insert its information in the database, if needed
// Returns the new file path or false if file wasn't renamed
function document_upgrade_file($path, $data)
{
        if ($data == 'document') {
                $table = 'document';
        } else {
                $table = 'group_documents';
        }

        // Filenames in older versions of eClass were in ISO-8859-7
        // No need to conver them, we're assuming "SET NAMES greek"
        $db_path = trim_path($path);
        $old_filename = preg_replace('|^.*/|', '', $db_path);
        $new_filename = random_filename($old_filename);
        $new_path = preg_replace('|/[^/]*$|', "/$new_filename", $db_path);
        $file_date = quote(date('c', filemtime($path)));
        $r = db_query("SELECT * FROM $table WHERE path = ".quote($db_path));
        if (mysql_num_rows($r) > 0) {
                $current_filename = mysql_fetch_array($r);
                if (empty($current_filename['filename']) or
                    preg_match('/[^\040-\177]/', $current_filename['path'])) {
                        if (!empty($current_filename['filename'])) {
                                $old_filename = $current_filename['filename'];
                        }
                        // File exists in database, hasn't been upgraded
                        db_query("UPDATE $table
                                        SET filename = " . quote($old_filename) . ",
                                        path = " . quote($new_path) . ",
                                        date = $file_date, date_modified = $file_date
                                        WHERE path= " . quote($db_path));
                        rename($path, $data.$new_path);
                } else {
                        // File wasn't renamed
                        $new_path = false;
                }
        } else {
                // File doesn't exist in database
                if ($table == 'document') {
                        db_query("INSERT INTO document
                                  SET path = " . quote($new_path) . ",
                                      filename = " . quote($old_filename) . ",
                                      visibility = 'v',
                                      comment = '', category = '',
                                      title = '', creator = '',
                                      date = $file_date, date_modified = $file_date,
                                      subject = '', description = '',
                                      author = '', format = '',
                                      language = '', copyrighted = ''");
                } else {
                        db_query("INSERT INTO group_documents
                                  SET path = " . quote($new_path) . ",
                                  filename = " . quote($old_filename));
                }
                rename($path, $data.$new_path);
        }
        return $new_path;
}

// Upgrade a directory, and if it was renamed, fix its contents'
// database records to point to the new path
function document_upgrade_dir($path, $data)
{
        if ($data == 'document') {
                $table = 'document';
        } else {
                $table = 'group_documents';
        }

        $db_path = trim_path($path);
        $new_path = document_upgrade_file($path, $data);
        if ($new_path) {
                // Directory was renamed - need to update contents' entries
                db_query("UPDATE $table
                          SET path = CONCAT(".quote($new_path).',
                                SUBSTRING(path FROM '. (1+strlen($db_path)) .'))
                          WHERE path LIKE '.quote("$db_path%"));
        }
}


// Remove the first component from beginning of $path, return the rest starting with '/'
function trim_path($path)
{
        return preg_replace('|^[^/]*/|', '/', $path);
}


// Upgrades 'group_documents' table and encodes all filenames to be pure ASCII
// Database selected should be the current course database
function encode_group_documents($course_code, $group_id, $secret_directory)
{
        $cwd = getcwd();
        chdir($GLOBALS['webDir'].'courses/'.$course_code.'/group');
	if (is_dir($secret_directory)) {
	        traverseDirTree($secret_directory, 'document_upgrade_file', 'document_upgrade_dir', $secret_directory);
	} else {
		mkdir($secret_directory, '0775');
        chdir($cwd);
}


// Upgrades 'document' table and encodes all filenames to be pure ASCII
// Database selected should be the current course database
function encode_documents($course_code)
{
        $cwd = getcwd();
        chdir($GLOBALS['webDir'].'courses/'.$course_code);
        traverseDirTree('document', 'document_upgrade_file', 'document_upgrade_dir', 'document');
        chdir($cwd);
}


// -----------------------------------------------------------
// generic function to traverse the directory tree depth first
// -----------------------------------------------------------
function traverseDirTree($base, $fileFunc, $dirFunc, $data) {
        $subdirectories = opendir($base);
        // First process all directories
        while (($subdirectory = readdir($subdirectories)) !== false){
                $path = $base.'/'.$subdirectory;
                if ($subdirectory != '.' and $subdirectory != '..' and is_dir($path)) {
                        traverseDirTree($path, $fileFunc, $dirFunc, $data);
                        $dirFunc($path, $data);
                }
        }
        // Then process all files
        rewinddir($subdirectories);
        while (($filename = readdir($subdirectories)) !== false){
                $path = $base.'/'.$filename;
                if (is_file($path)) {
                        $fileFunc($path, $data);
                }
        }
        closedir($subdirectories);
}



// -------------------------------------
// function for upgrading video files
// -------------------------------------
function upgrade_video($file, $id, $code)
{
	global $webDir, $langGroupNone, $langWarnVideoFile;

	$fileName = trim($file);
        $fileName = replace_dangerous_char($fileName);
        $fileName = add_ext_on_mime($fileName);
	$fileName = php2phps($fileName);
        $safe_filename = date("YmdGis")."_".randomkeys('3').".".get_file_extention($fileName);
	$path_to_video = $webDir.'video/'.$code.'/';
        if (rename($path_to_video.$file, $path_to_video.$safe_filename)) {
        	db_query("UPDATE video SET path = '$safe_filename'
	        	WHERE id = '$id'", $code);
	} else {
		echo "$langWarnVideoFile $path_to_video.$file $langGroupNone!<br>";
                db_query("DELETE FROM video WHERE id = '$id'", $code);
        }
}



