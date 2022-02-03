#!/bin/bash
repo="/path/to/my-repo"
branch="main"
cd $repo

# git branch --show-current is more readable but only available since git 2.22.0
if [[ $(git symbolic-ref --short HEAD) != $branch ]]; then
    echo "WARNING: Not deploying to $repo - we're not on branch $branch as expected."
    exit 1
fi

if [[ -n $(git status --porcelain) ]]; then
    echo "WARNING: Not deploying to $repo - found uncommitted changes."
    exit 1
fi

if git status --porcelain --branch | grep -q ahead; then
    echo "WARNING: Not deploying to $repo - is ahead of its origin."
    exit 1
fi

echo "Deploying to $repo"
git pull
