<?php

define('NO_MOODLE_COOKIES', true); // session not used here

require('../../config.php');
$plugin_url = $CFG->wwwroot . '/blocks/exam_actions';

$lifetime  = 600;                                   // Seconds to cache this stylesheet

header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
header('Expires: ' . gmdate("D, d M Y H:i:s", time() + $lifetime) . ' GMT');
header('Cache-control: max_age = '. $lifetime);
header('Pragma: ');
header('Content-type: text/css; charset=utf-8');  // Correct MIME type

?>

/* CSS Tree menu styles */

.remote_courses ol.tree {
	padding: 0 0 0 30px;
	width: 90%;
}

.remote_courses li {
    position: relative;
    margin-left: -15px;
    list-style: none;
}

.remote_courses li.course {
    margin-left: -1px !important;
}

.remote_courses li.course a {
    padding-left: 5px;
    text-decoration: none;
    display: inline;
}

.remote_courses .identifier {
    color: #000;
    font-weight: bold;
    text-decoration: none;
}

.remote_courses li input {
    position: absolute;
    left: 0;
    margin-left: 0;
    opacity: 0;
    z-index: 2;
    cursor: pointer;
    height: 1em;
    width: 1em;
    top: 0;
}

.remote_courses li input + ol {
    background: url("<?php echo $plugin_url;?>/images/toggle-small-expand.png") 40px 0 no-repeat;
    margin: -0.938em 0 0 -44px; /* 15px */
    height: 1em;
}

.remote_courses li input + ol > li {
    display: none;
    margin-left: -14px !important;
    padding-left: 1px;
}

.remote_courses li label {
    background: url("<?php echo $plugin_url;?>/images/folder-horizontal.png") 15px 1px no-repeat;
    cursor: pointer;
    display: block;
    padding-left: 37px;
}

.remote_courses li input:checked + ol {
    background: url("<?php echo $plugin_url;?>/images/toggle-small.png") 40px 5px no-repeat;
    margin: -1.25em 0 0 -44px; /* 20px */
    padding: 1.563em 0 0 80px;
    height: auto;
}

.remote_courses li input:checked + ol > li {
    display: block;
    margin: 0 0 0.125em;  /* 2px */
}

.remote_courses li input:checked + ol > li:last-child {
    margin: 0 0 0.063em; /* 1px */
}
