#!/bin/bash
repo="/path/to/my-repo"
branch="main"
cd $repo
if [[ $(git branch --show-current) != $branch ]]; then
    echo "WARNING: Not deploying to $repo - we're not on branch main as expected."
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
