#!/usr/bin/env bash

function renderFile {
  echo $1
  ./manner.php $1 > /tmp/manner-render-sh && $HOME/bin/tidy-html5/build/cmake/tidy -indent -omit -utf8 --tidy-mark no --wrap 0 -q /tmp/manner-render-sh > renders/$(basename $1).html
}


if [ "$#" -eq 1 ]; then
  renderFile $1
else
  rm renders/*
  for f in ../manlib/man/man1/*; do
    renderFile ${f}
  done
fi
