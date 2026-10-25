#!/bin/sh
# Requests' documentation (psf/requests v2.32.3) through Sphinx 8.1.3's JSON builder: real/sphinx/json.
set -e
apk add -q git >/dev/null
pip -q install "sphinx==8.1.3"
rm -rf /tmp/src /real/sphinx && mkdir -p /real/sphinx
git clone -q --depth 1 -b v2.32.3 https://github.com/psf/requests /tmp/src
pip -q install -e /tmp/src
git -C /tmp/src rev-parse HEAD > /real/sphinx/COMMIT
sphinx-build -q -b json /tmp/src/docs /real/sphinx/json || true
ls /real/sphinx/json/objects.inv /real/sphinx/json/globalcontext.json
