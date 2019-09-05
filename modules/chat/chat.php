<?php

/* ========================================================================
 * Open eClass 3.0
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2014  Greek Universities Network - GUnet
 * A full copyright notice can be read in "/info/copyright.txt".
 * For a full list of contributors, see "credits.txt".
 *
 * Open eClass is an open platform distributed in the hope that it will
 * be useful (without any warranty), under the terms of the GNU (General
 * Public License) as published by the Free Software Foundation.
 * The full license can be read in "/info/license/license_gpl.txt".
 *
 * Contact address: GUnet Asynchronous eLearning Group,
 *                  Network Operations Center, University of Athens,
 *                  Panepistimiopolis Ilissia, 15784, Athens, Greece
 *                  e-mail: info@openeclass.org
 * ======================================================================== */

/**
 * @file chat.php
 * @brief Main script for chat module
 */
$require_current_course = TRUE;
$require_login = TRUE;
$require_help = true;
$helpTopic = 'chat';

require_once '../../include/baseTheme.php';
require_once 'functions.php';

$coursePath = $webDir . '/courses/';
$conference_id = $_GET['conference_id'];
$conference_activity = false;
$conference_agent = false;
$agent_id = false;
$q = Database::get()->querySingle("SELECT status, chat_activity, agent_created, agent_id FROM conference WHERE conf_id = ?d AND course_id = ?d", $conference_id, $course_id);
if ($q) { // additional security
    $conference_status = $q->status;
    $conference_activity = $q->chat_activity;
    $conference_agent = $q->agent_created;
    $agent_id = $q->agent_id;
} else {
    Session::Messages($langForbidden, "alert-danger");
    redirect_to_home_page();
}
if (!is_valid_chat_user($uid, $conference_id, $conference_status)) {
    Session::Messages($langForbidden, "alert-danger");
    redirect_to_home_page();
}
if (!is_valid_activity_user($conference_activity, $conference_agent)) {
    Session::Messages($langForbidden, "alert-danger");
    redirect_to_home_page();
}

  $fileChatName = $coursePath . $course_code . '/'. $conference_id. '_chat.txt';
  $tmpArchiveFile = $coursePath . $course_code . '/'. $conference_id. '_tmpChatArchive.txt';

  $nick = uid_to_name($uid);

// How many lines to show on screen
  define('MESSAGE_LINE_NB', 40);
// How many lines to keep in temporary archive
// (the rest are in the current chat file)
  define('MAX_LINE_IN_FILE', 80);

  if ($GLOBALS['language'] == 'el') {
      $timeNow = date("d-m-Y / H:i", time());
  } else {
      $timeNow = date("Y-m-d / H:i", time());
  }

  if (!file_exists($fileChatName)) {
      $fp = fopen($fileChatName, 'w') or die('<center>$langChatError</center>');
      fclose($fp);
  }

/* * ** The following is added for statistics purposes ** */
require_once 'include/action.php';
$action = new action();
$action->record(MODULE_ID_CHAT);
/* * *********************************** */

$toolName = $langChat;
// guest user not allowed
if (check_guest()) {
    $tool_content .= "<div class='alert alert-danger'>$langNoGuest</div>";
    draw($tool_content, 2);
}

$head_content .= '<script type="text/javascript">
    function prepare_message() {
            document.chatForm.chatLine.value=document.chatForm.msg.value;
            document.chatForm.msg.value = "";
            document.chatForm.msg.focus();
            return true;
    }
    setTimeout(function(){
        $( "#iframe" ).attr( "src", function ( i, val ) { return val; });
    }, 2000);        
</script>';

