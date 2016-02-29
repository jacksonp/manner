#!/usr/bin/env bash

for f in ../manlib/man/man1/*
do
  echo ${f}
  ./manner.php ${f} > /tmp/man && $HOME/bin/tidy-html5/build/cmake/tidy -indent -omit -utf8 --tidy-mark no --wrap 0 -q /tmp/man > renders/$(basename ${f}).html
done
