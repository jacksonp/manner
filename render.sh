#!/usr/bin/env bash

rm renders/*

for f in ../manlib/man/man1/*
do
  echo ${f}
  ./manner.php ${f} > /tmp/manner-render-sh && $HOME/bin/tidy-html5/build/cmake/tidy -indent -omit -utf8 --tidy-mark no --wrap 0 -q /tmp/manner-render-sh > renders/$(basename ${f}).html
done
