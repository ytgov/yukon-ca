# Staging merges and keeping PR branches current

`staging` and `staging-test` are built by merging approved PR branches into them by hand (see [CLAUDE.md](../CLAUDE.md) and issue #975). `scripts/staging.py` does the mechanical and error-prone parts. Python 3, standard library only, run from the repo root. It never pushes.

## Rules

- **When `main` updates, merge `main` into every remaining open PR branch** and resolve conflicts there, so each PR merges cleanly back into `main` once it is approved for deployment.
- **Staging branches get merge commits only.** Resolve conflicts inside the merge commit. `git add` named files, never a directory (an untracked file in `docs/` was once committed by `git add docs`). Check `git diff --cached --stat` before committing.
- **Lock files are regenerated, never hand-merged.** Take one side's `composer.lock`, run `composer update --lock` (refreshes the `content-hash`, updates nothing), then `composer patches-relock` for `patches.lock.json`, and confirm the package list did not change. If both sides changed package versions, take one side and run a targeted `composer update <pkg> --with-dependencies` for only the differing packages, never a full update.
- **Record conflicts in the PR.** When a PR conflicts with another open PR, say which PR, which files and how to resolve it, in the PR's Notes (see below). Not every open PR is merged at once, so only name the PRs it actually conflicts with.

## Commands

```text
scripts/staging.py status
```

Read-only. For each recently active open PR: commits behind `main`, and the files that would conflict if `main` were merged into it. Use it after every `main` update.

```text
scripts/staging.py conflicts [--target staging-test] [--md] [BRANCH ...]
```

Read-only, uses `git merge-tree` so nothing is checked out. Each PR branch against the target (default `main`), and every pair of branches. Conflicts that a branch already has with the target are not repeated for its pairs. Without branch names it uses open, non-dependabot PRs updated in the last 45 days (`--max-age-days N`, 0 for all). `--md` prints bullets to paste into a PR's Notes under "Merge conflicts with other open PRs". Exit code 1 when anything conflicts.

```text
scripts/staging.py merge BRANCH [--target staging-test] [--commit]
scripts/staging.py merge main --target PR-BRANCH
```

Checks out the target (fast-forwarded to `origin/<target>`), runs `git merge --no-ff --no-commit origin/BRANCH`, and resolves `composer.lock` and `patches.lock.json` as described above, verifying the package list matches the side it took. Anything else that conflicts is left for you, with the lock files already resolved and staged. It refuses to run with tracked changes in the working tree. Without `--commit` the merge is left staged for review. Push yourself afterwards.

Stops for a human when `composer.json` conflicts, when both sides changed package versions, or when `composer update --lock` moves any version.

## Where conflicts usually happen

- `composer.json`, `composer.lock`, `patches.lock.json`: any PR that adds or changes a patch changes the lock `content-hash`, even when no package changes.
- `CLAUDE.md` and `docs/security-update-pr-template.md`: PRs that edit the shared process docs.
- `config/default/*.yml`: roles, displays, `core.extension.yml`, search index. After resolving, run `drush cim` and `drush cex` and review the diff.
