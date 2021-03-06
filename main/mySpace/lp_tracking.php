<?php
/* For licensing terms, see /license.txt */
/**
 * Learning paths reporting.
 *
 * @package chamilo.reporting
 */
require_once __DIR__.'/../inc/global.inc.php';

// resetting the course id
$cidReset = true;
$from_myspace = false;
$from_link = '';
if (isset($_GET['from']) && $_GET['from'] == 'myspace') {
    $from_link = '&from=myspace';
    $this_section = SECTION_TRACKING;
} else {
    $this_section = SECTION_COURSES;
}

$session_id = isset($_REQUEST['id_session']) && !empty($_REQUEST['id_session'])
    ? intval($_REQUEST['id_session'])
    : api_get_session_id();
$export_csv = isset($_GET['export']) && $_GET['export'] == 'csv' ? true : false;
$user_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : api_get_user_id();
$courseCode = isset($_GET['course']) ? Security::remove_XSS($_GET['course']) : api_get_course_id();
$origin = api_get_origin();
$lp_id = intval($_GET['lp_id']);
$csv_content = [];
$course_info = api_get_course_info($courseCode);

if (empty($course_info) || empty($lp_id)) {
    api_not_allowed(api_get_origin() !== 'learnpath');
}
$userInfo = api_get_user_info($user_id);
$name = $userInfo['complete_name'];
$isBoss = UserManager::userIsBossOfStudent(api_get_user_id(), $user_id);

if (!api_is_platform_admin(true) &&
    !CourseManager::is_course_teacher(api_get_user_id(), $courseCode) &&
    !$isBoss &&
    !Tracking::is_allowed_to_coach_student(api_get_user_id(), $user_id) &&
    !api_is_drh() &&
    !api_is_course_tutor()
) {
    api_not_allowed(
        api_get_origin() !== 'learnpath'
    );
}

if ($origin == 'user_course') {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_COURSE_PATH).$course_info['directory'],
        'name' => $course_info['name'],
    ];
    $interbreadcrumb[] = [
        'url' => "../user/user.php?cidReq=$courseCode",
        'name' => get_lang("Users"),
    ];
} elseif ($origin == 'tracking_course') {
    $interbreadcrumb[] = [
        'url' => "../tracking/courseLog.php?cidReq=$courseCode&id_session=$session_id",
        'name' => get_lang("Tracking"),
    ];
} else {
    $interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('MySpace')];
    $interbreadcrumb[] = ['url' => 'student.php', 'name' => get_lang("MyStudents")];
    $interbreadcrumb[] = ['url' => "myStudents.php?student=$user_id", 'name' => get_lang("StudentDetails")];
    $nameTools = get_lang("DetailsStudentInCourse");
}

$interbreadcrumb[] = [
    'url' => "myStudents.php?student=$user_id&course=$courseCode&details=true&origin=$origin",
    'name' => get_lang("DetailsStudentInCourse"),
];
$nameTools = get_lang('LearningPathDetails');
$sql = 'SELECT name	FROM '.Database::get_course_table(TABLE_LP_MAIN).' 
        WHERE c_id = '.$course_info['real_id'].' AND id='.$lp_id;
$rs = Database::query($sql);
$lp_title = Database::result($rs, 0, 0);

$origin = 'tracking';

$output = require_once api_get_path(SYS_CODE_PATH).'lp/lp_stats.php';

$actions = [];
$actions[] = Display::url(
    Display::return_icon('back.png', get_lang('Back'), '', ICON_SIZE_MEDIUM),
    'javascript:history.back();'
);
$actions[] = Display::url(
    Display::return_icon('printer.png', get_lang('Print'), '', ICON_SIZE_MEDIUM),
    'javascript:window.print();'
);
$actions[] = Display::url(
    Display::return_icon('export_csv.png', get_lang('ExportAsCSV'), '', ICON_SIZE_MEDIUM),
    api_get_self().'?export=csv&'.Security::remove_XSS($_SERVER['QUERY_STRING'])
);

Display::display_header($nameTools);
echo Display::toolbarAction(
    'actions',
    [implode(PHP_EOL, $actions)]
);

$table_title = $session_id
    ? Display::return_icon('session.png', get_lang('Session')).PHP_EOL.api_get_session_name($session_id).PHP_EOL
    : PHP_EOL;
$table_title .= Display::return_icon('course.png', get_lang('Course')).PHP_EOL.$course_info['name'].PHP_EOL
    .Display::return_icon('user.png', get_lang('User')).' '.$name;

echo Display::page_header($table_title);
echo Display::page_subheader(
    Display::return_icon('learnpath.png', get_lang('ToolLearnpath')).PHP_EOL.$lp_title
);
echo $output;
Display::display_footer();
