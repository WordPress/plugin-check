<?php
function a($b){global $wpdb;$c=$_POST['x'];$wpdb->query("SELECT * FROM $wpdb->posts WHERE ID='$c'");return true;}function d($e){if(!$e){return false;}return f($e);}add_action('init','a');add_filter('content','d');class G{private $h;public function __construct(){$this->h='test';}public function i(){echo $this->h;}}

