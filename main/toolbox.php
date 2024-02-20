<?php
/* ========================================================================
 * Open eClass 3.6
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2017  Greek Universities Network - GUnet
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

/*
 * Course category / toolbox search page
 */

require_once '../include/baseTheme.php';
require_once 'modules/sharing/sharing.php';
require_once 'modules/auth/login_form.inc.php';
require_once 'modules/search/indexer.class.php';

load_js('select2');

$t = new Template("template/$theme/toolbox");
$t->set_file('main', 'toolbox.html');
$t->set_block('main', 'resultListBlock', 'resultList');
$t->set_block('main', 'welcomeBlock', 'welcome');
$t->set_block('main', 'statsBlock', 'stats');
$t->set_block('main', 'footerBlock', 'footer');
$t->set_block('resultListBlock', 'resultBlock', 'result');
$t->set_block('resultBlock', 'resultMetaBlock', 'resultMeta');
$t->set_block('main', 'selectFieldBlock', 'selectField');
$t->set_block('selectFieldBlock', 'selectOptionBlock', 'selectOption');
$t->set_var('URL_PATH', $urlAppend);
$eclass_stud_reg = get_config('eclass_stud_reg');
if (get_config('user_registration') and $eclass_stud_reg) {
    if ($eclass_stud_reg == 2) {
        $t->set_var('registerModalTitle', $langRegister);
        $t->set_var('registrationUrl', $urlAppend . 'modules/auth/newuser.php');
        $t->set_var('FIELD_GIVENNAME', 'givenname_form');
        $t->set_var('FIELD_SURNAME', 'surname_form');
        $t->set_var('FIELD_USERNAME', 'uname');
        $t->set_var('FIELD_EMAIL', 'email');
    } else {
        $t->set_var('registerModalTitle', $langUserAccountInfo1);
        $department = Database::get()->querySingle('SELECT id FROM hierarchy
            WHERE allow_user = 1 ORDER BY lft LIMIT 1')->id;
        $t->set_var('registrationUrl', $urlAppend . 'modules/auth/formuser.php?auth=');
        $t->set_var('FIELD_GIVENNAME', 'givenname');
        $t->set_var('FIELD_SURNAME', 'surname');
        $t->set_var('FIELD_USERNAME', 'username');
        $t->set_var('FIELD_EMAIL', 'usermail');
        $t->set_block('main', 'registerPasswordBlock', 'EXTRA_FIELDS');
        $t->set_var('EXTRA_FIELDS', "
            <input type='hidden' name='usercomment' value='Toolbox Registration Request'>
            <input type='hidden' name='department' value='$department'>
            <input type='hidden' name='localize' value='$language'>");
    }
} else {
    $t->set_block('main', 'registerLinkBlock', 'delete');
    $t->set_block('main', 'registerModalBlock', 'delete');
}
$t->set_var('TOOLBOX_PATH', $urlAppend . "template/$theme/toolbox/");
$t->set_var('template_base', $urlAppend . 'template/' . $theme);
$t->set_var('PAGE_TITLE', q($siteName));
$t->set_var('COPYRIGHT', 'Open eClass © 2003-' . date('Y'));
$t->set_var('TERMS_URL', $urlAppend .'info/terms.php');
$t->set_var('LANG_TERMS', $langUsageTerms);
$t->set_var('FAVICON_PATH', $urlAppend . 'template/favicon/favicon.ico');
$t->set_var('ICON_PATH', $urlAppend . 'template/favicon/openeclass_128x128.png');
$t->set_var('noAccountPleaseRegister',
    sprintf($langNoAccountPleaseRegister, '<a class="registerModal">', '</a>'));
$t->set_var('alreadyHaveAccount',
    sprintf($langAlreadyHaveAccount, '<a class="loginModal">', '</a>'));
$t->set_var('loginForm', login_form('toolbox'));
$t->set_var('toolboxTitle', q(getSerializedMessage(get_config('toolbox_title', $langEclass))));
$t->set_var('container', 'container');

if ($messages = Session::getMessages()) {
    $t->set_var('EXTRA_MSG', "<div class='row'><div class='col-xs-12'>$messages</div></div>");
} elseif (Session::has('login_warning')) {
    $t->set_var('EXTRA_MSG', "<div class='row'><div class='col-xs-12'>" . Session::get('login_warning') . '</div></div>');
}

$lang_select = "<div class='dropdown'>
  <span class='dropdown-toggle' role='button' id='dropdownMenuLang' data-toggle='dropdown'>
      <span class='fa fa-globe'></span><span class='sr-only'>$langChooseLang</span>
  </span>
  <ul class='dropdown-menu dropdown-menu-right' role='menu' aria-labelledby='dropdownMenuLang'>";
foreach ($session->active_ui_languages as $code) {
    $class = ($code == $session->language)? ' class="active"': '';
    $lang_select .=
        "<li role='presentation'$class>
            <a role='menuitem' tabindex='-1' href='$_SERVER[SCRIPT_NAME]?localize=$code'>" .
                q($native_language_names_init[$code]) . "</a></li>";
}
$lang_select .= "</ul></div>";
$t->set_var('LANG_SELECT', $lang_select);

$msgs = array('langSearch', 'langRegister', 'langLogin', 'langName',
    'langSurname', 'langUsername', 'langEmail', 'langPass', 'langSubmit',
    'langConfirmation', 'langSearchForm', 'langSearchResults',
    'langSearchFreeText', 'langSearchFreeTextPlaceholder');
foreach ($msgs as $msg) {
    $t->set_var($msg, q($GLOBALS[$msg]));
}
$t->set_var('langSelectFromMenu', js_escape($langSelectFromMenu));
if ($uid) {
    $t->set_var('loginLogout', "<form method='post' action='{$urlAppend}modules/auth/logout.php'>
        <input type='hidden' name='token' value='$_SESSION[csrf_token]'>" .
        q($_SESSION['givenname'] . ' ' . $_SESSION['surname']) .
        " &nbsp; <a class='logout-button' href='{$urlAppend}modules/auth/logout.php'>$langLogout</a></form>");
    $t->set_block('main', 'loginModalsBlock', 'delete');
} else {
    $t->set_var('loginModal', 'loginModal');
    $t->set_var('loginLogout', "$langLogIn / $langRegister");
    $t->set_var('langRemindPass', $lang_remind_pass);
    $t->set_var('lostPassUrl', $urlAppend . 'modules/auth/lostpass.php');
    if (Session::has('login_error')) {
        $t->set_var('loginError', '<div class="alert alert-danger">' . Session::get('login_error') . '</div>');
        $head_content .= '<script>$(function () { $("#loginModalContent").modal("toggle"); })</script>';
    } elseif (Session::has('login-details')) {
        $loginDetails = Session::get('login-details');
        $t->set_var('givennameValue', $loginDetails['givenname_form']);
        $t->set_var('surnameValue', $loginDetails['surname_form']);
        $t->set_var('emailValue', $loginDetails['email']);
        $t->set_var('usernameValue', $loginDetails['uname']);
        if (Session::has('email-correct')) {
            $t->set_var('recoveryWarning', "<div class='alert alert-info'>$langRegisteredUserAlreadyExists</div>");
            $head_content .= '<script>$(function () { $("#lostPassModalContent").modal("toggle"); })</script>';
        } else {
            if (Session::has('username-exists')) {
                $t->set_var('registerError', "<div class='alert alert-danger'>$langMultiRegUsernameError</div>");
            } elseif (Session::has('registration-errors')) {
                $t->set_var('registerError', "<div class='alert alert-danger'>" .
                    implode('<br>', Session::get('registration-errors')) . "</div>");
            }
            $head_content .= '<script>$(function () { $("#registerModalContent").modal("toggle"); })</script>';
        }
    }
}

$theme_id = isset($_SESSION['theme_options_id']) ? $_SESSION['theme_options_id'] : get_config('theme_options_id');
if ($theme_id) {
    $theme_options = Database::get()->querySingle("SELECT * FROM theme_options WHERE id = ?d", $theme_id);
    $theme_options_styles = unserialize($theme_options->styles);
    $urlThemeData = $urlAppend . 'courses/theme_data/' . $theme_id;

    $styles_str = '';
    if (!empty($theme_options_styles['bgColor']) || !empty($theme_options_styles['bgImage'])) {
        $background_type = "";
        if (isset($theme_options_styles['bgType']) && $theme_options_styles['bgType'] == 'stretch') {
            $background_type .= "background-size: 100% 100%;";
        } elseif(isset($theme_options_styles['bgType']) && $theme_options_styles['bgType'] == 'fix') {
            $background_type .= "background-size: 100% 100%;background-attachment: fixed;";
        }
        $bg_image = isset($theme_options_styles['bgImage']) ? " url('$urlThemeData/$theme_options_styles[bgImage]')" : "";
        $bg_color = isset($theme_options_styles['bgColor']) ? $theme_options_styles['bgColor'] : "";
        $styles_str .= "body{background: $bg_color$bg_image;$background_type}";
    }
    if (isset($theme_options_styles['fluidContainerWidth'])) {
        $t->set_var('container', 'container-fluid');
        $styles_str .= ".container-fluid {max-width:$theme_options_styles[fluidContainerWidth]px}";
    }
    if (isset($theme_options_styles['openeclassBanner'])){
        $styles_str .= "#openeclass-banner {display: none;}";
    }
    if (!empty($theme_options_styles['leftNavBgColor'])) {
        $rgba_no_alpha = explode(',', preg_replace(['/^.*\(/', '/\).*$/'], '', $theme_options_styles['leftNavBgColor']));
        $rgba_no_alpha[3] = '1';
        $rgba_no_alpha = 'rgba(' . implode(',', $rgba_no_alpha) . ')';
        $styles_str .= "#background-cheat-leftnav, #bgr-cheat-header, #bgr-cheat-footer{background:$theme_options_styles[leftNavBgColor];} @media(max-width: 992px){#leftnav{background:$rgba_no_alpha;}}";
    }
    if (!empty($theme_options_styles['linkColor'])) {
        $styles_str .= "a {color: $theme_options_styles[linkColor];}";
    }
    if (!empty($theme_options_styles['linkHoverColor'])) {
        $styles_str .= "a:hover, a:focus {color: $theme_options_styles[linkHoverColor];}";
    }
    if (!empty($theme_options_styles['leftSubMenuFontColor'])) {
        $styles_str .= "#leftnav .panel a {color: $theme_options_styles[leftSubMenuFontColor];}";
    }
    if (!empty($theme_options_styles['leftSubMenuHoverBgColor'])) {
        $styles_str .= "#leftnav .panel a.list-group-item:hover{background: $theme_options_styles[leftSubMenuHoverBgColor];}";
    }
    if (!empty($theme_options_styles['leftSubMenuHoverFontColor'])) {
        $styles_str .= "#leftnav .panel a.list-group-item:hover{color: $theme_options_styles[leftSubMenuHoverFontColor];}";
    }
    if (!empty($theme_options_styles['leftMenuFontColor'])) {
        $styles_str .= "#leftnav .panel a.parent-menu{color: $theme_options_styles[leftMenuFontColor];}";
    }
    if (!empty($theme_options_styles['leftMenuBgColor'])) {
        $styles_str .= "#leftnav .panel a.parent-menu{background: $theme_options_styles[leftMenuBgColor];}";
    }
    if (!empty($theme_options_styles['leftMenuHoverFontColor'])) {
        $styles_str .= "#leftnav .panel .panel-heading:hover {color: $theme_options_styles[leftMenuHoverFontColor];}";
    }
    if (!empty($theme_options_styles['leftMenuSelectedFontColor'])) {
        $styles_str .= "#leftnav .panel a.parent-menu:not(.collapsed){color: $theme_options_styles[leftMenuSelectedFontColor];}";
    }

    $t->set_var('EXTRA_CSS', "<style>$styles_str</style>");
}

if (isset($theme_options_styles['imageUpload'])) {
    $t->set_var('logo_img', "$urlThemeData/$theme_options_styles[imageUpload]");
} else {
    $t->set_var('logo_img', $themeimg.'/eclass-new-logo.png');
}
if (isset($theme_options_styles['imageUploadSmall'])) {
    $t->set_var('logo_img_small', "$urlThemeData/$theme_options_styles[imageUploadSmall]");
} else {
    $t->set_var('logo_img_small', $themeimg.'/logo_eclass_small.png');
}

if ($footer = get_config('toolbox_footer_' . $session->language)) {
    $t->set_var('FOOTER', $footer);
    $t->parse('footer', 'footerBlock', false);
}

if (get_config('enable_social_sharing_links')) {
    $t->set_var('socialSharingLinks',
        print_sharing_links($urlServer . 'main/toolbox.php', $siteName));
}

if (get_config('ext_analytics_enabled') and $html_footer = get_config('ext_analytics_code')) {
    $t->set_var('HTML_FOOTER', $html_footer);
}

$searching = false;
$valTitles = $catTitles = $catNames = array();
$t->set_var('formAction', $urlAppend . 'main/toolbox.php');
$categories = Database::get()->queryArray("SELECT * FROM category WHERE active = 1 ORDER BY ordering, id");
foreach ($categories as $category) {
    $catName = 'cat' . $category->id;
    $catNames[$category->id] = $catName;
    $catTitles[$category->id] = $catTitle = q(getSerializedMessage($category->name));
    if ($category->searchable) {
        if (isset($_GET[$catName])) {
            $searching = true;
        }
        $t->set_var('selectFieldLabel', $catTitle);
        $t->set_var('selectFieldName', $catName . '[]');
        $t->set_var('selectFieldId', 'modules' . $catName);
    }
    $values = Database::get()->queryArray("SELECT * FROM category_value
          WHERE category_id = ?d AND active = 1
          ORDER BY ordering, id", $category->id);
    foreach ($values as $value) {
        $valTitles[$value->id] = $valTitle = q(getSerializedMessage($value->name));
        if ($category->searchable) {
            $t->set_var('selectOptionTitle', $valTitle);
            $t->set_var('selectOptionValue', $value->id);
            if (isset($_GET[$catName]) and in_array($value->id, $_GET[$catName])) {
                $t->set_var('selectOptionSelected', 'selected');
            } else {
                $t->set_var('selectOptionSelected', '');
            }
            $t->parse('selectOption', 'selectOptionBlock', true);
        }
    }

    if ($category->searchable) {
        $t->parse('selectField', 'selectFieldBlock', true);
    }
    $t->set_var('selectOption', '');
}

if (isset($_GET['search_terms'])) {
    $search_terms = trim($_GET['search_terms']);
} else {
    $search_terms = null;
}
if (isset($_GET['catlang']) or isset($_GET['department']) or $search_terms) {
    $searching = true;
}
$t->set_var('selectFieldLabel', $langLanguage);
$t->set_var('selectFieldName', 'catlang[]');
foreach ($session->active_ui_languages as $langCode) {
    $t->set_var('selectOptionTitle', q($langNameOfLang[langcode_to_name($langCode)]));
    $t->set_var('selectOptionValue', $langCode);
    if (isset($_GET['catlang']) and in_array($langCode, $_GET['catlang']) or
        (!$searching and $langCode == $session->language)) {
        $t->set_var('selectOptionSelected', 'selected');
    } else {
        $t->set_var('selectOptionSelected', '');
    }
    $t->parse('selectOption', 'selectOptionBlock', true);
}
$t->parse('selectField', 'selectFieldBlock', true);

// Free text search in index
$course_ids = array();
if (get_config('enable_search')) {
    if ($search_terms) {
        // search in the index
        $idx = new Indexer();
        $hits = $idx->multiSearchRaw(CourseIndexer::buildQueries($_GET));
        $course_ids = array_map(function ($hit) {
            return $hit->pkid;
        }, $hits);
        $t->set_var('search_terms', q($search_terms));
    }
} else {
    $t->set_block('main', 'searchBlock', 'delete');
}

if ($searching) {
    if (isset($_GET['department'])) {
        $sql_department = 'course_department,';
    } else {
        $sql_department = '';
    }
    $query = 'SELECT DISTINCT(a.course_id), a.title, a.code, a.description, a.lang, a.visible'
             . " FROM $sql_department ("
             . '  SELECT'
             . '   c.id AS course_id, c.title, c.code, c.description, c.lang, c.visible';
    $where = array();
    $args = array();

    foreach ($catNames as $catId => $catName) {
        if (isset($_GET[$catName])) {
            $query .= ', ' . $catName . '.category_value_id AS ' . $catName;
        }
    }

    $query .= ' FROM course c ';

    if (count($course_ids)) {
        $where[] = 'course_id IN ' . placeholders($course_ids);
        $args[] = $course_ids;
    } elseif ($search_terms) {
        // Did a free-text search and found no results - suppress further searching
        $where[] = 'false';
    }

    foreach ($catNames as $catId => $catName) {
        if (isset($_GET[$catName])) {
            $query .= ' LEFT JOIN course_category ' . $catName . ' ON (c.id = ' . $catName . '.course_id AND ' . $catName . '.category_value_id IN (SELECT id FROM category_value WHERE category_id = ' . $catId . '))';
            $where[] = 'a.' . $catName . ' IN ' . placeholders($_GET[$catName]);
            $args[] = $_GET[$catName];
        }
    }

    // lang searching
    if (isset($_GET['catlang'])) {
        $where[] = "a.lang IN " . placeholders($_GET['catlang'], 's');
        $args[] = $_GET['catlang'];
    }

    $query .= ' ) AS a WHERE ' . implode(' AND ', $where);

    if (isset($_GET['department'])) {
        $query .= ' AND a.course_id = course_department.course AND course_department.department = ?d';
        $args[] = $_GET['department'];
    }

    $query .= ' ORDER BY title';

    $courses = Database::get()->queryArray($query, $args);
    foreach ($courses as $course) {
        $t->set_var('resultLink', $urlAppend . 'courses/' . $course->code . '/');
        $t->set_var('resultTitle', q($course->title));
        $t->set_var('resultContent', standard_text_escape($course->description));
        $t->set_var('resultMeta', '');
        if ($course->visible != COURSE_OPEN and !$uid) {
            $t->set_var('resultExtra', ' class="course-not-open"');
        }

        Database::get()->queryFunc('SELECT category_id, category_value_id
            FROM course_category
            INNER JOIN category_value ON course_category.category_value_id = category_value.id
            INNER JOIN category ON category_value.category_id = category.id
            WHERE course_id = ?d
            ORDER BY category.ordering, category_value.ordering',
            function ($item) use ($t, $valTitles, $catTitles) {
                $t->set_var('resultMetaIcon', 'fa-plus-circle');
                $t->set_var('resultMetaLabel', q($catTitles[$item->category_id] . ': ' . $valTitles[$item->category_value_id]));
                $t->parse('resultMeta', 'resultMetaBlock', true);
            }, $course->course_id);

        $t->parse('result', 'resultBlock', true);
    }
    $c = count($courses);
    $entriesFound = ($c == 0)? $langNoEntriesFound:
        sprintf($c == 1? $langNumEntryFound : $langNumEntriesFound, "<b>$c</b>");
    $t->set_var('resultNumEntriesFound', $entriesFound);
    $t->parse('resultList', 'resultListBlock', false);
} else {
    $t->set_var('infoAbout', standard_text_escape(getSerializedMessage(get_config('toolbox_intro', $langInfoAbout))));
    $t->parse('welcome', 'welcomeBlock', false);

    $totalCourses = Database::get()->querySingle('SELECT COUNT(*) AS totalCourses
        FROM course WHERE visible <> ' . COURSE_INACTIVE)->totalCourses;
    $totalUsers = Database::get()->querySingle('SELECT COUNT(*) AS totalUsers
        FROM user WHERE status <> ' . USER_GUEST . ' AND
            expires_at > ' . DBHelper::timeAfter())->totalUsers;
    $totalVisits = 9000;
    $t->set_var('langTotalCourses', 'Total Exercises');
    $t->set_var('langTotalUsers', 'Total Users');
    $t->set_var('langTotalVisits', 'Total Visits');
    $t->set_var('totalCourses', $totalCourses);
    $t->set_var('totalUsers', $totalUsers);
    $t->set_var('totalVisits', $totalVisits);
    $t->parse('stats', 'statsBlock', false);
}

if (!$uid) {
    $urlAppendLen = strlen($urlAppend);
    $head_content .= "<script>var urlCourse;
    $(function () {
        $('a.course-not-open').click(function (e) {
            urlCourse = $(this).attr('href').substr($urlAppendLen);
            e.preventDefault();
            $('.nextUrl').attr('value', urlCourse);
            $('#loginModalContent').modal();
        });
    });</script>";
}

$t->set_var('HEAD_EXTRAS', $head_content);
$t->pparse('Output', 'main');

function placeholders($array, $type = 'd') {
    $c = count($array);
    return '(' . implode(', ', array_fill(0, $c, '?' . $type)) . ')';
}
