#!/bin/sh
# TinyXML-2 (leethomason/tinyxml2 10.0.0) through Doxygen, with its own configuration (dox): real/doxygen/xml.
set -e
apk add -q git doxygen >/dev/null
rm -rf /tmp/src /real/doxygen && mkdir -p /real/doxygen
git clone -q --depth 1 -b 10.0.0 https://github.com/leethomason/tinyxml2 /tmp/src
git -C /tmp/src rev-parse HEAD > /real/doxygen/COMMIT
cd /tmp/src
(cat dox; printf 'OUTPUT_DIRECTORY=/real/doxygen\nGENERATE_XML=YES\nGENERATE_HTML=NO\nGENERATE_LATEX=NO\nXML_OUTPUT=xml\nQUIET=YES\nWARNINGS=NO\n') | doxygen - >/dev/null 2>&1
doxygen --version > /real/doxygen/VERSION
ls /real/doxygen/xml/index.xml
