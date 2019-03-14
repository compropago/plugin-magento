if [ -f Compropago_Payment_Extension-3.0.0.tgz ]; then
    echo Delete old file
    rm Compropago_Payment_Extension-3.0.0.tgz
fi

echo Remove .DS_Store files
find . -name .DS_Store -print0 | xargs -0 git rm -f --ignore-unmatch

echo "Building tgz module for Magento"
tar -cvzf Compropago_Payment_Extension-3.0.0.tgz app lib LICENSE package.xml README.md
