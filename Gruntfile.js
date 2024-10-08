"use strict";
module.exports = function (grunt) {
  // load all grunt tasks matching the `grunt-*` pattern
  const sass = require('node-sass');
  require("load-grunt-tasks")(grunt);
  require("time-grunt")(grunt);

  grunt.initConfig({
    pkg: grunt.file.readJSON("package.json"),

    // Meta definitions
    meta: {
      project: "wp-ulike",
      version: "<%= pkg.title || pkg.name %> - v<%= pkg.version %>",
      copyright: "<%= pkg.author.name %> <%= grunt.template.today('yyyy') %>",

      header:
        "/*\n" +
        " *  <%= meta.version %>\n" +
        " *  <%= pkg.homepage %>\n" +
        " *\n" +
        " *  <%= pkg.description %>\n" +
        " *\n" +
        " *  <%= meta.copyright %>" +
        " */\n",

      phpheader:
        "\n" +
        " * @package    <%= pkg.name %>\n" +
        " * @author     <%= pkg.author.name %> <%= grunt.template.today('yyyy') %>\n" +
        " * @link       <%= pkg.homepage %>",

      buildDir: "build",
      projectSubDir: "<%= meta.project %>",
      buildPath: "<%= meta.buildDir %>/<%= meta.projectSubDir %>",
      installableZipFile: "<%= meta.project %>", // '<%= meta.project %>-installable'
      zipBuildPath: "<%= meta.buildDir %>/<%= meta.installableZipFile %>.zip",
    },

    // javascript linting with jshint
    jshint: {
      options: {
        jshintrc: ".jshintrc",
        force: true,
      },

      gruntFile: {
        // you can overrides global options for this target here
        options: {},
        files: {
          src: ["Gruntfile.js"],
        },
      },

      frontJsScript: {
        // you can overrides global options for this target here
        options: {},
        files: {
          src: ["assets/js/wp-ulike.js"],
        },
      },
    },

    sass: {
      options: {
        implementation: sass,
        sourceMap: false
      },
      dist: {
        files: {
          'assets/css/wp-ulike.css': 'assets/sass/wp-ulike.scss',
          'admin/assets/css/admin.css': 'admin/assets/sass/admin.scss',
          'admin/assets/css/plugins.css': 'admin/assets/sass/plugins.scss',
        }
      }
    },

    phplint: {
      options: {
        phpArgs: {
          "-d": null,
          "-f": null,
        },
      },
      all: {
        src: ["*.php", "**/*.php", "!node_modules/**", "!vendor/**"],
      },
    },

    // Generate POT file
    makepot: {
      target: {
        options: {
          domainPath: "/languages",
          mainFile: "wp-ulike.php",
          potFilename: "wp-ulike.pot",
          exclude: ["admin/settings/.*", "build/.*"],
          processPot: function (pot, options) {
            pot.headers["report-msgid-bugs-to"] = "https://wpulike.com";
            pot.headers["language-team"] = "WP ULike Team <info@wpulike.com>";
            return pot;
          },
          type: "wp-plugin",
          updatePoFiles: true,
        },
      },
    },

    // Convert po to mo
    potomo: {
      dist: {
        options: {
          poDel: false,
        },
        files: [
          {
            expand: true,
            cwd: "languages",
            src: ["*.po"],
            dest: "languages",
            ext: ".mo",
            nonull: true,
          },
        ],
      },
    },

    // Copy files from bower_component folder to right places
    copy: {
      // gMaps: {

      //     files: [
      //         {
      //             expand: true,     // Enable dynamic expansion.
      //             cwd: '<%= pkg.bower.components %>jqvmap/dist',      // Src matches are relative to this path.
      //             src: ['jquery.vmap.min.js', 'maps/jquery.vmap.world.js'],  // Actual pattern(s) to match.
      //             dest: 'admin/assets/js/src/plugins'   // Destination path prefix.
      //         }
      //     ]
      // },
      // chartjs: {
      //   files: [
      //     {
      //       expand: true, // Enable dynamic expansion.
      //       cwd: "node_modules/chart.js/dist", // Src matches are relative to this path.
      //       src: ["Chart.min.js"], // Actual pattern(s) to match.
      //       dest: "admin/assets/js/src/plugins/", // Destination path prefix.
      //     },
      //   ],
      // },
      // vueJs: {
      //   files: [
      //     {
      //       expand: true, // Enable dynamic expansion.
      //       cwd: "node_modules/vue/dist", // Src matches are relative to this path.
      //       src: ["vue.min.js", "vue.js"], // Actual pattern(s) to match.
      //       dest: "admin/assets/js/solo/vue/", // Destination path prefix.
      //     },
      //   ],
      // },
      // matchHeight: {
      //   files: [
      //     {
      //       expand: true, // Enable dynamic expansion.
      //       cwd: "node_modules/jquery-match-height/dist", // Src matches are relative to this path.
      //       src: ["jquery.matchHeight-min.js"], // Actual pattern(s) to match.
      //       dest: "admin/assets/js/src/plugins/", // Destination path prefix.
      //     },
      //   ],
      // },
    },

    // merge js files
    concat: {
      frontJsScripts: {
        options: {
          banner:
            "/*! <%= meta.version %>\n" +
            " *  <%= pkg.homepage %>\n" +
            " *  <%= meta.copyright %>;\n" +
            " */\n",

          process: function (src, filepath) {
            var separator =
              "\n\n/* ================== " +
              filepath +
              " =================== */\n\n\n";
            return (separator + src).replace(/;\s*$/, "") + ";"; // make sure always a semicolon is at the end
          },
        },
        src: [
          "assets/js/src/tooltip.js",
          "assets/js/src/wordpress-ulike-notifications.js",
          "assets/js/src/wordpress-ulike.js",
          "assets/js/src/scripts.js",
        ],
        dest: "assets/js/wp-ulike.js",
      },

      // adminJsPlugins: {
      //   options: {
      //     banner:
      //       "/*! <%= meta.version %>\n" +
      //       " *  <%= pkg.homepage %>\n" +
      //       " *  <%= meta.copyright %>;\n" +
      //       " */\n",

      //     process: function (src, filepath) {
      //       var separator =
      //         "\n\n/* ================== " +
      //         filepath +
      //         " =================== */\n\n\n";
      //       return (separator + src).replace(/;\s*$/, "") + ";"; // make sure always a semicolon is at the end
      //     },
      //   },
      //   src: [
      //     "node_modules/chart.js/dist/Chart.js",
      //     "node_modules/jquery-match-height/dist/jquery.matchHeight.js",
      //   ],
      //   dest: "admin/assets/js/plugins.js",
      // },

      // adminJsScripts: {
      //     options: {

      //         banner: "/*! <%= meta.version %>\n" +
      //             " *  <%= pkg.homepage %>\n" +
      //             " *  <%= meta.copyright %>;\n" +
      //             " */\n",

      //         process: function(src, filepath) {
      //             var separator = "\n\n/* ================== " + filepath + " =================== */\n\n\n";
      //             return (separator + src).replace(/;\s*$/, "") + ";"; // make sure always a semicolon is at the end
      //         },
      //     },
      //     src: [
      //         'admin/assets/js/src/*.js',
      //     ],
      //     dest: 'admin/assets/js/scripts.js'
      // }
    },

    // css minify
    cssmin: {
      options: {
        level: {
          1: {
            specialComments: 0,
          },
        },
      },
      target: {
        files: {
          "assets/css/wp-ulike.min.css": ["assets/css/wp-ulike.css"],
          "admin/assets/css/admin.css": ["admin/assets/css/admin.css"],
          "admin/assets/css/plugins.css": ["admin/assets/css/plugins.css"],
        },
      },
    },

    clean: {
      build: ["<%= meta.buildPath %>", "<%= meta.zipBuildPath %>"],
      version: ["build/*.txt"],
    },

    // JS minification
    uglify: {
      options: {
        mangle: true,
        preserveComments: "some",
      },

      frontJsScripts: {
        src: "<%= concat.frontJsScripts.dest %>",
        dest: "assets/js/wp-ulike.min.js",
      },
    },

    // JS minification
    terser: {
      options: {
        compress: true,
        mangle: true,
        output: {
          comments: false,
        }
      },
      main: {
        files: {
          "assets/js/wp-ulike.min.js": ["<%= concat.frontJsScripts.dest %>"]
        },
      },
    },

    preprocess: {
      options: {
        context: {
          VERSION: "<%= pkg.version %>",
          DEV: true,
          TODO: true,
          LITE: false,
          PRO: false,
          HEADER: "<%= meta.phpheader %>",
        },
      },
      pro: {
        src: [
          "<%= meta.buildPath %>/**/*.php",
          "<%= meta.buildPath %>/**/*.css",
          "<%= meta.buildPath %>/README.txt",
        ],
        options: {
          inline: true,
          context: {
            DEV: false,
            TODO: false,
            LITE: false,
            PRO: true,
          },
        },
      },
      liteOfficial: {
        src: [
          "<%= meta.buildPath %>/**/*.php",
          "<%= meta.buildPath %>/**/*.css",
          "<%= meta.buildPath %>/README.txt",
        ],
        options: {
          inline: true,
          context: {
            DEV: false,
            TODO: false,
            LITE: true,
            PRO: false,
          },
        },
      },
    },

    shell: {
      install: {
        command:
          "brew install jpegoptim; brew install pngquant; mkdir <%= meta.buildPath %>",
      },
      // Sync package.json version with git repo version
      updateVersion: {
        command:
          "npm version $(git describe --tags `git rev-list --tags --max-count=1`);",
      },
      // Increase package.json version one step
      bumpVersion: {
        command: "npm version patch",
      },

      cleanBuildDotFiles: {
        command: ' find <%= meta.buildDir %> -name ".DS_Store" -delete', // exclude dotfiles
      },
      zipBuild: {
        command:
          "cd <%= meta.buildDir %>; zip -FSr -9 <%= meta.installableZipFile %> <%= meta.projectSubDir %> -x */.*; cd ..;", // exclude dotfiles
      },
      zipDlPack: {
        command:
          "cd <%= meta.buildDir %>; zip -FSr -9 <%= meta.project %>-download-package * -x /<%= meta.projectSubDir %>/* */.*; cd ..;", // exclude dotfiles
      },
      createTextVersion: {
        command:
          "echo Latest version: v<%= pkg.version %> >> <%= meta.buildDir %>/<%= pkg.version %>.txt",
      },

      findLitePngs: {
        command: "find ./<%= meta.buildPath %>/ -name '*.png'",
      },
      compressLitePngs: {
        command:
          "find ./<%= meta.buildPath %>/ -name '*.png' -exec pngquant --speed 3 --quality=65-80 --skip-if-larger --ext .png --force 256 {}  \\;",
      },
      getLitePngsSize: {
        command:
          "find ./<%= meta.buildPath %>/ -name '*.png' -exec du -ch {} + | grep total$ ",
      },

      findLiteJpgs: {
        command: "find ./<%= meta.buildPath %>/ -name '*.jpg'",
      },
      compressLiteJpgs: {
        command:
          "find ./<%= meta.buildPath %>/ -name '*.jpg' -exec jpegoptim -m80 -o -p  {} \\;",
      },
      getLiteJpgsSize: {
        command:
          "find ./<%= meta.buildPath %>/ -name '*.jpg' -exec du -ch {} + | grep total$ ",
      },
    },

    // Running multiple blocking tasks
    concurrent: {
      watch_frontend_scss: {
        tasks: ["watch"],
        options: {
          logConcurrentOutput: true,
        },
      },
    },

    // watch for changes and trigger sass, jshint, uglify and livereload
    watch: {
      concat_front_js_scripts: {
        files: ["assets/js/src/*.js"],
        tasks: ["concat:frontJsScripts"],
      },

      concat_admin_js_plugins: {
        files: ["admin/assets/js/src/plugins/**/*.js"],
        tasks: ["concat:adminJsPlugins"],
      },

      compile_sass: {
        files: ["assets/sass/**/*.scss", "admin/assets/sass/**/*.scss"],
        tasks: ["sass:dist"],
      },

      compile_sass: {
        files: ["assets/sass/**/*.scss", "admin/assets/sass/**/*.scss"],
        tasks: ["sass:dist"],
      },

      // concat_admin_js_scripts: {
      //     files: ['admin/assets/js/src/*.js'],
      //     tasks: ['concat:adminJsScripts']
      // },

      livereload: {
        options: { livereload: 35985 },
        files: [
          "*.css",
          "assets/css/*.css",
          "assets/js/src/*.js",
          "assets/js/*.js",
          "assets/img/**/*.{png,jpg,jpeg,gif,webp,svg}",
        ],
      },
    },

    wp_deploy: {
      deploy: {
        options: {
          plugin_slug: "<%= meta.project %>",
          svn_user: "alimir",
          build_dir: "<%= meta.buildPath %>", //relative path to your build directory
          assets_dir: "wp-assets", //relative path to your assets directory (optional).
        },
      },
    },

    // deploy via rsync
    deploy: {
      options: {
        args: ["--verbose --delete-after"], // z:compress while transfering data, P: display progress
        exclude: [
          ".git*",
          "node_modules",
          "Gruntfile.js",
          "package.json",
          "composer.json",
          "assets/js/src",
          "admin/assets/js/src",
          "readme.md",
          ".jshintrc",
          "build",
          ".*",
          ".ds_store",
          "composer.lock",
          "package-lock.json",
          "config.rb",
          "assets/sass/",
          "admin/assets/sass/",
          "deploy.sh",
          "wp-assets",
          "docs",
          "README.md",
          "SUMMARY.md",
        ],
        recursive: true,
        syncDestIgnoreExcl: true,
      },

      build: {
        options: {
          src: "./",
          dest: "<%= meta.buildPath %>",
        },
      },
      prod_ir: {
        options: {
          exclude: [],
          src: "<%= meta.buildPath %>/",
          dest: "/home/wpulike.ir/public_html/wp-content/plugins/<%= meta.project %>/",
          host: "root@78.46.117.192",
        },
      },
      prod: {
        options: {
          exclude: [],
          src: "<%= meta.buildPath %>/",
          dest: "/home/wpulikec/www/wp-content/plugins/<%= meta.project %>/",
          host: "wpulikec@nl1-ts109.a2hosting.com",
          port: "7822",
        },
      },
      sandbox: {
        options: {
          exclude: [],
          src: "<%= meta.buildPath %>/",
          dest: "/home/u743-m0zi3gdikuac/www/maryamr1.sg-host.com/public_html/wp-content/plugins/<%= meta.project %>/",
          host: "u743-m0zi3gdikuac@35.207.156.31",
          port: "18765",
        },
      },
      lite: {
        options: {
          exclude: [
            ".git*",
            "node_modules",
            ".sass-cache",
            "dist",
            "Gruntfile.js",
            "package.json",
            "composer.json",
            "_devDependencies",
            "assets/js/src",
            "admin/assets/js/src",
            "readme.md",
            ".jshintrc",
            "build",
            ".*",
            ".ds_store",
            "composer.lock",
            "package-lock.json",
            "bower.json",
            "config.rb",
            "assets/sass/",
            "admin/assets/sass/",
            "deploy.sh",
            "docs",
            "wp-assets",
            "README.md",
            "SUMMARY.md",
          ],
          src: ["./"],
          dest: "<%= meta.buildPath %>",
        },
      },
    },
  });

  grunt.registerTask("install", ["shell:install"]);
  grunt.registerTask("compress", [
    "shell:compressLitePngs",
    "shell:compressLiteJpgs",
  ]);

  grunt.registerTask("buildVersion", [
    "clean:version",
    "shell:createTextVersion",
  ]);

  // rename tasks
  grunt.renameTask("rsync", "deploy");

  // phplint
  grunt.registerTask("php", ["phplint"]);

  // register task
  grunt.registerTask("syncversion", ["shell:updateVersion"]);
  grunt.registerTask("bump", ["shell:bumpVersion"]);

  grunt.registerTask("i18n", ["makepot", "potomo"]);

  // compress the product in one pack
  grunt.registerTask("pack", ["shell:zipBuild"]);

  // deploy the lite version in /build folder
  grunt.registerTask("beta", [
    "clean:build",
    "sass:dist",
    "cssmin",
    "deploy:lite",
    "shell:cleanBuildDotFiles",
    "compress",
  ]);

  // build the final lite version in /build folder and pack the product
  grunt.registerTask("build", [
    "concat",
    "terser",
    "beta",
    "preprocess:liteOfficial",
    "buildVersion",
    "pack",
  ]);

  grunt.registerTask("release", ["build", "wp_deploy:deploy"]);

  // register task
  grunt.registerTask("default", ["concat", "cssmin", "terser"]);

  grunt.registerTask("dev", ["concurrent"]);

  grunt.registerTask("update_dep", ["copy", "concat"]);

  grunt.registerTask("product", ["build", "deploy:prod"]);
  grunt.registerTask("sandbox", ["build", "deploy:sandbox"]);

  grunt.registerTask("product_fa", ["build", "deploy:prod_ir"]);
};
