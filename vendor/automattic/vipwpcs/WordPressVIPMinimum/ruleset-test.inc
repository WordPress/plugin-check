


<?php // Error - Squiz.WhiteSpace.SuperfluousWhitespace.
$hello = ''; $posts_not_in = ''; $title = ''; $ch = ''; $thing = ''; $descriptorspec = ''; $pipes = ''; $cwd = ''; $env = ''; $page_title = ''; $menu_title = ''; $capability = ''; $function = ''; $icon_url = ''; $position = ''; $wpdb = ''; $file = ''; $fp = ''; $test = ''; $post = ''; $bar = ''; $array = []; $query_args = []; $url = ''; $page_title = ''; $true = true; $data = ''; $group = ''; $stdClass = new stdClass(); $needle = ''; $some_var = ''; $blogid = 1; $text = ''; $category_id = 123; $foo = ''; $bar = ''; $var = ''; $wp_rewrite = ''; $count = 1; $loop = 1; $obj = ''; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- All set for VariableAnalysis checks.
// Generic.PHP.Syntax
foreach() { // Error.
}

// Generic.PHP.NoSilencedErrors
if (@in_array( $array, $needle, true )) { // Error.
    echo '@';
}

// WordPress.Security.EscapeOutput
echo $hello; // Error.
echo apply_filters( 'the_title', $title ); // Error.

// WordPress.Security.NonceVerification
function bar() {
	if ( ! isset( $_POST['test'] ) ) { // Error.
	}
}

