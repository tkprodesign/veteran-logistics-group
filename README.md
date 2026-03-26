# veteran-logistics-group
Delivery logistics web app

## One-command PR + auto-merge flow

To reduce manual GitHub clicks, use:

```bash
./scripts/auto-pr-merge.sh "Your PR title" squash
```

What it does:
- Pushes your current feature branch.
- Creates a PR with `gh pr create`.
- Enables auto-merge with your selected strategy (`squash`, `merge`, or `rebase`).

Requirements:
- GitHub CLI installed (`gh`).
- `gh` authenticated (`gh auth login`).
- Run from a non-main feature branch with a clean working tree.
