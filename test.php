<?php
require_once('TagPicker.php');
use \TagPicker as TagPicker;

$a = new TagPicker(TagPicker::LANG_ENUS, TagPicker::OUTPUT_WPRESS);
$a->getTags('test.txt', 15);
