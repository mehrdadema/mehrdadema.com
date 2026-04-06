<?php
/* Do not remove this line. Add your functions below. */


/* Login logo >>>*/

function rgt_custom_dashboard_logo(){
echo '<style  type="text/css">
body.login {
	background:url(/wp-content/uploads/dynamik-gen/theme/images/login2.jpg) #666 center no-repeat !important;
	background-size: cover !important;
}
.login #backtoblog a,
.login #nav a {
	color:#ffffff !important;
}
.login #backtoblog a,
.login #nav a:hover {
	color:#33ffc5 !important;
}
.login h1 a {
	background-image:url(http://www.mehrdadema.com/wp-content/uploads/dynamik-gen/theme/images/login-m.png)  !important;
	width:124px;
	height:78px;
	background-size:100% !important;
	margin-bottom:20px;
}
.wp-core-ui .button-primary {
	background:#1c8f6f none repeat scroll 0 0;
	border-color:none;
	box-shadow:none;
	text-shadow:none;
}
.wp-core-ui .button-primary:hover {
	background:#2c2c2c;
}

</style>';
}
add_action('login_head',  'rgt_custom_dashboard_logo');


/*<<< Login logo */

add_filter( 'login_headerurl', 'custom_loginlogo_url' );
function custom_loginlogo_url($url) {
	return 'http://www.mehrdadema.com';
}

/*>> Gravity Forms Spinner >>*/

add_filter( 'gform_ajax_spinner_url', 'spinner_url', 10, 2 );
function spinner_url( $image_src, $form ) {
    return "http://www.mehrdadema.com/wp-content/uploads/dynamik-gen/theme/images/spinner2line.gif";
}



// Defer Javascripts
// Defer jQuery Parsing using the HTML5 defer property

function defer_parsing_of_js ( $url ) {
if ( FALSE === strpos( $url, '.js' ) ) return $url;
if ( strpos( $url, 'jquery.js' ) ) return $url;
return "$url' defer ";
}
add_filter( 'clean_url', 'defer_parsing_of_js', 11, 1 );

