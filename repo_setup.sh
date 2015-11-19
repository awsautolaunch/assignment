#!/bin/bash

# Check out submodules.
git submodule update --init --recursive

# Setup hooks.
cd $(git rev-parse --show-toplevel)
ln -sf ../../hooks/pre-commit .git/hooks/pre-commit
ln -sf ../../../../hooks/post-commit .git/modules/ops/hooks/post-commit
