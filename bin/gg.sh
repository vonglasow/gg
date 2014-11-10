#!/bin/sh
php gg.php $1 > toto.gv && dot -Tpng toto.gv -o output.png; rm -rf toto.gv
