/* eslint-env node */
module.exports = function Gruntfile( grunt ) {
	grunt.loadNpmTasks( 'grunt-banana-checker' );
	grunt.loadNpmTasks( 'grunt-stylelint' );

	grunt.initConfig( {
		banana: {
			all: {
				src: 'i18n/'
			}
		},
		stylelint: {
			all: [
				'**/*.{css,less}',
				'!node_modules/**',
				'!vendor/**',
				'!var/**'
			]
		},
	} );

	grunt.registerTask( 'test', [ 'stylelint', 'banana' ] );
	grunt.registerTask( 'default', 'test' );
};
