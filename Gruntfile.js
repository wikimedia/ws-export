/* eslint-env node */
module.exports = function Gruntfile( grunt ) {

	grunt.loadNpmTasks( 'grunt-banana-checker' );

	grunt.initConfig( {
		banana: {
			all: {
				src: 'i18n/'
			}
		}
	} );

};
