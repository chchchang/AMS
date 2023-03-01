#!/bin/bash

MIN_VERSION="7.2.0"
MAX_VERSION="7.4.999"
PHP_VERSION=`php -r 'echo PHP_VERSION;'`

function version_compare() {
    COMPARE_OP=$1;
    TEST_VERSION=$2;
    RESULT=$(php -r 'echo version_compare(PHP_VERSION, "'${TEST_VERSION}'", "'${COMPARE_OP}'") ? "TRUE" : "";')

    test -n "${RESULT}";
}

if ( version_compare "<" "${MIN_VERSION}" || version_compare ">" "${MAX_VERSION}" ); then
    echo "PHP Version ${PHP_VERSION} must be between ${MIN_VERSION} - ${MAX_VERSION}";
    exit 1;
fi

echo "PHP Version ${PHP_VERSION} is good!";

#$len = ; return (substr(PHP_VERSION, 0, strlen($ss)) === $ss);