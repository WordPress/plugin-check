<?php
$file = ''; $fp = ''; $dir = ''; $test = ''; $bar = ''; $array = []; $query_args = []; $url = ''; $query = ''; $page_title = ''; $true = true; $some_nasty_var = ''; $data = ''; $group = ''; $testing = ''; $needle = ''; $some_var = ''; $blogid = 1; $text = ''; $category_id = 123; $foo = ''; $bar = ''; $var = ''; $wp_rewrite = ''; $count = 1; $loop = 1; $a = ''; $b = ''; $obj = ''; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- All set for VariableAnalysis checks.



// WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
file_put_contents( 'file.txt', '', FILE_APPEND ); // Warning + Message.

// WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_flock
if ( flock( $fp, LOCK_EX ) ) { // Warning + Message.
}

// WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fputcsv
fputcsv( $fp, $array ); // Warning + Message.

// WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fputs
fputs( $fp, 'test' ); // Warning + Message.

// WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fwrite
fwrite( $fp, 'test' ); // Warning + Message.

// WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_ftruncate
ftruncate( $fp, 1 ); // Warning + Message.

// WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_is_writable
is_writable( 'file.txt' ); // Warning + Message.

// WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_is_writeable
is_writeable( $file ); // Warning + Message.

// WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_link
link( 'file.txt', 'newfile.txt' ); // Warning + Message.

// WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_rename
rename( 'oldfile.txt', $file ); // Warning + Message.

// WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_symlink
symlink( $file, 'file.txt' ); // Warning + Message.

// WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_tempname
tempnam( $dir, 'pre' ); // Warning + Message.

// WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_touch
touch( $file ); // Warning + Message.

// WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink
unlink( $file ); // Warning + Message.

// WordPressVIPMinimum.Functions.RestrictedFunctions.cookies_setcookie
setcookie( 'cookie[three]', 'cookiethree' ); // Error.

// WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE
$x = sanitize_key( $_COOKIE['bar'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated -- Error.

// WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__HTTP_USER_AGENT__
if ( isset( $_SERVER['HTTP_USER_AGENT'] ) && $_SERVER['HTTP_USER_AGENT'] === 'some_value' ) { // Error.
}

// Make sure nonce verification is done in global scope to silence notices about use of superglobals without later on in the file.
isset( $_GET['my_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_GET['my_nonce'] ) );

// WordPress.WP.AlternativeFunctions.file_system_operations_fopen
fopen( 'file.txt', 'r' ); // Warning + Message.

// WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
$external_resource = file_get_contents( $test ); // Warning + Message.
$file_content = file_get_contents( 'my-file.svg' ); // Ok.
wpcom_vip_file_get_contents( $bar ); // Ok.

// WordPress.Security.NonceVerification (inherited from parent)
function bar_foo() {
	if ( ! isset( $_POST['test'] ) ) { // Error.
		return;
	}
}
function foo_bar() {
	if ( ! isset( $_POST['test'] ) || ! wp_verify_nonce( 'some_action' ) ) { // Ok.
		exit;
	}
}

// WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
do_something( $_POST['key'] ); // Error + warning.
if ( isset( $_POST['foo2'] ) ) {
	bar( wp_unslash( $_POST['foo2'] ) ); // Warning.
}

// WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
$args = array(
	'posts_per_page' => 999, // Warning + Message.
	'posts_per_page' => 1, // OK.
	'posts_per_page' => '1', // OK.
);
_query_posts( 'posts_per_page=999' ); // Warning + Message.
$query_args['posts_per_page'] = 999; // Warning + Message.
$query_args['posts_per_page'] = 1; // OK.

// WordPressVIPMinimum.Hooks.RestrictedHooks.upload_mimes
add_filter( 'upload_mimes', 'foo' ); // Warning.

// WordPressVIPMinimum.Security.PHPFilterFunctions
filter_var( $url ); // Warning.
filter_var_array( $array ); // Warning.
filter_input_array( $array ); // Warning.
filter_var( $url, FILTER_SANITIZE_URL ); // Ok.
filter_input( INPUT_GET, 'foo' ); // Warning.
filter_input( INPUT_GET, 'foo', FILTER_SANITIZE_STRING ); // Ok.
filter_var( $url, FILTER_DEFAULT ); // Warning.
filter_var_array( $array, FILTER_UNSAFE_RAW ); // Warning.
filter_var_array( $array, FILTER_SANITIZE_STRING ); // Ok.
filter_input_array( $array,FILTER_SANITIZE_STRING      ); // Ok.
filter_input( INPUT_GET, 'foo', FILTER_DEFAULT ); // Warning.




// WordPressVIPMinimum.Functions.RestrictedFunctions.wp_mail_wp_mail
wp_mail(); // Warning.
mail(); // Warning.

// WordPressVIPMinimum.Functions.RestrictedFunctions.attachment_url_to_postid_attachment_url_to_postid
wpcom_vip_attachment_url_to_postid( $url ); // Ok.
attachment_url_to_postid( $url ); // Warning + Message.

// WordPressVIPMinimum.Functions.RestrictedFunctions.get_adjacent_post_get_adjacent_post
wpcom_vip_get_adjacent_post(); // Ok.
get_adjacent_post(); // Warning.
get_previous_post(); // Warning.
get_next_post(); // Warning.
get_previous_post_link(); // Warning.
get_next_post_link(); // Warning.





// WordPressVIPMinimum.Functions.RestrictedFunctions.get_posts_get_children
get_child(); // Ok.
get_children(); // Warning + Message.

// WordPressVIPMinimum.Functions.RestrictedFunctions.get_posts_get_posts
get_posts(); // Warning.
get_post( 123 ); // Ok.

// WordPressVIPMinimum.Functions.RestrictedFunctions.get_posts_wp_get_recent_posts
wp_get_recent_posts(); // Warning.

// WordPressVIPMinimum.Functions.RestrictedFunctions.url_to_postid_url_to_postid
wpcom_vip_url_to_postid( $url ); // Ok.
url_to_postid( $url ); // Warning + Message.

// WordPressVIPMinimum.Functions.RestrictedFunctions.wp_old_slug_redirect_wp_old_slug_redirect
wpcom_vip_old_slug_redirect(); // Ok.
wp_old_slug_redirect(); // Warning.

// Generic.CodeAnalysis.AssignmentInCondition.Found
if ($a = 123) { // Warning.
}

// WordPress.PHP.DiscouragedPHPFunctions.urlencode_urlencode
urlencode(); // Warning.
rawurlencode(); // Ok.

// WordPress.PHP.DontExtract
extract( array( 'a' => 1 ) ); // Error.
$obj->extract(); // Ok.

// Universal.Operators.StrictComparisons
true == $true; // Warning.
false === $true; // Ok.

// WordPress.PHP.StrictInArray.MissingTrueStrict
in_array( 1, array( '1', 1, true ), true ); // Ok.
in_array( 1, array( '1', 1, true ) ); // Warning.
in_array( 1, array( '1', 1, true ), false ); // Warning.
array_search( 1, $array, false ); // Warning.
array_keys( array( '1', 1, true ), 'my_key' ); // Warning.

// WordPress.Security.EscapeOutput.UnsafePrintingFunction
_e( $some_nasty_var ); // Error.
_ex( $some_nasty_var, 'context' ); // Error.
echo esc_html_x( 'Something', 'context' ); // Ok.
echo esc_html_x( $some_nasty_var, 'context' ); // Ok.

// WordPress.WP.GlobalVariablesOverride.OverrideProhibited
global $wpdb;
$wpdb = 'test'; // Error.
$GLOBALS['domain']['subkey'] = 'something else'; // Error.

// WordPress.WP.EnqueuedResources.NonEnqueuedScript
echo wp_kses( '<script src="' . esc_url( $url ) . '">', [ 'script' => [ 'src' => [], ], ] ); // Warning + Message.
?> <script src="http://someurl/somefile.js"></script> <!-- Warning + Message. -->

<!-- WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -->
<link rel="stylesheet" href="http://someurl/somefile.css"> <!-- Warning + Message. -->
<?php echo '<link rel="stylesheet" src="' . esc_url( SOMESCRIPT ) . '">'; // Warning + Message.

// WordPressVIPMinimum.Performance.LowExpiryCacheTime.LowCacheTime
wp_cache_set( 'test', $data, $group, 100 ); // Warning.
wp_cache_add( 'test', $data, $group,  2*MINUTE_IN_SECONDS  ); // Warning.
wp_cache_replace( $testing, $data, '', 1.5 * MINUTE_IN_SECONDS ); // Warning.

// WordPressVIPMinimum.Files.IncludingFile
include_once ( MY_CONSTANT . "my_file.php" ); // Warning.
require_once( custom_function( 'test_file.php' ) ); // Warning.
require_once "my_file.php"; // Warning.
require '../../my_file.php'; // Warning.
include("http://www.google.com/bad_file.php"); // Warning.

// VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable
function foo_bar_bar() {
	$b . 'test'; // Warning.
}

// VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
function foo_bar_foo() {
	$a = 'Hello'; // OK.  Unused variables warning silenced.
}

// WordPressVIPMinimum.UserExperience.AdminBarRemoval
add_filter( 'show_admin_bar', '__return_false' ); // Warning.
add_filter( 'show_admin_bar', '__return_true' ); // Ok.
show_admin_bar( false ); // Warning.
show_admin_bar( true ); // Ok.
add_filter( 'show_admin_bar', 'my_own_return_false' ); // Warning.
echo '<style type="text/css">
#wpadminbar {
	visibility: hidden; /* Error. */
	display: none; /* Error. */
	opacity: 0; /* Error. */
}
</style>';
?> <style type="text/css">
#wpadminbar {
	visibility: hidden; /* Warning. */
	display: none; /* Warning. */
	opacity: 0; /* Warning. */
}
#not-wpadminbar {
	visibility: hidden; /* OK. */
	display: none; /* OK. */
	opacity: 0; /* OK. */
}
.show-admin-bar {
	visibility: hidden; /* Warning. */
	display: none; /* Warning. */
	opacity: 0; /* Warning. */
}
</style> <?php

// Generic.PHP.NoSilencedErrors
$test = @in_array( $array, $needle, true ); // Error.

// WordPressVIPMinimum.Security.ProperEscapingFunction.htmlAttrNotByEscHTML
echo '<a href="' . esc_attr( $some_var ) . '"></a>'; // Error.
echo '<a title="' . esc_html( $some_var ) . '"></a>'; // Error.
echo '<a href="' . esc_url( $some_var ) . '"></a>'; // OK.
?><a href="<?php echo esc_attr( $some_var ); ?>">Hello</a> <!-- Error. -->
<a href="" class="<?php echo esc_html( $some_var); ?>">Hey</a> <!-- Error. -->
<a href="<?php esc_url( $url );?>"></a> <!-- Ok. -->
<a title="<?php esc_attr( $url );?>"></a> <?php // Ok.

// WordPressVIPMinimum.Functions.RestrictedFunctions.is_multi_author_is_multi_author
multi_author(); // Ok.
is_multi_author(); // Warning.

// WordPressVIPMinimum.Functions.RestrictedFunctions.switch_to_blog_switch_to_blog
switch_blog(); // Ok.
switch_to_blog( $blogid ); // Warning + Message.

// WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn
$args( [
	'post__not_in' => array( 1, 2, 3 ), // Warning.
] );

/* Rules that are being silenced and should NOT be flagged. */

// WordPress.DB.SlowDBQuery.slow_db_query_meta_key
$query = new WP_Query( ['meta_key' => 'foo' ] ); // Ok.
$args = 'foo=bar&meta_key=foo'; // Ok.





















// WordPressVIPMinimum.Functions.RestrictedFunctions.site_option_delete_site_option
delete_site_option( $foo ); // Ok.

// WordPressVIPMinimum.Functions.RestrictedFunctions.site_option_update_site_option
update_site_option( $bar, $foo, true ); // Ok.

// WordPressVIPMinimum.Functions.RestrictedFunctions.site_option_add_site_option
add_site_option( 'foo', $bar ); // Ok.

// Generic.PHP.DisallowShortOpenTag.EchoFound
?> <?= esc_html( $var ); // Ok. ?> <?php

/* The below rules are inherited from WordPressVIPMinimum */

// WordPressVIPMinimum.Classes.DeclarationCompatibility
class MyWidget extends WP_Widget {
	function widget() { } // Error.
}

// WordPressVIPMinimum.Classes.RestrictedExtendClasses
class BadTestClass extends WP_CLI_Command { // Warning.
}




// WordPressVIPMinimum.Constants.ConstantString
define( WPCOM_VIP ); // Error.

// WordPressVIPMinimum.Constants.RestrictedConstants
if ( A8C_PROXIED_REQUEST === true ) { // Warning.
}
define( 'JETPACK_DEV_DEBUG', true ); // Error.

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
get_super_admins(); // OK.
wpcom_vip_irc(); // Error.
flush_rewrite_rules(); // Error.
$wp_rewrite->flush_rules(); // Error.
\add_role(); // Error.

count_user_posts(); // Error.
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

file_put_contents( $file, $text, FILE_APPEND ); // Warning.
while ( $count > $loop ) {
	if ( flock( $fp, LOCK_EX ) ) { // Warning.
		fwrite( $fp, $text ); // Warning.
	}
}
fputcsv(); // Warning.
fputs(); // Warning.
ftruncate(); // Warning.
is_writable(); // Warning.
is_writeable(); // Warning.
link(); // Warning.
rename(); // Warning.
symlink(); // Warning.
tempnam(); // Warning.
touch(); // Warning.
unlink(); // Warning.
mkdir(); // Error.
rmdir(); // Error.
chgrp(); // Error.
chown(); // Error.
chmod(); // Error.
lchgrp(); // Error.
lchown(); // Error.
the_sub_field( 'field' ); // Warning.
the_field( 'field' ); // Warning.
wp_remote_get( $url ); // Warning.
get_posts(); // Warning.
function test_function( $a, $b ) { // OK. Unused variables warning silenced.
	return create_function( '$a, $b', 'return ( $b / $a ); '); // Warning.
}
wpcom_vip_get_term_link(); // Warning.
wpcom_vip_get_term_by(); // Warning.
wpcom_vip_get_category_by_slug(); // Warning.

// WordPressVIPMinimum.Functions.StripTagsSniff
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
add_action( 'http_request_timeout', 'bad_example_function' ); // Warning.
add_filter('http_request_args', 'bad_example_function' ); // Warning.
add_action( 'do_robotstxt', 'my_do_robotstxt'); // Warning.
add_filter( 'robots_txt', function() { // Warning.
	return 'test';
} );

// WordPressVIPMinimum.Performance.CacheValueOverride
$bad_wp_users = wp_cache_get( md5( self::CACHE_KEY . '_wp_users'), self::CACHE_GROUP );
$bad_wp_users = false; // Error.

// WordPressVIPMinimum.Performance.NoPaging
$args = array(
	'nopaging'       => true, // Error.
);
_query_posts( 'nopaging=true' ); // Error.

// WordPressVIPMinimum.Performance.OrderByRand
$args = array(
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
wp_remote_post( $obj->endpoint, array(
        'method'      => 'POST',
        'timeout'     => 45, // Error.
        'httpversion' => '1.1',
        'blocking'    => false,
        'body'        => wp_json_encode( $obj->logs, JSON_UNESCAPED_SLASHES ),
    )
);

// WordPressVIPMinimum.Performance.TaxonomyMetaInOptions
get_option( "taxonomy_rating_$obj->term_id" ); // Warning.
update_option( 'taxonomy_rating_' . $category_id ); // Warning.

// WordPressVIPMinimum.Performance.WPQueryParams
$query_args = array(
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

// WordPressVIPMinimum.Variables.RestrictedVariables
foo( $_SESSION['bar'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Error.

// WordPressVIPMinimum.Variables.ServerVariables
// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
$test = $_SERVER['PHP_AUTH_PW']; // Error.
bar( $_SERVER['HTTP_X_IP_TRAIL'] ); // Error.
$_SERVER['HTTP_X_FORWARDED_FOR']; // Error.
$_SERVER["REMOTE_ADDR"]; // Error.
// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotValidated,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

// Generic.VersionControl.GitMergeConflict
?>
<<<<<<< HEAD // Error.

>>>>>>> // Error.

<?php

// WordPress.CodeAnalysis.AssignmentInTernaryCondition
$var = ($a = 123) ? $a : 0; // Warning.
