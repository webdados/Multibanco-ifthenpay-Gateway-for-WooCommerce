#! /bin/bash

cd /Users/marcoalmeida/Documents/Websites/_local/wordpress-testing/app/public/wp-content/plugins/

rm multibanco-ifthen-software-gateway-for-woocommerce.zip

zip -r "multibanco-ifthen-software-gateway-for-woocommerce.zip" "multibanco-ifthen-software-gateway-for-woocommerce" \
    -x *.DS_Store* \
    -x *.git* \
    -x *.svn* \
    -x *.idea* \
    -x *.phpcs* \
    -x *__MACOSX* \
    -x *roadmap.txt* \
    -x *_build* \
    -x *node_modules* \
    -x *createzip.command*