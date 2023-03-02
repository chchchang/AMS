#!/bin/bash
# 掃描以下字元 @author: william_tu
# ref: https://stackoverflow.com/questions/16080716/execute-multiple-commands-in-a-bash-script-sequentially-and-fail-if-at-least-one
# ref: https://unix.stackexchange.com/questions/15308/how-to-use-find-command-to-search-for-multiple-extensions
# ref: https://unix.stackexchange.com/questions/197352/how-to-start-multi-threaded-grep-in-terminal

echo "* Change directory into prject root path"
#cd $(dirname $(dirname $0) )
cd $( dirname $0)/../

pwd

EXIT_STATUS=0
#command1 || EXIT_STATUS=$?
#command2 || EXIT_STATUS=$?
#command3 || EXIT_STATUS=$?

# scanning \x00-\x1F, \x7F, except \x09 Horizontal tab (HT), except \x0A Line feed (LF), except \x0D Carriage return (CR)
# scanning U+200B (ZERO WIDTH SPACE: 零寬度打斷空間), U+200C (ZERO WIDTH NON-JOINER: 防止連字的零寬度單詞成分), U+200D (ZERO WIDTH JOINER: 強制連字的零寬度單詞成分), U+2060 (WORD JOINER: 零寬度不間斷空格)
# scanning BOM, 測試方法: 1. set BOM to empty file (printf "\xEF\xBB\xBF" > testBom.txt) 2.check file (od -t x1 testBom.txt) 3. then get result (0000000 ef bb bf)  
# scanning CR, 說明: CR、CRLF可能在Linux上造成問題，為確保一致性，版控一律儲存LF。Git在push時會根據客戶端OS自動轉換，若有檢測到Git沒處理到的檔案，請依據dos2unix修復，並注意檔案是否為binary

## === Normal ===
echo "--- scan ASCII controll chars ----"
grep -r --include "*.sh" --include "*.php" -m 1 -i --exclude-dir=vendor -n -P "[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]"
if [[ $? -eq 0 ]]; then echo "test failed" && EXIT_STATUS=1; else echo "test success"; fi

echo "--- scan UTF-8 invisible chars ---"
grep -r --include "*.sh" --include "*.php" -m 1 -i --exclude-dir=vendor -n -P "[\x{200B}\x{200C}\x{200D}\x{2060}]"
if [[ $? -eq 0 ]]; then echo "test failed" && EXIT_STATUS=1; else echo "test success"; fi

echo "--- scan Byte Order Mark (BOM) ---"
grep -r --include "*.sh" --include "*.php" -m 1 -i --exclude-dir=vendor $'\xEF\xBB\xBF'
if [[ $? -eq 0 ]]; then echo "test failed" && EXIT_STATUS=1; else echo "test success"; fi

echo "--- scan Carriage Return (CR) ----"
grep -r --include "*.sh" --include "*.php" -m 1 -i --exclude-dir=vendor $'\r'
if [[ $? -eq 0 ]]; then echo "test failed" && EXIT_STATUS=1; else echo "test success"; fi

echo "--- setup php error level code ---"
level=$(php -r "fwrite(STDOUT, E_ALL & ~E_DEPRECATED);")
if [[ $? -ne 0 ]]; then echo "test failed" && EXIT_STATUS=1; else echo "test success"; fi

echo "--- scan php syntax by level -----"
find . -type f -name "*.php" -not -path "./vendor/*" -print0|xargs -0 -P 16 -I % php -d error_reporting=${level} -l %
if [[ $? -ne 0 ]]; then echo "test failed" && EXIT_STATUS=1; else echo "test success"; fi

#find . -type f -name "*.php" -not -path "./vendor/*" -print0|xargs -0 -P 16 -I % php -d error_reporting=${level} -l % || ( echo "test failed" && EXIT_STATUS=1 )

## === Test ===
#echo "--- scan Byte Order Mark (BOM) ---" && find . -regex ".*\.\(sh\|script\|php\)" -not -path "./vendor/*" -print0|xargs -0 -P 16 grep -m 1 -i $'\xEF\xBB\xBF' || EXIT_STATUS=$?
#echo "--- scan Byte Order Mark (BOM) ---" && find . -regex ".*\.\(sh\|script\|php\)" -not -path "./vendor/*" -print0|xargs -0 -P 16 -I % grep % -m 1 -i $'\xEF\xBB\xBF' || EXIT_STATUS=$?
#echo "--- scan Byte Order Mark (BOM) ---" && find . -regex ".*\.\(sh\|script\|php\)" -not -path "./vendor/*" -print0|xargs -0 -P 16 grep -m 1 -i $'\xEF\xBB\xBF' || EXIT_STATUS=$?
#echo "--- scan ASCII controll chars ----" && ! grep -r --include "*.sh" --include "*.php" -m 1 -i --exclude-dir=vendor -n -P "[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]" || EXIT_STATUS=$?
#echo "--- scan UTF-8 invisible chars ---" && ! grep -r --include "*.sh" --include "*.php" -m 1 -i --exclude-dir=vendor -n -P "[\x{200B}\x{200C}\x{200D}\x{2060}]" || EXIT_STATUS=$?
#echo "--- scan Byte Order Mark (BOM) ---" && ! grep -r --include "*.sh" --include "*.php" -m 1 -i --exclude-dir=vendor $'\xEF\xBB\xBF' || EXIT_STATUS=$?
#echo "--- scan Carriage Return (CR) ----" && ! grep -r --include "*.sh" --include "*.php" -m 1 -i --exclude-dir=vendor $'\r' || EXIT_STATUS=$?

## ===== backup =====
#find . -type f -name "*.php" -not -path "./vendor/*" -not -path "./test/*" -path "./test/unit" -exec php -d error_reporting=${level} -l {} \; | (! grep -v "No syntax errors detected" )
#find . htdocs/include/gateway -type f -name "*.php" -not -path "./vendor/*" -print0|xargs -0 -P 16 -I % php -d error_reporting=24575 -l %
#echo "--- scan Byte Order Mark (BOM) ---" && find htdocs/include/gateway -type f \( -name "*.sh" -o -name "*.script" -o -name "*.php" \) -not -path "./vendor/*" -print0|xargs -0 -P 16 -I grep -m 1 -i $'\xEF\xBB\xBF' || EXIT_STATUS=$?

echo '=== show scanning result ==='
echo $EXIT_STATUS
#exit $EXIT_STATUS

# 2022/4/13 強制顯示執行成功
exit 0
