#!/usr/bin/env bash

set -euo pipefail

# Keep repository-specific material out of publishable documentation.
readonly blocking_pattern='testbed|\.ddev|doccheck-v(13|14)|doccheck-profiles\.local\.php|/var/www/doccheck_testbed|verify-doccheck-profile|reset-doccheck-test-users'
readonly review_pattern='[Ll]ocal|[Dd]evelopment|[Tt]esting|[Mm]anual|CI|[Ff]unctional[[:space:]-]+[Tt]est|[Ff]ake[[:space:]-]+[Pp]rovider'

files=(README.md CHANGELOG.md UPGRADE.md composer.json ext_conf_template.txt)
while IFS= read -r -d '' file; do
    files+=("$file")
done < <(find docs -type f -name '*.md' -print0)

failed=0
for file in "${files[@]}"; do
    [[ -f "$file" ]] || continue

    blocking_matches="$(grep -EinE "$blocking_pattern" "$file" || true)"
    if [[ -n "$blocking_matches" ]]; then
        printf 'Blocked internal/workbench reference in %s:\n%s\n' "$file" "$blocking_matches" >&2
        failed=1
    fi

    review_matches="$(grep -EnE "$review_pattern" "$file" || true)"
    if [[ -n "$review_matches" ]]; then
        printf 'Review public local-environment wording in %s:\n%s\n' "$file" "$review_matches" >&2
    fi
done

if ((failed)); then
    printf '%s\n' 'Public-boundary check failed. Move internal material outside the published package.' >&2
    exit 1
fi

printf '%s\n' 'Public-boundary check passed.'
