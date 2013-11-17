#/bin/bash

dimensions="24 34 48 64"
files=`ls *.svg | sed -s 's/\.svg//g'`

for f in $files; do
    for d in $dimensions; do
        inkscape -z -e $f-$d.png -w $d -h $d $f.svg
    done
done