#! /bin/bash
#
# Script to deploy from Github to WordPress.org Plugin Repository
# A modification of Dean Clatworthy's deploy script as found here: https://github.com/deanc/wordpress-plugin-git-svn
# The difference is that this script lives in the plugin's git repo & doesn't require an existing SVN repo.
# Source: https://github.com/thenbrent/multisite-user-management/blob/master/deploy.sh

#prompt for plugin slug
echo -e "Plugin Slug: \c"
read PLUGINSLUG

# main config, set off of plugin slug
CURRENTDIR=`pwd`
# CURRENTDIR="$CURRENTDIR/$PLUGINSLUG"
CURRENTDIR="$CURRENTDIR"
MAINFILE="$PLUGINSLUG.php" # this should be the name of your main php file in the wordpress plugin

# git config
GITPATH="$CURRENTDIR" # this file should be in the base of your git repository

# svn config
SVN_LOCAL_PATH="/home/alimir/SVN/$PLUGINSLUG" # path to a temp SVN repo. No trailing slash required and don't add trunk.
SVNURL="http://plugins.svn.wordpress.org/$PLUGINSLUG/" # Remote SVN repo on WordPress.org, with no trailing slash
SVNUSER="alimir" # your svn username

# Let's begin...
echo ".........................................."
echo
echo "Preparing to deploy WordPress plugin"
echo
echo ".........................................."
echo

# Check version in readme.txt is the same as plugin file
# on ubuntu $GITPATH/readme.txt seems to have an extra /
NEWVERSION1=$(grep -i "Stable tag:" $GITPATH/readme.txt | awk -F' ' '{print $NF}' | tr -d '\r')
echo "readme version: $NEWVERSION1"
NEWVERSION2=$(grep -i "Version:" $GITPATH/$MAINFILE | awk -F' ' '{print $NF}' | tr -d '\r')
echo "$MAINFILE version: $NEWVERSION2"

if [ "$NEWVERSION1" != "$NEWVERSION2" ]; then echo "Versions don't match. Exiting...."; exit 1; fi

echo "Versions match in README and PHP file. Let's proceed..."

cd $GITPATH

# ask if not clean (staged, unstaged, untracked)
if [ -n "$(git status --porcelain)" ]; then
  echo -e "Enter a commit message for this new version: \c"
  read COMMITMSG
#   git commit -am "$COMMITMSG"
else
    COMMITMSG=$(git log -1 --pretty=%B)
fi

echo "Tagging new version in git"
# git tag -a "$NEWVERSION1" -m "Tagging version $NEWVERSION1"

echo "Pushing latest commit to origin, with tags"
# git push origin master
# git push origin master --tags

if [ ! -d "$SVN_LOCAL_PATH" ]; then
    echo "Creating local copy of SVN repo ..."
    mkdir $SVN_LOCAL_PATH
  svn co $SVNURL $SVN_LOCAL_PATH
fi

echo "Ignoring github specific files and deployment script"
# svn propset svn:ignore wp-assets "deploy.sh
# README.md
# .git
# .gitignore" "$SVN_LOCAL_PATH/trunk/"

#couldn't get multi line patten above to ignore wp-assets folder
svn propset svn:ignore "deploy.sh"$'\n'"deploy-build.sh"$'\n'"wp-assets"$'\n'"README.md"$'\n'"readme.md"$'\n'".git"$'\n'"bower.json"$'\n'"Gruntfile.js"$'\n'".gitignore" "$SVN_LOCAL_PATH/trunk/"

#export git -> SVN
echo "Exporting the HEAD of master from git to the trunk of SVN"
# git checkout-index -a -f --prefix=$SVN_LOCAL_PATH/trunk/
rm -rf `find build -name Thumbs.db`
cp -R "$GITPATH/build/$PLUGINSLUG/" "$SVN_LOCAL_PATH/trunk/"

# sed commands to convert readme.md to readme.txt
# sed -e 's/^#\{1\} \(.*\)/=== \1 ===/g' -e 's/^#\{2\} \(.*\)/== \1 ==/g' -e 's/^#\{3\} \(.*\)/= \1 =/g' -e 's/^#\{4,5\} \(.*\)/**\1**/g' "readme.md" > "$SVN_LOCAL_PATH/trunk/readme.txt"

#if submodule exist, recursively check out their indexes
#if [ -f ".gitmodules" ]
#then
#echo "Exporting the HEAD of each submodule from git to the trunk of SVN"
#git submodule init
#git submodule update
#git submodule foreach --recursive 'git checkout-index -a -f --prefix=$SVN_LOCAL_PATH/trunk/$path/'
#fi

echo "Changing directory to SVN and committing to trunk"
cd $SVN_LOCAL_PATH/trunk/

#prompt for plugin slug
echo -e "SVN Commit Message: \c"
read SVNCOMMITMSG

# Add all new files that are not set to be ignored
svn status | grep -v "^.[ \t]*\..*" | grep "^?" | awk '{print $2}' | xargs svn add

# svn commit --username=$SVNUSER -m "$SVNCOMMITMSG"
svn ci --username=$SVNUSER -m "$SVNCOMMITMSG"


echo "Creating new SVN tag & committing it"
cd $SVN_LOCAL_PATH

if [ ! -d "$SVN_LOCAL_PATH/tags/$NEWVERSION1" ]
then
  svn copy trunk/ tags/$NEWVERSION1/
  cd $SVN_LOCAL_PATH/tags/$NEWVERSION1
  svn commit --username=$SVNUSER -m "Tagging version $NEWVERSION1"
fi


echo -e "Update Assets?(Y/N) \c"
read UPDATESVNASSETS

# Add assets
if [[ -d "$GITPATH/wp-assets" && ( "$UPDATESVNASSETS" = "Y" || "$UPDATESVNASSETS" = "y" ) ]]
then

  echo "Changing directory to SVN and committing to assets"
  cd $SVN_LOCAL_PATH/assets
  cp $GITPATH/wp-assets/* .

  svn status | grep -v "^.[ \t]*\..*" | grep "^?" | awk '{print $2}' | xargs svn add
  svn commit --username=$SVNUSER -m "$COMMITMSG"

fi

# echo "Removing temporary directory $SVN_LOCAL_PATH"
# rm -fr $SVN_LOCAL_PATH/

echo "*** FIN ***"
