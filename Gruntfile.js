/*
 *
 */

module.exports = function (grunt) {

	/**
	 * Files added to WordPress SVN, don't include 'assets/**' here.
	 * @type {Array}
	 */
	svn_files_list = [
		'readme.txt',
		'plugin-customizer-demo.php',
		'inc/**',
		'js/**',
		'src/**',
		'templates/**',
	];

	/**
	 * Let's add a couple of more files to GitHub
	 * @type {Array}
	 */
	git_files_list = svn_files_list.concat([
		'README.md',
		'CREDITS.md',
		'CONTRIBUTING.md',
		'LICENSE',
		'\.[c,e,t]*',
		'phpcs.xml',
		'package.json',
		'Gruntfile.js',
		'assets/**',
		'\.gitattributes',
	]);

	// Project configuration.
	grunt.initConfig({
		pkg : grunt.file.readJSON( 'package.json' ),
		clean: {
			post_build: [
				'build'
			]
		},
		copy: {
			svn_assets: {
				options : {
					mode :true
				},
				expand: true,
				cwd:  'assets/',
				src:  '**',
				dest: 'build/<%= pkg.name %>/assets/',
				flatten: true,
				filter: 'isFile'
			},
			svn_trunk: {
				options : {
					mode :true
				},
				expand: true,
				src:  svn_files_list,
				dest: 'build/<%= pkg.name %>/trunk/'
			},
			svn_tag: {
				options : {
					mode :true
				},
				expand: true,
				src:  svn_files_list,
				dest: 'build/<%= pkg.name %>/tags/<%= pkg.version %>/'
			}
		},
		gittag: {
			addtag: {
				options: {
					tag: '<%= pkg.version %>',
					message: 'Version <%= pkg.version %>'
				}
			}
		},
		gitcommit: {
			commit: {
				options: {
					message: 'Version <%= pkg.version %>',
					noVerify: true,
					noStatus: false,
					allowEmpty: true
				},
				files: {
					src: [ git_files_list ]
				}
			}
		},
		gitpush: {
			push: {
				options: {
					tags: true,
					remote: 'origin',
					branch: 'master'
				}
			}
		},
		replace: {
			reamde_md: {
				src: [ 'README.md' ],
				overwrite: true,
				replacements: [{
					from: /~Current Version:\s*(.*)~/,
					to: "~Current Version: <%= pkg.version %>~"
				}, {
					from: /Latest Stable Release:\s*\[(.*)\]\s*\(https:\/\/github.com\/soderlind\/read-offline\/releases\/tag\/(.*)\s*\)/,
					to: "Latest Stable Release: [<%= pkg.git_tag %>](https://github.com/soderlind/read-offline/releases/tag/<%= pkg.git_tag %>)"
				}]
			},
			reamde_txt: {
				src: [ 'readme.txt' ],
				overwrite: true,
				replacements: [{
					from: /Stable tag: (.*)/,
					to: "Stable tag: <%= pkg.version %>"
				}]

			},
			plugin_php: {
				src: [ '<%= pkg.main %>' ],
				overwrite: true,
				replacements: [{
					from: /Version:\s*(.*)/,
					to: "Version: <%= pkg.version %>"
				}, {
					from: /define\(\s*'PLUGIN_CUSTOMIZER_DEMO_VERSION',\s*'(.*)'\s*\);/,
					to: "define( 'PLUGIN_CUSTOMIZER_DEMO_VERSION', '<%= pkg.version %>' );"
				}]
			},
			plugin_customizer_php: {
				src: [ 'src/Plugin_Customizer.php' ],
				overwrite: true,
				replacements: [{
					from: /define\(\s*'PLUGIN_CUSTOMIZER_VERSION',\s*'(.*)'\s*\);/,
					to: "define( 'PLUGIN_CUSTOMIZER_VERSION', '<%= pkg.version %>' );"
				}]
			}
		},
		svn_export: {
		    dev: {
		      options: {
		        repository: 'http://plugins.svn.wordpress.org/<%= pkg.name %>',
		        output: 'build/<%= pkg.name %>'
		    	}
		    }
		},
		push_svn: {
			options: {
				remove: true
			},
			main: {
				src: 'build/<%= pkg.name %>',
				dest: 'http://plugins.svn.wordpress.org/<%= pkg.name %>',
				tmp: 'build/make_svn'
			}
		},
		githubChanges: {
			dist : {
				options: {
					// Owner and Repository options are mandatory
					owner : 'soderlind',
					repository : '<%= pkg.name %>',
					useCommitBody: true,
					verbose : true
				}
			}
		},
		makepot: {
		    target: {
		        options: {
		            domainPath: '/languages',
		            mainFile: '<%= pkg.main %>',
		            potFilename: 'read-offline.pot',
		            potHeaders: {
		                poedit: true,
		                'x-poedit-keywordslist': true
		            },
		            bugsurl: '<%= pkg.bugs.url%>',
		            processPot: function( pot, options ) {
	                    pot.headers['report-msgid-bugs-to'] = options.bugsurl;
	                    /*pot.headers['language-team'] = 'Team Name <team@example.com>';*/
	                    return pot;
	                },
		            type: 'wp-plugin',
		            updateTimestamp: true,
		            exclude: [
		            	'lib/.*',
		            	'node_modules/.*'
		            ],
		        }
		    }
		}, //makepot
	});



	//load modules
	// grunt.loadNpmTasks( 'grunt-glotpress' );
	grunt.loadNpmTasks( 'grunt-contrib-clean' );
	grunt.loadNpmTasks( 'grunt-contrib-copy' );
	grunt.loadNpmTasks( 'grunt-git' );
	grunt.loadNpmTasks( 'grunt-text-replace' );
	grunt.loadNpmTasks( 'grunt-svn-export' );
	grunt.loadNpmTasks( 'grunt-push-svn' );
	grunt.loadNpmTasks( 'grunt-remove' );
	grunt.loadNpmTasks( 'grunt-wp-i18n' );
	grunt.loadNpmTasks( 'grunt-github-changes' );

	grunt.registerTask('syntax', 'default task description', function(){
	  console.log('Syntax:\n' +
	  				'\tgrunt release (pre_vcs, do_svn, do_git, clean:post_build)\n' +
	  				'\tgrunt pre_vcs (update plugin version number in files, make languages/.pot)\n' +
	  				'\tgrunt do_svn (svn_export, copy:svn_assets, copy:svn_trunk, copy:svn_tag, push_svn)\n' +
	  				'\tgrunt update_svn (copy:svn_assets copy:svn_trunk, copy:svn_tag, push_svn)\n' +
	  				'\tgrunt do_git (gitattributes, gitcommit, gittag, gitpush)'
	  	);
	});

	grunt.registerTask( 'default', ['syntax'] );
	grunt.registerTask( 'version_number', [ 'replace:reamde_md', 'replace:reamde_txt', 'replace:plugin_php', 'replace:plugin_customizer_php' ] );
	grunt.registerTask( 'pre_vcs', [ 'version_number' /*, 'makepot'*/] );
	grunt.registerTask( 'changelog', [ 'githubChanges:dist'] );


	grunt.registerTask( 'do_svn', [ 'svn_export', 'copy:svn_assets', 'copy:svn_trunk', 'copy:svn_tag', 'push_svn' ] );
	grunt.registerTask( 'update_svn', [ 'copy:svn_assets', 'copy:svn_trunk', 'copy:svn_tag', 'push_svn' ] );
	grunt.registerTask( 'do_git', [  'gitcommit', 'gittag', 'gitpush' ] );
	grunt.registerTask( 'release', [ 'pre_vcs',  'do_git', /*'do_svn'*/, 'clean:post_build' ] );

};

/**
 * Helper
 */
// from http://stackoverflow.com/a/4026828/1434155
Array.prototype.diff = function(a) {
    return this.filter(function(i) {return a.indexOf(i) < 0;});
};
