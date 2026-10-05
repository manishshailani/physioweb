#!/usr/bin/env bash
#
# Runs every automated check in this project, in order, and stops at the
# first failure (set -e) so a broken step is never masked by a later one
# that happens to pass. See tests/README.md for exactly what each step
# does and does not prove.
#
# Usage: bash tests/run_all.sh   (run from the project root, or anywhere --
# it cds to its own directory's parent first)

set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."

echo "== 1/3: PHP lint (php -l) across the entire codebase =="
find app database tests public config routes -name "*.php" -print0 \
    | xargs -0 -n1 php -l \
    | grep -v "No syntax errors detected" && { echo "Lint errors found above."; exit 1; } || true
echo "No syntax errors in any .php file."
echo

echo "== 2/3: Model/business-logic smoke tests (tests/smoke_test_models.php) =="
php tests/smoke_test_models.php
echo

echo "== 3/3: View-rendering + Controller-logic tests (tests/render_*_test.php) =="
for f in tests/render_*_test.php; do
    echo "--- $f ---"
    php "$f"
    echo
done

echo "All checks passed."