// WordPress.Security.ValidatedSanitizedInput
if ( isset( $_POST['nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ) ) ) {
	bar( $_POST['foo2'] ); // Error x 2.
	$foo2 = isset( $_POST['foo2'] ) ?? foo( sanitize_text_field( $_POST['foo2'] ) ); // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- Ok - exclude WordPress.Security.ValidatedSanitizedInput.MissingUnslash.
}

// WordPress.Security.PluginMenuSlug
add_menu_page( $page_title, $menu_title, $capability, __FILE__, $function, $icon_url, $position ); // Warning.

// WordPress.WP.EnqueuedResources
?> <script src="http://someurl/somefile.js"></script> <?php // Error.

// WordPress.WP.PostsPerPage
$args = array( // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
	'posts_per_page' => 999, // Warning.
);
_query_posts( 'posts_per_page=999' ); // Warning.
$query_args['posts_per_page'] = 999; // Warning.

// WordPress.DateTime.RestrictedFunctions
date_default_timezone_set( 'FooBar' ); // Error.

// WordPress.DB.PreparedSQL
$b = function () { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
	global $wpdb;
	$listofthings = wp_cache_get( 'foo' );
	if ( ! $listofthings ) {
		$foo = "column = 'test'";

		$listofthings = $wpdb->query( 'SELECT something FROM somewhere WHERE ' . $foo ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Error.
		wp_cache_set( 'foo', $listofthings );
	}
};

// WordPress.DB.DirectDatabaseQuery
$baz = $wpdb->get_results( $wpdb->prepare( 'SELECT X FROM Y ' ) ); // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- Warning x 2.

// WordPress.DB.SlowDBQuery
$test = [
	'tax_query' => [], // Warning.
];
new WP_Query( array(
	'meta_query' => [], // Warning.
	'meta_key' => 'foo', // Warning.
	'meta_value' => 'bar', // Warning.
) );

// WordPress.WP.GlobalVariablesOverride
$GLOBALS['wpdb'] = 'test'; // Error.

// Universal.Operators.StrictComparisons
if ( true == $true ) { // Warning.
}

// Generic.CodeAnalysis.AssignmentInCondition
if ( $test = get_post( $post ) ) { // Warning.
}

// WordPress.PHP.StrictInArray
if ( true === in_array( $foo, $bar ) ) { // Warning.
}

// WordPress.Functions.DontExtract
extract( $foobar ); // Error.

// WordPress.WP.CronInterval
function my_add_weekly( $schedules ) {
	$schedules['every_6_mins'] = array(
		'interval' => 360,
		'display' => __( 'Once every 6 minutes' )
	);
	return $schedules;
}
add_filter( 'cron_schedules', 'my_add_weekly'); // Warning.

// Generic.NamingConventions.ConstructorName
class TestClass extends MyClass
{
    function __construct() {
        parent::MYCLASS(); // Error.
        parent::__construct();
    }
}
class OldClass
{
    function OldClass() // Error.
    {
    }
}

// Generic.NamingConventions.ConstructorName
class TestClass extends MyClass {
    function TestClass() { // Error.
        parent::MyClass(); // Error.
        parent::__construct();
    }
}

// Generic.PHP.DisallowShortOpenTag
?> <?= esc_html( $var ); // Error.

// Squiz.PHP.CommentedOutCode <!-- Warning. -->
// if (empty($this)) {echo 'This is will not work';}

// Squiz.PHP.Eval
eval('$var = 4;'); // Error + Message.

// WordPress.PHP.DiscouragedPHPFunctions
base64_decode( 'VGhpcyBpcyBhbiBlbmNvZGVkIHN0cmluZw=='); // Ok - exclude obfuscation group.
base64_encode( 'This is an encoded string' ); // Ok - exclude obfuscation group.
convert_uudecode( "+22!L;W9E(%!(4\"$`\n`" ); // Ok - exclude obfuscation group.
convert_uuencode( "test\ntext text\r\n" ); // Ok - exclude obfuscation group.
str_rot13( 'The quick brown fox jumps over the lazy dog.' ); // Ok - exclude obfuscation group.
serialize(); // Warning.
unserialize(); // Warning.
urlencode(); // Warning.
passthru( 'cat myfile.zip', $err ); // Warning.
$process = proc_open( 'php', $descriptorspec, $pipes, $cwd, $env ); // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- Warning.
$last_line = system( 'ls', $retval ); // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- Warning.
$handle = popen( '/bin/ls', 'r' ); // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- Warning.

// WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting
error_reporting(); // Error.

// WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_ini_restore
ini_restore(); // Error.

// WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_apache_setenv
apache_setenv(); // Error.

// WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv
putenv(); // Error.

// WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path
set_include_path(); // Error.

// WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_restore_include_path
restore_include_path(); // Error.

// WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_magic_quotes_runtime
magic_quotes_runtime(); // Error.

// WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_magic_quotes_runtime
set_magic_quotes_runtime(); // Error.

// WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_dl
dl(); // Error.

// WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
exec( 'whoami' ); // Error.

// WordPress.PHP.DiscouragedPHPFunctions.system_calls_shell_exec
$output = shell_exec( 'ls -lart' ); // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- Error.

// WordPress.PHP.DevelopmentFunctions
var_dump(); // Warning.
var_export(); // Warning.
print_r(); // Warning.
trigger_error( 'message' ); // Warning.
set_error_handler(); // Warning.
debug_backtrace(); // Warning.
debug_print_backtrace(); // Warning.
wp_debug_backtrace_summary(); // Warning.

// WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_phpinfo
phpinfo(); // Error.

// WordPress.PHP.DevelopmentFunctions.error_log_error_log
error_log(); // Error.

// WordPress.PHP.IniSet
ini_set( 'auto_detect_line_endings', true ); // Ok.
ini_set( 'highlight.bg', '#000000' ); // Ok.
ini_set( 'highlight.comment', '#000000' ); // Ok.
ini_set( 'highlight.default', '#000000' ); // Ok.
ini_set( 'highlight.html', '#000000' ); // Ok.
ini_set( 'highlight.keyword', '#000000' ); // Ok.
ini_set( 'highlight.string', '#000000' ); // Ok.
ini_set( 'short_open_tag', 1 ); // Ok.
ini_set( 'bcmath.scale', 1 ); // Error.
ini_set( 'display_errors', 1 ); // Error.
ini_set( 'error_reporting', 1 ); // Error.
ini_set( 'filter.default', 1 ); // Error.
ini_set( 'filter.default_flags', 1 ); // Error.
ini_set( 'iconv.input_encoding', 1 ); // Error.
ini_set( 'iconv.internal_encoding', 1 ); // Error.
ini_set( 'iconv.output_encoding', 1 ); // Error.
ini_set( 'ignore_user_abort', 1 ); // Error.
ini_set( 'log_errors', 1 ); // Error.
ini_set( 'max_execution_time', 1 ); // Error.
ini_set( 'memory_limit', 1 ); // Error.
ini_set( 'short_open_tag', 'off' ); // Error.
ini_set( 'foo', true ); // Warning.
ini_alter( 'auto_detect_line_endings', true ); // Ok.
ini_alter( 'highlight.bg', '#000000' ); // Ok.
ini_alter( 'highlight.comment', '#000000' ); // Ok.
ini_alter( 'highlight.default', '#000000' ); // Ok.
ini_alter( 'highlight.html', '#000000' ); // Ok.
ini_alter( 'highlight.keyword', '#000000' ); // Ok.
ini_alter( 'highlight.string', '#000000' ); // Ok.
ini_alter( 'short_open_tag', 1 ); // Ok.
ini_alter( 'bcmath.scale', 1 ); // Error.
ini_alter( 'display_errors', 1 ); // Error.
ini_alter( 'error_reporting', 1 ); // Error.
ini_alter( 'filter.default', 1 ); // Error.
ini_alter( 'filter.default_flags', 1 ); // Error.
ini_alter( 'iconv.input_encoding', 1 ); // Error.
ini_alter( 'iconv.internal_encoding', 1 ); // Error.
ini_alter( 'iconv.output_encoding', 1 ); // Error.
ini_alter( 'ignore_user_abort', 1 ); // Error.
ini_alter( 'log_errors', 1 ); // Error.
ini_alter( 'max_execution_time', 1 ); // Error.
ini_alter( 'memory_limit', 1 ); // Error.
ini_alter( 'short_open_tag', 'off' ); // Error.
ini_alter( 'foo', true ); // Warning.

// WordPress.WP.AlternativeFunctions
curl_init(); // Warning + Message.
curl_close( $ch ); // Warning + Message.
CURL_getinfo(); // Warning + Message.
parse_url( 'http://example.com/' ); // Warning.
$json = json_encode( $thing ); // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- Warning.
readfile(); // Warning.
fclose(); // Warning.
fopen(); // Warning.
fread(); // Warning.
fsockopen(); // Warning.
pfsockopen(); // Warning.
srand(); // Warning.
mt_srand(); // Warning.
rand(); // Warning.
mt_rand(); // Warning.

// WordPressVIPMinimum.Functions.RestrictedFunctions.get_posts_get_children
get_children(); // Error + Message.

// VariableAnalysis.CodeAnalysis.VariableAnalysis
function foo() {
	$a = 'Hello';
	$c = compact( $a, $b ); // Warning x 2.
	try {
		do_something_silly();
	} catch ( Exception $e ) {} // Ok.
}

/* The below rules are implicitly included via WordPressVIPMinimum */

// WordPressVIPMinimum.Classes.DeclarationCompatibility
class MyWidget extends WP_Widget {
	function widget() { // Error.
	}
}

// WordPressVIPMinimum.Classes.RestrictedExtendClasses
class BadTestClass extends WP_CLI_Command { } // Warning.




// WordPressVIPMinimum.Constants.ConstantString
define( WPCOM_VIP ); // Error.

// WordPressVIPMinimum.Constants.RestrictedConstants
if ( A8C_PROXIED_REQUEST === true ) { // Warning.
}
define( 'JETPACK_DEV_DEBUG', true ); // Error.

// WordPressVIPMinimum.Files.IncludingFile
include ( MY_CONSTANT . "my_file.php" ); // Warning.
require_once( custom_function( 'test_file.php' ) ); // Warning.
require '../my_file.php'; // Error.
include_once("http://www.google.com/bad_file.php"); // Error.

// WordPressVIPMinimum.Files.IncludingNonPHPFile
require_once __DIR__ . "/my_file.svg"; // Error.

// WordPressVIPMinimum.Functions.CheckReturnValue
$my_theme_options = get_option( 'my_theme', false );
if ( array_key_exists( 'key', $my_theme_options ) ) { } // Error.
echo '<a href="' . esc_url( get_term_link( $var ) ) . '">My term link</a>'; // Error.

// WordPressVIPMinimum.Functions.DynamicCalls
$my_notokay_func = 'extract';
$my_notokay_func(); // Error.

// WordPressVIPMinimum.Functions.RestrictedFunctions

opcache_reset(); // Error.
opcache_invalidate( 'test_script.php' ); // Error.
opcache_compile_file( $var ); // Error.
opcache_is_script_cached( 'test_script.php' ); // Error.
opcache_get_status(); // Error.
opcache_get_configuration(); // Error.

wpcom_vip_irc(); // Error.
flush_rewrite_rules(); // Error.
$wp_rewrite->flush_rules(); // Error.
attachment_url_to_postid( $url ); // Error.

switch_to_blog( $blogid ); // Warning.

url_to_postid( $url ); // Error.
\add_role(); // Error.





count_user_posts(); // Error.
wp_old_slug_redirect(); // Error.
get_adjacent_post(); // Error.
get_previous_post(); // Error.
get_previous_post_link(); // Error.
get_next_post(); // Error.
get_next_post_link(); // Error.
get_intermediate_image_sizes(); // Error.
wp_is_mobile(); // Error.
session_abort(); // Error.
session_cache_expire(); // Error.
session_cache_limiter(); // Error.
session_commit(); // Error.
session_create_id(); // Error.
session_decode(); // Error.
session_destroy(); // Error.
session_encode(); // Error.
session_gc(); // Error.
session_get_cookie_params(); // Error.
session_id(); // Error.
session_is_registered(); // Error.
session_module_name(); // Error.
session_name(); // Error.
session_regenerate_id(); // Error.
session_register_shutdown(); // Error.
session_register(); // Error.
session_reset(); // Error.
session_save_path(); // Error.
session_set_cookie_params(); // Error.
session_set_save_handler(); // Error.
session_start(); // Error.
session_status(); // Error.
session_unregister(); // Error.
session_unset(); // Error.
session_write_close(); // Error.

file_put_contents( $file, $text, FILE_APPEND ); // Error.
while ( $count > $loop ) {
	if ( flock( $fp, LOCK_EX ) ) { // Error.
		fwrite( $fp, $text ); // Error.
	}
}
fputcsv(); // Error.
fputs(); // Error.
ftruncate(); // Error.
is_writable(); // Error.
is_writeable(); // Error.
link(); // Error.
rename(); // Error.
symlink(); // Error.
tempnam(); // Error.
touch(); // Error.
unlink(); // Error.
mkdir(); // Error.
rmdir(); // Error.
chgrp(); // Error.
chown(); // Error.
chmod(); // Error.
lchgrp(); // Error.
lchown(); // Error.



wp_mail(); // Warning.
mail(); // Warning.
is_multi_author(); // Warning.
the_sub_field( 'field' ); // Warning.
the_field( 'field' ); // Warning.
wp_remote_get( $url ); // Warning.
setcookie( 'cookie[three]', 'cookiethree' ); // Error.
get_posts(); // Warning.
wp_get_recent_posts(); // Warning.
$wp_random_testing = create_function( '$a, $b', 'return ( $b / $a ); '); // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- Warning.
wpcom_vip_get_term_link(); // Warning.
wpcom_vip_get_term_by(); // Warning.
wpcom_vip_get_category_by_slug(); // Warning.

// WordPressVIPMinimum.Functions.StripTagsSniff
strip_tags( 'Testing' ); // Warning.
strip_tags( 'Test', $text ); // Warning.

// WordPressVIPMinimum.Hooks.AlwaysReturnInFilter
function bad_example_function_thing() { // Error.
	if ( 1 === 0  ) {
		if ( 1 === 1 ) {
			return 'ahoj';
		} else {
			return 'hello';
		}
	}
}
add_filter( 'bad_example_function_filter', 'bad_example_function_thing' );
add_filter( 'another_bad_example_closure', function() { // Error.
	return;
} );

// WordPressVIPMinimum.Hooks.PreGetPosts
add_action( 'pre_get_posts', function( $wp_query ) {
	if ( ! $wp_query->is_search() ) {
		$wp_query->set( 'cat', '-5' ); // Warning.
	}
} );

// WordPressVIPMinimum.Hooks.RestrictedHooks
add_filter( 'upload_mimes', 'bad_example_function' ); // Warning.
add_action( 'http_request_timeout', 'bad_example_function' ); // Warning.
add_filter('http_request_args', 'bad_example_function' ); // Warning.
add_action( 'do_robotstxt', 'my_do_robotstxt'); // Warning.
add_filter( 'robots_txt', function() { // Warning.
	return 'test';
} );





// WordPressVIPMinimum.Performance.CacheValueOverride
$bad_wp_users = wp_cache_get( md5( self::CACHE_KEY . '_wp_users'), self::CACHE_GROUP ); // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
$bad_wp_users = false; // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- Error.

// WordPressVIPMinimum.Performance.FetchingRemoteData
$external_resource = file_get_contents( 'https://example.com' ); // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- Warning.

// WordPressVIPMinimum.Performance.LowExpiryCacheTime
wp_cache_set( 'test', $data, $group, 100 ); // Warning.
wp_cache_add( 123, $data, null, 1.5 * MINUTE_IN_SECONDS ); // Warning.
wp_cache_replace( 'test', $data, $group,  2*MINUTE_IN_SECONDS  ); // Warning.

// WordPressVIPMinimum.Performance.NoPaging
$args = array( // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
	'nopaging'       => true, // Error.
);
_query_posts( 'nopaging=true' ); // Error.

// WordPressVIPMinimum.Performance.OrderByRand
$args = array( // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
	"orderby" => "RAND", // Error.
);
$query_args['orderby'] = 'rand'; // Error.

// WordPressVIPMinimum.Performance.RegexpCompare
$query_args = array(
	'posts_per_page' => 1,
	'post_status' => 'draft',
	'meta_compare' => 'REGEXP', // Error.
);
$query_args = [
	'post_status' => 'publish',
	'meta_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		[
			'compare' => 'REGEXP', // Error.
		]
	]
];

// WordPressVIPMinimum.Performance.RemoteRequestTimeout
wp_remote_post( $stdClass->endpoint, array(
        'method'      => 'POST',
        'timeout'     => 45, // Error.
        'httpversion' => '1.1',
        'blocking'    => false,
        'body'        => wp_json_encode( $stdClass->logs, JSON_UNESCAPED_SLASHES ),
    )
);

// WordPressVIPMinimum.Performance.TaxonomyMetaInOptions
get_option( "taxonomy_rating_$obj->term_id" ); // Warning.
update_option( 'taxonomy_rating_' . $category_id ); // Warning.

// WordPressVIPMinimum.Performance.WPQueryParams
$query_args = array(
	'post__not_in'     => $posts_not_in, // Warning.
	'suppress_filters' => true, // Error.
);

// WordPressVIPMinimum.Security.EscapingVoidReturnFunctions.Found
esc_js( _deprecated_argument() ); // Error.
esc_js( _deprecated_constructor() ); // Error.
esc_js( _deprecated_file( 'filename' ) ); // Error.
esc_js( _deprecated_function() ); // Error.
esc_js( _deprecated_hook() ); // Error.
esc_js( _doing_it_wrong() ); // Error.
esc_html( printf( 'foo', [] ) ); // Error.
esc_attr( user_error( 'foo', '' ) ); // Error.
esc_attr( vprintf( 'foo', [] ) ); // Error.
esc_attr( wp_die( 'foo' ) ); // Error.
esc_attr( wp_dropdown_pages() ); // Error.

// WordPressVIPMinimum.Security.ExitAfterRedirect
function redirect_test() {
	wp_safe_redirect( 'https.//vip.wordpress.com' ); // Error.
}
wp_redirect( 'https://vip.wordpress.com' ); // Error.

// WordPressVIPMinimum.Security.PHPFilterFunctions
filter_input( INPUT_GET, 'foo' ); // Warning.
filter_input( INPUT_GET, "foo", FILTER_UNSAFE_RAW  ); // Warning.
filter_var( $url, FILTER_DEFAULT ); // Warning.
filter_var_array( $array ); // Warning.
filter_input_array( $array ); // Warning.

// WordPressVIPMinimum.Security.Mustache
echo '<a href="{{href}}">{{{data}}}</div></a>'; // Warning.
?>
<script type="text/html" id="tmpl-example">
{{=<% %>=}} <!-- Warning. -->
</script>
<script>
	andlebars.registerHelper('link', function(text, url) {
		text = 'test';
		url  = Handlebars.Utils.escapeExpression(url);
		var result = '<a href="' + url + '">' + text + '</a>';
		return new Handlebars.SafeString(result); // Warning.
	});
</script> <?php

// WordPressVIPMinimum.Security.ProperEscapingFunction
echo '<a href="' . esc_attr( $some_var ) . '"></a>'; // Error.
echo '<a title="' . esc_html( $some_var ) . '"></a>'; // Error.

// WordPressVIPMinimum.Security.StaticStrreplace
str_replace( 'foo', array( 'bar', 'foo' ), 'foobar' ); // Error.

// WordPressVIPMinimum.Security.Underscorejs
echo "<script>
 _.templateSettings = { 
	interpolate: /\{\{(.+?)\}\}/g" . // Warning.
"};
 </script>";

// WordPressVIPMinimum.Security.Vuejs
?> <article v-for="post in posts" class="listing-post">
	<a v-bind:href="'/research'+post.permalink" class="column-4"><h4 v-html="post.title"></h4></a> <!-- Warning. -->
</article> <?php

// WordPressVIPMinimum.UserExperience.AdminBarRemoval
add_filter( 'show_admin_bar', '__return_false' ); // Error.
show_admin_bar( false ); // Error.
echo '<style type="text/css">
#wpadminbar {
	visibility: hidden; /* Error. */
	display: none; /* Error. */
	opacity: 0; /* Error. */
}
</style>';
echo '<style type="text/css">.show-admin-bar { visibility: hidden; }</style>'; // Error.
?> <style type="text/css">
.show-admin-bar {
	visibility: hidden; /* Error. */
	display: none; /* Error. */
	opacity: 0; /* Error. */
}
</style> <?php

// WordPressVIPMinimum.Variables.RestrictedVariables
$query = "SELECT * FROM $wpdb->users"; // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- Error.
$x = foo( sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated,VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- Warning.
foo( $_SESSION['bar'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Error.

// WordPressVIPMinimum.Variables.ServerVariables
// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
$test = $_SERVER['PHP_AUTH_PW']; // Error.
bar( $_SERVER['HTTP_X_IP_TRAIL'] ); // Error.
$_SERVER['HTTP_X_FORWARDED_FOR']; // Error.
$_SERVER["REMOTE_ADDR"]; // Error.
// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

class MyClass {
	function my_function() {
		return function() {
			$this->my_callback(); // OK - new VariableAnalysis doesn't flag $this as undefined in closure.
		};
	}

	function my_callback() {}
}

// Generic.VersionControl.GitMergeConflict
?>
<<<<<<< HEAD // Error.

>>>>>>> // Error.
<?php

// WordPress.CodeAnalysis.AssignmentInTernaryCondition
$var = ( $test = get_post( $post ) ) ? $test : ''; // Warning.

// Squiz.WhiteSpace.SuperfluousWhitespace
// Error. ?>




