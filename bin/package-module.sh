#!/usr/bin/env bash

rm -rf build
mkdir -p build/gamepanelio

cp -R language build/gamepanelio
cp -R vendor build/gamepanelio
cp -R views build/gamepanelio

cp composer.* build/gamepanelio
cp games.json build/gamepanelio
cp gamepanelio.php build/gamepanelio

cp LICENSE build/LICENSE.txt
cp instructions.txt build/

cd build
zip -r gamepanelio.zip * -x *.git*
