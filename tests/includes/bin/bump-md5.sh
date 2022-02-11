#!/bin/bash

FILE_MD5=$(cat ./vendor/wordpress-autoload.php | md5)

echo "FILE_MD5: $FILE_MD5"
