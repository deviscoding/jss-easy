# shellcheck shell=bash
# NOTE THIS FILE IS FOR USE IN THE EXAMPLES ONLY; USE 'jhelper prep' to generate.
JHELPER="../dist/jhelper.phar"
JH_PASS="${JHELPER} badge --format=pass "
JH_SUCCESS="${JHELPER} badge --format=success "
JH_FAIL="${JHELPER} badge --format=fail "
JH_ERROR="${JHELPER} badge --format=error "
JH_MSG="${JHELPER} msg --width=50 "