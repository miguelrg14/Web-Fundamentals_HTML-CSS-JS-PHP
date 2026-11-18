#!/usr/bin/env bash
# End-to-end check of the running shop over HTTP, the way a browser uses it.
# Usage: tests/smoke.sh [base-url]   (default http://localhost:8080)
# With SAVE_PAGES=<dir>, the HTML of each page is saved there (CI validates it).
# Needs a freshly loaded database/schema.sql.
set -euo pipefail

BASE="${1:-http://localhost:8080}"
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
JAR="$WORK/cookies"
PAGE="$WORK/page.html"
EMAIL="ana.$RANDOM@example.com"
PASSWORD="correct horse battery"
failures=0

pass() { echo "  ok   $1"; }
fail() { echo "  FAIL $1"; failures=$((failures + 1)); }

# GET a page into $PAGE; prints the status code.
get() { curl -s -o "$PAGE" -w '%{http_code}' -b "$JAR" -c "$JAR" "$BASE/$1"; }
# POST a form (without following redirects); prints "status location".
post() {
    local path="$1"; shift
    curl -s -o "$PAGE" -w '%{http_code} %{redirect_url}' -b "$JAR" -c "$JAR" "$BASE/$path" "$@"
}
token() { sed -n 's/.*name="csrf_token" value="\([0-9a-f]*\)".*/\1/p' "$PAGE" | head -n1; }
expect() { # expect <description> <actual> <expected pattern>
    if [[ "$2" =~ $3 ]]; then pass "$1"; else fail "$1 (got: $2)"; fi
}
page_has() { if grep -q -- "$2" "$PAGE"; then pass "$1"; else fail "$1"; fi; }
keep() { if [ -n "${SAVE_PAGES:-}" ]; then mkdir -p "$SAVE_PAGES"; cp "$PAGE" "$SAVE_PAGES/$1.html"; fi; }
page_lacks() { if grep -q -- "$2" "$PAGE"; then fail "$1"; else pass "$1"; fi; }

echo "Shop at $BASE"

expect "the products page needs a login" "$(post products.php)" '^303 .*/login\.php$'

get login.php > /dev/null
keep login
get register.php > /dev/null
keep register
expect "a form without the CSRF token is rejected" \
    "$(post register.php --data-urlencode "email=$EMAIL" --data-urlencode "password=$PASSWORD")" '^400 '

get register.php > /dev/null
expect "a short password is refused" \
    "$(post register.php --data-urlencode "csrf_token=$(token)" --data-urlencode "email=$EMAIL" --data-urlencode "password=short")" '^422 '

get register.php > /dev/null
expect "signing up logs in and opens the shop" \
    "$(post register.php --data-urlencode "csrf_token=$(token)" --data-urlencode "email=$EMAIL" --data-urlencode "password=$PASSWORD")" '^303 .*/products\.php$'

expect "the shop lists the products" "$(get products.php)" '^200$'
page_has "the chaise longue is for sale at its database price" '349.00 €'
csrf="$(token)"

expect "adding to the cart redirects back to the shop" \
    "$(post products.php --data "csrf_token=$csrf&action=add&product_id=1&quantity=2&price=0.01&Price=0.01")" '^303 '
get products.php > /dev/null
page_has "the cart uses the real price, not one sent by the browser" '698.00 €'
keep products

expect "a quantity of 0 is refused" \
    "$(post products.php --data "csrf_token=$csrf&action=add&product_id=2&quantity=0")" '^303 '
get products.php > /dev/null
page_has "the refusal is explained" 'The quantity must be between 1 and 99.'

expect "a product that doesn't exist is refused" \
    "$(post products.php --data "csrf_token=$csrf&action=add&product_id=999&quantity=1")" '^303 '
get products.php > /dev/null
page_has "and explained" 'That product does not exist.'

post products.php --data "csrf_token=$csrf&action=add&product_id=2&quantity=1" > /dev/null
checkout="$(post products.php --data "csrf_token=$csrf&action=checkout")"
expect "checking out opens the order page" "$checkout" '^303 .*/thanks\.php\?order=[0-9]+$'
first_order="thanks.php?order=${checkout##*=}"
expect "the order page shows the order" "$(get "$first_order")" '^200$'
page_has "with the total of both products" '887.50 €'
keep thanks
expect "checking out again with an empty cart places no order" \
    "$(post products.php --data "csrf_token=$csrf&action=checkout")" '^303 .*/products\.php$'
get products.php > /dev/null
page_has "and says the cart is empty" 'Your cart is empty.'

expect "logging out needs the CSRF token" "$(post logout.php)" '^400 '
expect "logging out works" "$(post logout.php --data "csrf_token=$csrf")" '^303 .*/login\.php$'
expect "after logging out the order page sends you to log in" "$(get "$first_order")" '^303$'

get login.php > /dev/null
expect "SQL injection in the login does not get in" \
    "$(post login.php --data-urlencode "csrf_token=$(token)" --data-urlencode "email=' OR '1'='1' -- " --data-urlencode "password=' OR '1'='1")" '^401 '

get login.php > /dev/null
expect "a wrong password is refused" \
    "$(post login.php --data-urlencode "csrf_token=$(token)" --data-urlencode "email=$EMAIL" --data-urlencode "password=wrong password")" '^401 '
page_has "with a generic message" 'Wrong email or password.'

get register.php > /dev/null
expect "someone else signs up" \
    "$(post register.php --data-urlencode "csrf_token=$(token)" --data-urlencode "email=ben.$RANDOM@example.com" --data-urlencode "password=$PASSWORD")" '^303 '
expect "they can't see the first user's order" "$(get "$first_order")" '^404$'
post logout.php --data "csrf_token=$(get products.php > /dev/null; token)" > /dev/null

get register.php > /dev/null
expect "the same email can't sign up twice" \
    "$(post register.php --data-urlencode "csrf_token=$(token)" --data-urlencode "email=${EMAIL^^}" --data-urlencode "password=$PASSWORD")" '^422 '
page_has "and says so" 'There is already an account with that email.'
keep register-error

get register.php > /dev/null
xss='<script>alert(1)</script>@example.com'
post register.php --data-urlencode "csrf_token=$(token)" --data-urlencode "email=$xss" --data-urlencode "password=$PASSWORD" > /dev/null
page_lacks "typed HTML is shown as text, not run (XSS)" '<script>alert(1)</script>'

get login.php > /dev/null
expect "logging in with the right password works, whatever the email's case" \
    "$(post login.php --data-urlencode "csrf_token=$(token)" --data-urlencode "email=${EMAIL^^}" --data-urlencode "password=$PASSWORD")" '^303 .*/products\.php$'

curl -s -D "$WORK/headers" -o /dev/null "$BASE/login.php"
if grep -qi '^Set-Cookie:.*HttpOnly.*SameSite=Lax' "$WORK/headers"; then pass "the session cookie is HttpOnly and SameSite"; else fail "the session cookie is HttpOnly and SameSite"; fi
if grep -qi '^Content-Security-Policy:' "$WORK/headers"; then pass "pages send a Content-Security-Policy"; else fail "pages send a Content-Security-Policy"; fi

if [ "$failures" -gt 0 ]; then
    echo "$failures check(s) failed"
    exit 1
fi
echo "All checks passed"
