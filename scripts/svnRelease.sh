#!/usr/bin/env bash

zipfile=$1
version=$2
folder="temp"

rm -rf temp
unzip $zipfile -d $folder
cp -r $folder/* openpix-for-woocommerce/trunk/.
cd openpix-for-woocommerce
svn st | grep ^? | sed 's/? *//' | xargs -I fn svn add "fn"
svn ci -m "version ${version}"
svn cp trunk "tags/${version}"
svn ci -m "version ${version}"