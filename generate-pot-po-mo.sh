#!/usr/bin/env bash

TEXTDOMAIN=${PWD##*/}

#generate POT file from source
find . -name "*.php" > filelist
xgettext --keyword=__ --keyword=_e --keyword=_n:1,2 --keyword=_x:1,2c \
		 --keyword=_ex:1,2c --files-from=filelist --from-code=UTF-8 --language=PHP -o languages/$TEXTDOMAIN.pot
rm -f filelist

cd languages
for filename in ./*.po; do
    #update PO files from POT
    msgmerge --update --no-fuzzy-matching --backup=off $filename $TEXTDOMAIN.pot

    #update MO file from PO file
    msgcat $filename | msgfmt -o $(basename "$filename" .po).mo -

done
