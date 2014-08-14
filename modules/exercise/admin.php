<?php

/* ========================================================================
 * Open eClass 3.0
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2012  Greek Universities Network - GUnet
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


require_once 'exercise.class.php';
require_once 'question.class.php';
require_once 'answer.class.php';
require_once 'exercise.lib.php';

$require_current_course = true;
require_once '../../include/baseTheme.php';
require_once 'include/jscalendar/calendar.php';
require_once 'include/lib/modalboxhelper.class.php';
require_once 'include/lib/multimediahelper.class.php';

$jscalendar = new DHTML_Calendar($urlServer . 'include/jscalendar/', $language, 'calendar-blue2', false);
$head_content = $jscalendar->get_load_files_code();
ModalBoxHelper::loadModalBox();

$nameTools = $langExercices;
$navigation[] = array('url' => "index.php?course=$course_code", 'name' => $langExercices);

// picture path
$picturePath = "courses/$course_code/image";
// the 4 types of answers
$aType = array($langUniqueSelect, $langMultipleSelect, $langFillBlanks, $langMatching, $langTrueFalse, $langFreeText);

if (!$is_editor) {
    Session::set_flashdata($langNotAllowed, 'alert1');
    redirect_to_home_page('modules/exercise/index.php?course='.$course_code);
}

/* * ************************* */
/*  stripslashes POST data  */
/* * ************************* */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach ($_POST as $key => $val) {
        if (is_string($val)) {
            $_POST[$key] = stripslashes($val);
        } elseif (is_array($val)) {
            foreach ($val as $key2 => $val2) {
                $_POST[$key][$key2] = stripslashes($val2);
            }
        }
        $GLOBALS[$key] = $_POST[$key];
    }
}

// construction of the Exercise object
$objExercise = new Exercise();
if (isset($_GET['exerciseId'])) {
    $exerciseId = $_GET['exerciseId'];   
    $objExercise->read($exerciseId);
    $nbrQuestions = $objExercise->selectNbrQuestions();
}
// if cancelling an exercise
if (isset($_POST['cancelExercise'])) {
    // goes back to the exercise list
    redirect_to_home_page("modules/exercise/index.php?course=$course_code");
}

// if cancelling question creation/modification or cancelling answer creation/modification
if (isset($_POST['cancelQuestion']) || isset($_POST['cancelAnswers'])) {
    // goes back to the edit exercise or question pool
    if (isset($exerciseId)) {
        redirect_to_home_page("modules/exercise/admin.php?course=$course_code&exerciseId=$exerciseId");
    } else {
        redirect_to_home_page("modules/exercise/question_pool.php?course=$course_code");
    }
}

// intializes the Question object
if (isset($_GET['editQuestion']) || isset($_GET['newQuestion']) || isset($_GET['modifyQuestion']) || isset($_GET['modifyAnswers'])) {
    // construction of the Question object
    $objQuestion = new Question();
    // reads question data
    if (isset($_GET['editQuestion']) || isset($_GET['modifyQuestion']) || isset($_GET['modifyAnswers'])) {
        if (isset($_GET['editQuestion'])) {
            $question_id = $_GET['editQuestion'];
        } elseif (isset($_GET['modifyQuestion'])) {
            $question_id = $_GET['modifyQuestion'];
        } elseif (isset($_GET['modifyAnswers'])) {
            $question_id = $_GET['modifyAnswers'];
        }
        // if question not found
        if (!$objQuestion->read($question_id)) {
            Session::set_flashdata($langQuestionNotFound, 'alert1');
            redirect_to_home_page("modules/exercise/admin.php?course=$course_code&exerciseId=$exerciseId");
        }
        
        if (isset($_GET['editQuestion'])) {
            //clone and redirect to edit
            if (isset($_GET['clone'])) {
                //if user comes from an exercise page
                if (isset($exerciseId)) {
                    // duplicates the question
                    $new_question_id = $objQuestion->duplicate();
                    // deletes the old question from the specific exercise
                    $objQuestion->delete($exerciseId);
                    // removes the old question ID from the question list of the Exercise object
                    $objExercise->removeFromList($question_id);
                    // adds the new question ID into the question list of the Exercise object
                    $objExercise->addToList($new_question_id);
                    // construction of the duplicated Question
                    $objQuestion = new Question();
                    $objQuestion->read($new_question_id);
                    // adds the exercise ID into the exercise list of the Question object
                    $objQuestion->addToList($exerciseId);
                    // copies answers from the old qustion to the new
                    $objAnswer = new Answer($question_id);
                    $objAnswer->duplicate($new_question_id);
                    redirect_to_home_page("modules/exercise/admin.php?course=$course_code&exerciseId=$exerciseId&editQuestion=$new_question_id");
                //if the user comes from question pool    
                } else {
                    $new_question_id = $objQuestion->duplicate();
                    $objAnswer = new Answer($question_id);
                    $objAnswer->duplicate($new_question_id);
                    redirect_to_home_page("modules/exercise/admin.php?course=$course_code&editQuestion=$question_id");
                }
            }
            $nameTools = $langQuestionManagement;
            $navigation[] = array(
                'url' => (isset($exerciseId) ? "admin.php?course=$course_code&amp;exerciseId=$exerciseId" : "question_pool.php?course=$course_code&amp;exerciseId=0"), 
                'name' => (isset($exerciseId) ? $langExerciseManagement : $langQuestionPool)
            );            
            include('question_admin.inc.php');
        } elseif (isset($_GET['modifyAnswers'])) {
            $objAnswer = new Answer($question_id);            
            include('answer_admin.inc.php');
        } else {
            
            include('statement_admin.inc.php');
        }
    } else {
        $nameTools = $langNewQu;
        $navigation[] = array(
            'url' => (isset($exerciseId) ? "admin.php?course=$course_code&amp;exerciseId=$exerciseId" : "question_pool.php?course=$course_code&amp;exerciseId=0"), 
            'name' => (isset($exerciseId) ? $langExerciseManagement : $langQuestionPool)
            );
        include('statement_admin.inc.php');
    }
} else {
    if (isset($_GET['NewExercise'])) {
        $nameTools = $langNewEx;
    } elseif (isset($_GET['modifyExercise'])) {
        $nameTools = $langInfoExercise;
        $navigation[] = array('url' => "admin.php?course=$course_code&amp;exerciseId=$exerciseId", 'name' => $langExerciseManagement);
    }
    include('exercise_admin.inc.php');
    if (!isset($_GET['NewExercise']) && !isset($_GET['modifyExercise'])) {
        include('question_list_admin.inc.php');
    }
}

draw($tool_content, 2, null, $head_content);

// -----------------------------------------------
// function for displaying jscalendar
// -----------------------------------------------
function jscal_html($name, $u_date) {

    global $jscalendar;
    if (!$u_date) {
        $u_date = strftime('%Y-%m-%d %H:%M', strtotime('now -0 day'));
    }

    $cal = $jscalendar->make_input_field(
            array('showsTime' => true,
        'showOthers' => true,
        'ifFormat' => '%Y-%m-%d %H:%M'), array('style' => 'width: 15em; color: #840; background-color: #fff; border: 1px dotted #000; text-align: center',
        'name' => $name,
        'value' => $u_date));

    return $cal;
}
