#!/bin/sh
# flowjs (glitchr-studio/flowjs, master) through JSDoc 4's explain mode: real/jsdoc.json, real/jsdoc.COMMIT.
set -e
apk add -q git >/dev/null
rm -rf /tmp/src /tmp/tools && mkdir -p /tmp/tools
git clone -q --depth 1 https://github.com/glitchr-studio/flowjs /tmp/src
git -C /tmp/src rev-parse HEAD > /real/jsdoc.COMMIT
cd /tmp/tools && npm init -y >/dev/null && npm i -s --no-audit --no-fund jsdoc@4.0.5 >/dev/null
cd /tmp && /tmp/tools/node_modules/.bin/jsdoc -X /tmp/src/src/js > /real/jsdoc.json
echo "$(wc -c < /real/jsdoc.json) bytes"
