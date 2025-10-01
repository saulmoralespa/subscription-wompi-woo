<?php

/* Path to the WordPress codebase you'd like to test. Add a forward slash in the end. */
define( 'ABSPATH', __DIR__ . getenv('WP_TEST__DIR') . '/wordpress/' );

/*
 * Path to the theme to test with.
 *
 * The 'default' theme is symlinked from test/phpunit/data/themedir1/default into
 * the themes directory of the WordPress installation defined above.
 */
define( 'WP_DEFAULT_THEME', 'default' );

// Test with multisite enabled.
// Alternatively, use the tests/phpunit/multisite.xml configuration file.
// define( 'WP_TESTS_MULTISITE', true );

// Force known bugs to be run.
// Tests with an associated Trac ticket that is still open are normally skipped.
// define( 'WP_TESTS_FORCE_KNOWN_BUGS', true );

// Test with WordPress debug mode (default).
define( 'WP_DEBUG', true );

// ** MySQL settings ** //

// This configuration file will be used by the copy of WordPress being tested.
// wordpress/wp-config.php will be ignored.

// WARNING WARNING WARNING!
// These tests will DROP ALL TABLES in the database with the prefix named below.
// DO NOT use a production database or one that is shared with something else.

define( 'DB_NAME'       , getenv( 'WP_DB_NAME' ) ?: 'wp_test' );
define( 'DB_USER'       , getenv( 'WP_DB_USER' ) ?: 'wp_test' );
define( 'DB_PASSWORD'   , getenv( 'WP_DB_PASS' ) ?: '#dgs45As' );
define( 'DB_HOST'       , getenv( 'WP_DB_HOST' ) ?: '127.0.0.1' );
define( 'DB_CHARSET'    , 'utf8' );
define( 'DB_COLLATE'    , '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 */
define('AUTH_KEY',         '+[#ZF2+ -nz|YN4zUYl6U@7U;R7Cibe+)9l1[0+|AP^2Hni5C>oEW!q}w-yt*ZT(');
define('SECURE_AUTH_KEY',  '74([,-J4^9HGNw?mdDSE*(c){!M_r0$z)A-&_C>e>;f3wr)hC`z%6n~Co4UYQHm/');
define('LOGGED_IN_KEY',    '(9H!7:W(}4K|yVN>tMHX3y+8r:|A_+#;-(jDRR0)_F8u,gSM|KKmcHUa;h1}[{+5');
define('NONCE_KEY',        'zR+pb(J)GlH=<gf-&;R[.]5}TJzG|Yku<<Pjte8}pYrkMm!OD_)8-*TwX8p#a8j:');
define('AUTH_SALT',        '//*zzdHwm%<V7|@4FeD}Kx%Xu0(xi&+15N-A-c>f-[cl$Om<jL6NV3gB8yU<`$=x');
define('SECURE_AUTH_SALT', 'f5kKeY$Rii,[c=ytwD&v*ZN&K<^Or0jc^dKRhqN<YElGu!}iH]c#Ini@Z<IZR3DS');
define('LOGGED_IN_SALT',   'F0l.(H89+bUjPpuxAlR#w[BDpS7ka|1!&mg+W1%c)?MdcgH|/:Ra:{~CLMyJGl5q');
define('NONCE_SALT',       'jveK&pC|:=xo}Jk!T^wNGiUD1.>ZnM,C4uJN!q(y+&m*fuU`O5Cw|i{u?HJ=R3Lo');

$table_prefix = 'tests_';   // Only numbers, letters, and underscores please!

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Test Blog' );

define( 'WP_PHP_BINARY', 'php' );

define( 'WPLANG', '' );