if (!$conference_activity) {
    if (isset($_REQUEST['unit'])) {
        $save_link = "../units/view.php?course=$course_code&amp;res_type=chat_actions&amp;unit=$_REQUEST[unit]&amp;store=true&amp;conference_id=$conference_id&amp;" . generate_csrf_token_link_parameter();
        $back_link = "../units/index.php?course=$course_code&amp;id=$_REQUEST[unit]";
        $wash_link = "../units/view.php?course=$course_code&amp;res_type=chat_actions&amp;unit=$_REQUEST[unit]&amp;reset=true&amp;conference_id=$conference_id&amp;" . generate_csrf_token_link_parameter();
    } else {
        $save_link = "messageList.php?course=$course_code&amp;store=true&amp;conference_id=$conference_id&amp;" . generate_csrf_token_link_parameter();
        $back_link = "index.php";
        $wash_link = "messageList.php?course=$course_code&amp;reset=true&amp;conference_id=$conference_id&amp;" . generate_csrf_token_link_parameter();
    }
    $tool_content .= action_bar(array(
        array('title' => $langSave,
            'url' => $save_link,
            'icon' => 'fa-plus-circle',
            'level' => 'primary-label',
            'button-class' => 'btn-success',
            'link-attrs' => "target='messageList'",
            'show' => $is_editor
        ),
        array('title' => $langBack,
            'url' => $back_link,
            'icon' => 'fa-reply',
            'level' => 'primary-label'
        ),
        array('title' => $langWash,
            'url' => $wash_link,
            'icon' => 'fa-trash',
            'level' => 'primary',
            'link-attrs' => "target='messageList'",
            'show' => $is_editor
        )
    ));

    if (isset($_REQUEST['unit'])) {
        $action_form = "../units/view.php?course=$course_code&amp;res_type=chat_actions&amp;unit=$_REQUEST[unit]";
        $iframe_file = "../units/view.php?course=$course_code&amp;res_type=chat_actions&amp;unit=$_REQUEST[unit]&amp;conference_id=$conference_id";
    } else {
        $action_form = "messageList.php";
        $iframe_file = "messageList.php?course=$course_code&amp;conference_id=$conference_id";
    }
    $tool_content .= "<div class='alert alert-info'>$langTypeMessage</div>
       <div class='row'><div class='col-sm-12'><div class='form-wrapper'>
       <form name='chatForm' action='$action_form' method='POST' target='messageList' onSubmit='return prepare_message();'>
       <input type='hidden' name='course' value='$course_code'>
       <input type='hidden' name='conference_id' value='$conference_id'>
       <fieldset>
        <div class='col-xs-12'>
            <div class='input-group'>
              <input type='text' name='msg' size='80' class='form-control'>
              <input type='hidden' name='chatLine'>
              <span class='input-group-btn'>
                <input class='btn btn-primary' type='submit' value='&raquo;'>
              </span>
            </div>
            <div class='embed-responsive embed-responsive-4by3 margin-top-fat'>
              <iframe class='embed-responsive-item' id='iframe' src='$iframe_file' name='messageList' style='border: 1px solid #CAC3B5;width:100%;overflow-x: hidden;'></iframe>
            </div>       
        </div>   
       </fieldset>
       " . generate_csrf_token_form_field() . "
       </form></div></div></div>";
} else {
    if ($is_editor && isset($_GET['create_agent'])) {
        $agent_id = colmooc_create_agent($conference_id);
        if ($agent_id) {
            Database::get()->querySingle("UPDATE conference SET agent_id = ?d WHERE conf_id = ?d", $agent_id, $conference_id);
            $conference_agent = true;
        }
    }

    $tool_content .= action_bar(array(
        array('title' => $langCreateAgent,
            'url' => "chat.php?conference_id=" . $conference_id . "&create_agent=1",
            'icon' => 'fa-plus-circle',
            'level' => 'primary-label',
            'button-class' => 'btn-success',
            'show' => $is_editor && !$conference_agent
        ),
        array('title' => $langEditAgent,
            'url' => "chat.php?conference_id=" . $conference_id . "&edit_agent=1",
            'icon' => 'fa-plus-circle',
            'level' => 'primary-label',
            'button-class' => 'btn-success',
            'show' => $is_editor && $conference_agent && $agent_id
        ),
        array('title' => $langBack,
            'url' => "index.php",
            'icon' => 'fa-reply',
            'level' => 'primary-label'
        )
    ));

    if ($is_editor && isset($_GET['create_agent']) && $agent_id) {
        // Redirect teacher to colMOOC editor with agent_id & teacher_id parameters
        $colmooc_url = $colmoocapp->getParam(ColmoocApp::BASE_URL)->value() . "/colmoocapi/editor/?agent_id=" . $agent_id . '&teacher_id=' . $uid;
        redirect_to_home_page($colmooc_url, true);
    } else if ($is_editor && isset($_GET['edit_agent']) && $agent_id) {
        // Redirect teacher to colMOOC editor with agent_id & teacher_id parameters
        $colmooc_url = $colmoocapp->getParam(ColmoocApp::BASE_URL)->value() . "/colmoocapi/editor/?agent_id=" . $agent_id . '&teacher_id=' . $uid;
        redirect_to_home_page($colmooc_url, true);
    } else if ($is_editor && isset($_GET['create_agent']) && !$agent_id) {
        $tool_content .= "<div class='alert alert-danger'>" . $langColmoocCreateAgentFailed . "</div>";
    } else if ($is_editor && !isset($_GET['create_agent']) && !isset($_GET['edit_agent'])) {
        $tool_content .= "<div class='alert alert-info'>" . $langColMoocAgentCreateOrEdit . "</div>";
    }

    if (!$is_editor) {
        list($sessionId, $sessionToken) = colmooc_register_student($conference_id); // STEP 2
        if ($sessionId && $sessionToken) {
            // Redirect student to colMOOC chat
            $colmooc_url = $colmoocapp->getParam(ColmoocApp::CHAT_URL)->value() . "/?session_id=" . $sessionId . "&amp;session_token=" . $sessionToken;
            $chatindex_url = $urlAppend . "modules/chat/index.php";
            $tool_content .= "<div class='alert alert-info'>" . $langColmoocFollowLink
                . ': <a id="studentChat" href="#" title="' . $langChat . '">' . $langChat . '</a>'
                . "</div>";
        } else {
            $tool_content .= "<div class='alert alert-info'>" . $langColmoocRegisterStudentFailed . "</div>";
        }
    }
}

load_js('screenfull/screenfull.min.js');
$head_content .= "<script>
    $(function(){
        $('.fileModal').click(function (e)
        {
            e.preventDefault();
            var fileURL = $(this).attr('href');
            var fileTitle = $(this).attr('title');
            
            // BUTTONS declare
            var bts = {};
            if (screenfull.enabled) {
                bts.fullscreen = {
                    label: '<i class=\"fa fa - arrows - alt\"></i> $langFullScreen',
                    className: 'btn-primary',
                    callback: function() {
                        screenfull.request(document.getElementById('fileFrame'));
                        return false;
                    }
                };
            }
            bts.cancel = {
                label: '$langCancel',
                className: 'btn-default'
            };
            
            bootbox.dialog({
                size: 'large',
                title: fileTitle,
                message: '<div class=\"row\">'+
                            '<div class=\"col-sm-12\">'+
                                '<div class=\"iframe-container\"><iframe id=\"fileFrame\" src=\"'+fileURL+'\"></iframe></div>'+
                            '</div>'+
                        '</div>',
                buttons: bts
            });
        });
        
        $('#studentChat').click(function (e) {
            window.open('" . $colmooc_url . "', '_blank');
            window.location.href = '" . $chatindex_url . "';
        });
    });
    </script>";

add_units_navigation(TRUE);
draw($tool_content, 2, null, $head_content);
