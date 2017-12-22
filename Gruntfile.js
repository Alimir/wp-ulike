'use strict';
module.exports = function(grunt) {

    // load all grunt tasks matching the `grunt-*` pattern
    require('load-grunt-tasks')(grunt);
    require('time-grunt')(grunt);

    grunt.initConfig({

        pkg: grunt.file.readJSON('package.json'),

        // Meta definitions
        meta: {
            project:   "wp-ulike",
            version:   "<%= pkg.title || pkg.name %> - v<%= pkg.version %>",
            copyright: "<%= pkg.author.name %> <%= grunt.template.today('yyyy') %>",

            header: "/*\n" +
                " *  <%= meta.version %>\n" +
                " *  <%= pkg.homepage %>\n" +
                " *\n" +
                " *  <%= pkg.description %>\n" +
                " *\n" +
                " *  <%= meta.copyright %>" +
                " */\n",

            phpheader: "\n" +
                " * @package    <%= pkg.name %>\n" +
                " * @author     <%= pkg.author.name %> <%= grunt.template.today('yyyy') %>\n" +
                " * @link       <%= pkg.homepage %>",


            buildDir: "build",
            projectSubDir: '<%= meta.project %>',
            buildPath:     '<%= meta.buildDir %>/<%= meta.projectSubDir %>',
            installableZipFile: '<%= meta.project %>', // '<%= meta.project %>-installable'
            zipBuildPath: '<%= meta.buildDir %>/<%= meta.installableZipFile %>.zip'
        },

        // javascript linting with jshint
        jshint: {
            options: {
                jshintrc: '.jshintrc',
                "force": true
            },

            gruntFile: {
                // you can overrides global options for this target here
                options: {},
                files: {
                    src: ['Gruntfile.js']
                }
            },

            frontJsScript: {
                // you can overrides global options for this target here
                options: {},
                files: {
                    src: ['assets/js/wp-ulike.js']
                }
            }
        },

        // watch and compile scss files to css
        compass: {
            options: {
                config: 'assets/sass/config.rb',
                sassDir: 'assets/sass',
                cssDir: 'assets/css',
                sourcemap: false
            },

            back_dev: {
                options: {
                    sassDir: 'admin/classes/sass',
                    cssDir: 'admin/classes/css/',
                    environment: 'development',
                    watch:true,
                    trace:true,
                    outputStyle: 'compact' // nested, expanded, compact, compressed.
                }
            },

            front_dev: {
                options: {
                    sassDir: 'assets/sass',
                    cssDir: 'assets/css',
                    specify: ['assets/sass/wp-ulike.scss'],
                    environment: 'development',
                    watch:true,
                    trace:true,
                    outputStyle: 'expanded' // nested, expanded, compact, compressed.
                }
            },

            front_build: {
                options: {
                    sassDir: 'assets/sass',
                    cssDir: 'assets/css',
                    specify: ['assets/sass/wp-ulike.scss'],
                    environment: 'development',
                    watch:false,
                    trace:true,
                    outputStyle: 'expanded' // nested, expanded, compact, compressed.
                }
            }
        },        

        // Generate POT file
        makepot: {
            target: {
                options: {
                    domainPath: 'lang',
                    mainFile: 'wp-ulike.php',
                    potFilename: 'wp-ulike.pot',
                    potHeaders: {
                        poedit: true,
                        'Report-Msgid-Bugs-To': 'https://wordpress.org/plugins/wp-ulike/',
                        'Last-Translator': 'Alimir <info@alimir.ir>',
                        'Language-Team': 'Alimir <info@alimir.ir>',
                        'x-poedit-keywordslist': '__;_e;__ngettext:1,2;__ngettext_noop:1,2;_n:1,2;_x:1,2c;_nx:4c,1,2;_nx_noop:4c,1,2;_ex:1,2c;esc_attr__;esc_attr_e;esc_attr_x:1,2c;esc_html__;esc_html_e;esc_attr_ex:1,2c;esc_html_x',
                        'x-poedit-country': 'United States',
                        'x-textdomain-support': 'yes',
                    },
                    type: 'wp-plugin',
                    updatePoFiles: true
                }
            }
        },

        po2mo: {
            files: {
                src: 'lang/*.po',
                expand: true,
            }
        },

        // merge js files
        concat: {

            frontJsScripts: {
                options: {

                    banner: "/*! <%= meta.version %>\n" + 
                        " *  <%= pkg.homepage %>\n" +
                        " *  <%= meta.copyright %>;\n" +
                        " */\n",

                    process: function(src, filepath) {
                        var separator = "\n\n/* ================== " + filepath + " =================== */\n\n\n";
                        return (separator + src).replace(/;\s*$/, "") + ";"; // make sure always a semicolon is at the end
                    },
                },
                src: [
                    'assets/js/src/toastr.js',
                    'assets/js/src/wordpress-ulike.js',
                    'assets/js/src/scripts.js'
                ],
                dest: 'assets/js/wp-ulike.js'
            },

            adminJsScripts: {
                options: {

                    banner: "/*! <%= meta.version %>\n" + 
                        " *  <%= pkg.homepage %>\n" +
                        " *  <%= meta.copyright %>;\n" +
                        " */\n",

                    process: function(src, filepath) {
                        var separator = "\n\n/* ================== " + filepath + " =================== */\n\n\n";
                        return (separator + src).replace(/;\s*$/, "") + ";"; // make sure always a semicolon is at the end
                    },
                },
                src: [
                    'admin/classes/js/src/chart.min.js',
                    'admin/classes/js/src/jquery.vmap.min.js',
                    'admin/classes/js/src/jquery.vmap.world.js',
                    'admin/classes/js/src/scripts.js',
                ],
                dest: 'admin/classes/js/statistics.js'
            }

        },

        // css minify
        cssmin: {
            options: {
                keepSpecialComments: 1
            },
            target: {
                files: {
                    'assets/css/wp-ulike.min.css': ['assets/css/wp-ulike.css']
                }
            }
        },     

        clean: {
            build: [
                '<%= meta.buildPath %>', '<%= meta.zipBuildPath %>',
            ],
            version: [
                'build/*.txt',
            ]
        },

        // JS minification
        uglify: {
            options: {
                mangle: true,
                preserveComments: 'some'
            }, 

            frontJsScripts: {
                src: '<%= concat.frontJsScripts.dest %>',
                dest: 'assets/js/wp-ulike.min.js'
            }     
        },

        preprocess : {
            options: {
                context : {
                    VERSION: "<%= pkg.version %>",
                    DEV  : true,
                    TODO : true,
                    LITE : false,
                    PRO  : false,
                    HEADER: "<%= meta.phpheader %>"
                }
            },
            pro : {
                src : [ '<%= meta.buildPath %>/**/*.php', '<%= meta.buildPath %>/**/*.css', '<%= meta.buildPath %>/README.txt' ],
                options: {
                    inline : true,
                    context : {
                        DEV  : false,
                        TODO : false,
                        LITE : false,
                        PRO  : true
                    }
                }
            },
            liteOfficial : {
                src : [ '<%= meta.buildPath %>/**/*.php', '<%= meta.buildPath %>/**/*.css', '<%= meta.buildPath %>/README.txt' ],
                options: {
                    inline : true,
                    context : {
                        DEV  : false,
                        TODO : false,
                        LITE : true,
                        PRO  : false
                    }
                }
            }
        },

        shell:{

            install:{
                command: "brew install jpegoptim; brew install pngquant; mkdir <%= meta.buildPath %>"
            },
            // Sync package.json version with git repo version
            updateVersion:{
                command: 'npm version $(git describe --tags `git rev-list --tags --max-count=1`);'
            },
            // Increase package.json version one step
            bumpVersion: {
                command: 'npm version patch'
            },

            cleanBuildDotFiles: {
                command: ' find <%= meta.buildDir %> -name ".DS_Store" -delete' // exclude dotfiles
            },
            zipBuild: {
                command: 'cd <%= meta.buildDir %>; zip -FSr -9 <%= meta.installableZipFile %> <%= meta.projectSubDir %> -x */\.*; cd ..;' // exclude dotfiles
            },
            zipDlPack: {
                command: 'cd <%= meta.buildDir %>; zip -FSr -9 <%= meta.project %>-download-package * -x /<%= meta.projectSubDir %>/* */\.*; cd ..;' // exclude dotfiles
            },
            createTextVersion:{
                command: 'echo Latest version: v<%= pkg.version %> >> <%= meta.buildDir %>/<%= pkg.version %>.txt'
            },

            findLitePngs:{
                command: "find ./<%= meta.buildPath %>/ -name '*.png'"
            },
            compressLitePngs:{
                command: "find ./<%= meta.buildPath %>/ -name '*.png' -exec pngquant --speed 3 --quality=65-80 --skip-if-larger --ext .png --force 256 {}  \\;"
            },
            getLitePngsSize:{
                command: "find ./<%= meta.buildPath %>/ -name '*.png' -exec du -ch {} + | grep total$ "
            },

            findLiteJpgs:{
                command: "find ./<%= meta.buildPath %>/ -name '*.jpg'"
            },
            compressLiteJpgs:{
                command: "find ./<%= meta.buildPath %>/ -name '*.jpg' -exec jpegoptim -m80 -o -p  {} \\;"
            },
            getLiteJpgsSize:{
                command: "find ./<%= meta.buildPath %>/ -name '*.jpg' -exec du -ch {} + | grep total$ "
            }
        },

        // Running multiple blocking tasks
        concurrent: {
            watch_frontend_scss: {
                tasks: [ 'compass_dev', 'watch', 'compass:front_dev' ],
                options: {
                    logConcurrentOutput: true
                }
            }
        },

        // watch for changes and trigger sass, jshint, uglify and livereload
        watch: {
            concat_front_js_scripts: {
                files: ['assets/js/src/*.js'],
                tasks: ['concat:frontJsScripts', 'uglify:frontJsScripts']
            },

            concat_admin_js_scripts: {
                files: ['admin/classes/js/src/*.js'],
                tasks: ['concat:adminJsScripts']
            },

            livereload: {
                options: { livereload: true },
                files: ['*.css', 'assets/css/*.css',
                        'assets/js/src/*.js', 'assets/js/*.js',
                        'assets/img/**/*.{png,jpg,jpeg,gif,webp,svg}'
                        ]
            }
        },         

        // deploy via rsync
        deploy: {
            options: {
                args: ["--verbose --delete-after"], // z:compress while transfering data, P: display progress
                exclude: [
                        '.git*', 'node_modules', 'Gruntfile.js', 'package.json', 'composer.json',
                        'assets/js/src', 'admin/classes/js/src', 'readme.md', '.jshintrc', 'build', '.*', '.ds_store', 'package-lock.json',
                        'config.rb', 'assets/sass/', 'admin/classes/sass/'
                ],
                recursive: true,
                syncDestIgnoreExcl: true
            },

            build: {
                options: {
                    src: "./",
                    dest: "<%= meta.buildPath %>"
                }
            },

            lite: {
                options: {
                    exclude: [
                        '.git*', 'node_modules', 'Gruntfile.js', 'package.json', 'composer.json',
                        'assets/js/src', 'admin/classes/js/src', 'readme.md', '.jshintrc', 'build', '.*', '.ds_store', 'package-lock.json',
                        'config.rb', 'assets/sass/', 'admin/classes/sass/'
                    ],
                    src: ['./'],
                    dest: "<%= meta.buildPath %>"
                }
            }

        }

    });

    grunt.registerTask( 'install'       , ['shell:install'] );
    grunt.registerTask( 'compress'      , ['shell:compressLitePngs', 'shell:compressLiteJpgs'] );

    grunt.registerTask( 'buildVersion'  , ['clean:version', 'shell:createTextVersion'] );

    // rename tasks
    grunt.renameTask('rsync', 'deploy');

    // register task
    grunt.registerTask( 'syncversion'   , ['shell:updateVersion'] );
    grunt.registerTask( 'bump'          , ['shell:bumpVersion'  ] );

    grunt.registerTask( 'i18n'          , [ 'makepot' , 'po2mo' ] );

    grunt.registerTask( 'compass_dev'   , ['compass:back_dev'] );

    // compress the product in one pack
    grunt.registerTask( 'pack'          , ['shell:zipBuild'] );

    // deploy the lite version in /build folder
    grunt.registerTask( 'beta'          , ['clean:build', 'compass:front_build', 'cssmin', 'deploy:lite', 'shell:cleanBuildDotFiles', 'compress'] );

    // build the final lite version in /build folder and pack the product
    grunt.registerTask( 'build'         , ['concat', 'uglify', 'beta', 'preprocess:liteOfficial', 'buildVersion', 'pack'] );

    // register task
    grunt.registerTask( 'default'       , ['concat','cssmin', 'uglify']);

    grunt.registerTask( 'dev', ['concurrent'] );

};
