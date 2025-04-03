const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		index: path.resolve( process.cwd(), 'app', 'index.js' )
	},
	output: {
		filename: '[name].js',
		path: path.resolve(__dirname, 'assets/build')
	}
};
