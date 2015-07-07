#!/bin/bash 

# 
# Bash script for the generation of code documentation. 
#
 
rm -rf doc 

mkdir doc 
mkdir doc/apigen
mkdir doc/jsdoc

php rsc/apigen.phar generate \
    -s "src/application/controllers,src/application/models,src/application/libraries" \
    -d "doc/apigen" --exclude "*external*" --tree --todo --template-theme "bootstrap"


#jsdoc "src/assets/js" -d "doc/jsdoc"