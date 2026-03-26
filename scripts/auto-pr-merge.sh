#!/usr/bin/env bash
set -euo pipefail

# Automates: commit -> push -> create PR -> enable auto-merge.
# Requirements:
#   - GitHub CLI (`gh`) installed and authenticated.
#   - Current branch already contains your desired commits.

if ! command -v gh >/dev/null 2>&1; then
  echo "Error: GitHub CLI (gh) is not installed."
  exit 1
fi

if ! gh auth status >/dev/null 2>&1; then
  echo "Error: gh is not authenticated. Run: gh auth login"
  exit 1
fi

if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  echo "Error: current directory is not a git repository."
  exit 1
fi

branch="$(git rev-parse --abbrev-ref HEAD)"
if [[ "$branch" == "main" || "$branch" == "master" ]]; then
  echo "Error: run this from a feature branch, not '$branch'."
  exit 1
fi

if [[ $# -lt 1 ]]; then
  echo "Usage:"
  echo "  $0 \"PR title\" [merge_method]"
  echo
  echo "merge_method: squash (default) | merge | rebase"
  exit 1
fi

title="$1"
merge_method="${2:-squash}"

case "$merge_method" in
  squash|merge|rebase) ;;
  *)
    echo "Error: invalid merge_method '$merge_method'. Use squash, merge, or rebase."
    exit 1
    ;;
esac

if ! git diff --quiet || ! git diff --cached --quiet; then
  echo "Error: working tree is not clean. Commit or stash your changes first."
  exit 1
fi

echo "Pushing branch '$branch'..."
git push -u origin "$branch"

echo "Creating PR..."
pr_url="$(gh pr create --fill --title "$title" --web=false)"
echo "PR created: $pr_url"

echo "Enabling auto-merge ($merge_method)..."
gh pr merge --auto --"$merge_method" "$pr_url"

echo "Done. Auto-merge is enabled for: $pr_url"
