#!/bin/bash
# desc          : This script will replace the settings according to the specified environment name and delete the non-compliant files
# author        : William.Tu
# create        : 2021/09/15
# update        : 2023/03/01
# usage         : bash deploy_setting.sh -u stg
# notes         : This script will use MV and RM, please use it carefully.
# bash_version  : version 4.4.23(1)-release
#==============================================================================
echo "* Change directory into prject root path"
#cd $(dirname $(dirname $0) )
cd $( dirname $0)/../
pwd

### --- Set up default
#my_extension='php'
my_regex_extension=".*\.\(php\|script\|sh\|pem\|properties\|xml\)$"
my_prefix_use=''
my_prefix_arr=("stg" "prod")
#my_folder='./'
my_workdir=$(dirname $0)/../
my_folder_arr=("./")

### --- Get args
function show_usage (){
    printf "\n"
    printf "Usage: $0 [options [parameters]]\n"
    printf "\n"
    printf "Options:\n"
    #printf " -e|--ext, Specify the affected file extension, example '-s php'\n"
    #printf " -p|--prefix, Replace or delete files with specified prefix, example '-p test -p stg' for test.*.php and stg.*.php\n"
    printf " -u|--use, Replace with files based on this prefix, example: '-u stg' or '-u prod' \n"
    printf " -h|--help, Print help\n"
    return 0
}

while [ ! -z "$1" ]; do
  case "$1" in
    #-e|--ext)
    #    shift
    #    my_extension=$1
    #    ;;
    #-p|--prefix)
    #    shift
    #    my_prefix_arr+=($1)
    #    #declare -p my_prefix_arr
    #    ;;
    -u|--use)
        shift
        if [[ ! " ${my_prefix_arr[*]} " =~ " $1 " ]]; then
            # whatever you want to do when array doesn't contain value
            error_exit "ERROR! argument $1 is invalid, only accept: [${my_prefix_arr[*]}]";
            show_usage
            exit 1;
        fi
        #echo "You entered number as: $1"
        my_prefix_use=$1
        ;;
    *)
        show_usage
        exit 1;
        ;;
  esac
shift
done

### --- Check Important argument isset or not
echo "* Check argument"
if ! [[ " ${my_prefix_arr[*]} " =~ " ${my_prefix_use} " ]]
then
    echo ">> ERROR! Please check out command usage, ex:'-u stg' !"
    exit 1;
fi

### ---- Checking stamp
echo "* Check if the stamp has not yet been recorded"

FILE=deploy/DEPLOY
if [ -f "$FILE" ]; then
    echo ">> WARNING! This project has already pre-deployed!"
    cat $FILE
    exit 1
fi


### --- Pre-deploy
echo "* Replace config file with prefix"
echo "- working directory: ${my_workdir}"
#echo "- ext: ${my_extension}"
echo "- pre: ${my_prefix_arr[*]}"
echo "- use: ${my_prefix_use}"

for prefix in "${my_prefix_arr[@]}"
do
    for my_folder in "${my_folder_arr[@]}"
    do
        folder=${my_workdir}${my_folder}
        echo "- processing with prefix: $prefix in folder: $folder";
        if [[ $prefix = $my_prefix_use ]]
        then
            #for f in $(find ${folder} -name "${prefix}.*.${my_extension}"); do
            for f in $(find ${folder} -name "${prefix}.*" -regex "${my_regex_extension}"); do
                echo "- - mv" "$f" "${f%/*}/${f##*/${my_prefix_use}.}";
                mv "$f" "${f%/*}/${f##*/${my_prefix_use}.}" || { echo 'failed to move file' ; exit 1; }
            done
        else
            #for f in $(find ${folder} -name "${prefix}.*.${my_extension}"); do
            for f in $(find ${folder} -name "${prefix}.*" -regex "${my_regex_extension}"); do
                echo "- - rm" "$f";
                rm "$f" || { echo 'failed to remove file' ; exit 1; }
            done
        fi
    done;
done;


#echo "* cleaning files: rm -R ${my_workdir}bin/"
#rm -R "${my_workdir}bin/"
#exit 0


echo '* writing stamp to DEPLOY file'
echo -n "${my_prefix_use}" > deploy/DEPLOY || { echo 'failed to write stamp to DEPLOY file' ; exit 1; }


echo "* checking date"
date
echo "* setting date to Asia/Taipei"
sudo timedatectl set-timezone "Asia/Taipei"
date

echo "* writing package file log"
#find cht -type f  > deploy/FILES || { echo 'failed to write package file list to FILES file' ; exit 1; }
# UPDATE_LIST-YYYY-MM-DD_HH-MM格式，如UPDATE_LIST-2022-04-13_16-47

#TZ=America/New_York date
#TZ=Asia/Taipei date
#TODAY=$(TZ=America/New_York date +"%Y-%m-%d_%H-%M")
#TODAY=$(TZ=Asia/Taipei date +"%Y-%m-%d_%H-%M")
#Asia/Taipei
TODAY=$(date +"%Y-%m-%d_%H-%M")
find . -type f  > deploy/UPDATE_LIST-$TODAY || { echo 'failed to write package file list to FILES file' ; exit 1; }

#cd ./cht && find * -type f  > deploy/FILES || { echo 'failed to write package file list to FILES file' ; }

echo '* finish'
exit 0;
