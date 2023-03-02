#!/bin/bash
#pwd
#ls -l
echo "* Change directory into project root path"
#cd $(dirname $(dirname $0) )
cd $( dirname $0)/../
pwd

echo "* Get the current git describe"
describe="`git describe --tags --always`"
sha1="`git show -s --format=%H`"

#describe="`git describe | echo default`"
#describe="`git describe | echo default`"#describe="`git describe | echo NONE-0- git`"
echo "describe:"${describe}
#sha1="`git describe --tags --always`"
echo "sha1:"${sha1}

echo "* Get the current git branch"
#branch=$(Build.SourceBranchName)
branch="`git branch --show-current`"
#branch="`git symbolic-ref HEAD 2> /dev/null | cut -b 12-`"
#branch="${branch:-$(Build.SourceBranchName)}"
echo "branch:"${branch}

if [[ -n $1 ]]; then
    branch=$1
    echo "set branch name as '$1' with argument"
fi

echo "* Writing git describe as version to file..."
echo -n "${sha1}-${describe}-${branch}" > deploy/VERSION

echo "* cat deploy/VERSION"
cat deploy/VERSION -v