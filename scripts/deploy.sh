#!/bin/bash
if [ -n $TRAVIS_TAG ]
then
    printf "machine git.fury.io\nlogin $GEMFURY_USER\npassword $GEMFURY_PASSWORD" > ~/.netrc
    git remote add fury "https://git.fury.io/$TRAVIS_REPO_SLUG.git"
    git push --tags fury HEAD:master
fi