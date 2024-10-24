<?php
// This is a test plugin to check for the use of global variables without a prefix.
function dosomething() {
	echo 'Hello, World!';
}

function er_dosomething() {
	echo 'Hello, World!';
}

function tppg_dosomething() {
	echo 'Hello, World!';
}