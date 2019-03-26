#!/bin/bash

if [ -f Compropago_Payment_Extension-3.0.0.1.tgz ]; then
    echo -e "\033[1;31mDeleting old file\033[0m"
    rm Compropago_Payment_Extension-3.0.0.1.tgz
fi

echo -e "\033[1;33mRemove .DS_Store files\033[0m"
find . -name .DS_Store -print0 | xargs -0 git rm -f --ignore-unmatch

echo -e "\033[1;32mBuilding tgz module for Magento\033[0m"
tar -cvzf Compropago_Payment_Extension-3.0.0.1.tgz app lib LICENSE package.xml README.md
