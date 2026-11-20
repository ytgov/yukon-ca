#!/usr/bin/env python3
"""Helpers for building the staging / staging-test branches from open PR branches.

  scripts/staging.py conflicts [BRANCH ...] [--target BRANCH] [--md]
      Read-only. Previews merge conflicts without touching the working tree:
      each branch against --target (default main) and every pair of branches.
      With no branches, uses open non-dependabot PR heads from `gh` updated within
      --max-age-days (default 45), so long-stale PRs don't drown out the real ones.

  scripts/staging.py status [--max-age-days N]
      Read-only. For each active open PR: how many commits it is behind main and which
      files would conflict if main were merged into it. Convention: whenever main
      updates, merge main into every remaining open PR branch and resolve conflicts
      there (`staging.py merge main --target PR-BRANCH`), so each PR merges cleanly
      back into main once approved.

  scripts/staging.py merge BRANCH [--target staging-test] [--commit] [--local-target]
      Merges origin/BRANCH into the target branch, resolving composer.lock and
      patches.lock.json the safe way (take one side's lock, `composer update --lock`,
      `composer patches-relock`, verify no package versions moved). Anything else
      that conflicts is left for a human. Never pushes. Stages named files only.

Python 3 standard library only. Run from the repo root.
"""
import argparse
import itertools
import json
import os
import subprocess
import sys
from datetime import datetime, timedelta, timezone

LOCKS = ("composer.lock", "patches.lock.json")
HINTS = (
    ("composer.lock", "`staging.py merge` handles it: take one side, `composer update --lock`"),
    ("patches.lock.json", "`composer patches-relock` (`staging.py merge` handles it)"),
    ("composer.json", "hand-merge; keep both sides' requirements/patches"),
    ("config/", "hand-merge, then `drush cim`, `drush cex` and review the diff"),
    ("", "hand-merge"),
)


def run(cmd, check=True):
    p = subprocess.run(cmd, capture_output=True, text=True)
    if check and p.returncode != 0:
        sys.exit(f"command failed: {' '.join(cmd)}\n{p.stdout}{p.stderr}")
    return p


def git(*args, check=True):
    return run(["git", *args], check)


def composer(*args, check=True):
    base = ["ddev", "composer"] if os.path.isdir(".ddev") else ["composer"]
    return run([*base, *args], check)


def hint(path):
    return next(h for prefix, h in HINTS if path.startswith(prefix))


def preview(a, b):
    """Conflicted files from merging ref b into ref a (no working tree changes)."""
    p = git("merge-tree", "--write-tree", "--name-only", a, b, check=False)
    if p.returncode == 0:
        return []
    if p.returncode != 1:
        sys.exit(f"git merge-tree failed for {a} {b}: {p.stderr}")
    lines = p.stdout.splitlines()[1:]  # first line is the result tree id
    return lines[: lines.index("")] if "" in lines else lines


def open_pr_branches(max_age_days):
    p = run(["gh", "pr", "list", "--state", "open", "--limit", "100",
             "--json", "number,headRefName,author,updatedAt"])
    cutoff = datetime.now(timezone.utc) - timedelta(days=max_age_days) if max_age_days else None
    return [(x["number"], x["headRefName"]) for x in json.loads(p.stdout)
            if not x["author"].get("is_bot") and not x["headRefName"].startswith("dependabot/")
            and (cutoff is None
                 or datetime.fromisoformat(x["updatedAt"].replace("Z", "+00:00")) >= cutoff)]


def cmd_conflicts(args):
    git("fetch", "-q", "origin")
    if args.branches:
        branches = [(None, b) for b in args.branches]
    else:
        branches = open_pr_branches(args.max_age_days)
    target = f"origin/{args.target}"
    label = lambda n, b: f"#{n} ({b})" if n else b
    found, own = [], {}
    for n, b in branches:
        own[b] = preview(target, f"origin/{b}")
        if own[b]:
            found.append((f"{label(n, b)} into {args.target}", own[b]))
        if target != "origin/main":  # a conflict with main is the branch's own problem too
            own[b] = sorted(set(own[b]) | set(preview("origin/main", f"origin/{b}")))
    for (n1, b1), (n2, b2) in itertools.combinations(branches, 2):
        # files a branch already conflicts on with the target are that branch's problem,
        # not a conflict between the pair
        files = [f for f in preview(f"origin/{b1}", f"origin/{b2}")
                 if f not in own[b1] and f not in own[b2]]
        if files:
            found.append((f"{label(n1, b1)} with {label(n2, b2)}", files))
    if not found:
        print(f"No conflicts among {len(branches)} branch(es) or with {args.target}.")
        return 0
    short = lambda files: files[:6] + [f"(+{len(files) - 6} more)"] if len(files) > 6 else files
    for who, files in found:
        if args.md:
            print(f"- {who}: " + ", ".join(f if f.startswith("(") else f"`{f}`" for f in short(files)))
        else:
            print(who)
            for f in short(files):
                print(f"    {f}" + ("" if f.startswith("(") else f"  -> {hint(f)}"))
    return 1


def cmd_status(args):
    git("fetch", "-q", "origin")
    behind_any = False
    for n, b in open_pr_branches(args.max_age_days):
        behind = int(git("rev-list", "--count", f"origin/{b}..origin/main").stdout)
        files = preview(f"origin/{b}", "origin/main") if behind else []
        behind_any |= bool(behind)
        state = "up to date with main" if not behind else (
            f"{behind} behind main, merging main CONFLICTS: " + ", ".join(files[:6])
            + (f" (+{len(files) - 6} more)" if len(files) > 6 else "")
            if files else f"{behind} behind main, merges cleanly")
        print(f"#{n} ({b}): {state}")
    return 1 if behind_any else 0


def packages(text):
    d = json.loads(text)
    return {p["name"]: (p["version"], (p.get("source") or {}).get("reference"))
            for p in d.get("packages", []) + d.get("packages-dev", [])}


def lock_at(rev):
    p = git("show", f"{rev}:composer.lock", check=False)
    return packages(p.stdout) if p.returncode == 0 else {}


def resolve_locks(conflicted):
    base = lock_at(git("merge-base", "HEAD", "MERGE_HEAD").stdout.strip())
    ours, theirs = lock_at("HEAD"), lock_at("MERGE_HEAD")
    if ours != base and theirs != base and ours != theirs:
        diff = sorted(n for n in set(ours) | set(theirs) if ours.get(n) != theirs.get(n))
        print("Both sides changed package versions in composer.lock. Needs a human:\n"
              "  take one side's lock, then `composer update <pkg> --with-dependencies` for\n"
              "  only the packages below (never a full update), then compare package lists.\n"
              f"  Differing packages: {', '.join(diff)}")
        return None
    side, chosen = ("--ours", ours) if (theirs == base) else ("--theirs", theirs)
    if "composer.lock" in conflicted:
        git("checkout", side, "composer.lock")
    composer("update", "--lock")
    if packages(open("composer.lock").read()) != chosen:
        print("composer.lock package versions moved during `update --lock`. Needs a human.")
        return None
    if "patches.lock.json" in conflicted:
        git("checkout", "--theirs", "patches.lock.json")
        composer("patches-relock")
    return ["composer.lock"] + (["patches.lock.json"] if "patches.lock.json" in conflicted else [])


def cmd_merge(args):
    if git("status", "--porcelain", "--untracked-files=no").stdout.strip():
        sys.exit("Tracked changes in the working tree; commit or stash them first.")
    git("fetch", "-q", "origin")
    git("checkout", args.target)
    if not args.local_target:
        git("merge", "--ff-only", f"origin/{args.target}")
    git("merge", "--no-ff", "--no-commit", f"origin/{args.branch}", check=False)
    conflicted = git("diff", "--name-only", "--diff-filter=U").stdout.split()
    others = [f for f in conflicted if f not in LOCKS]
    if "composer.json" in conflicted:
        print("composer.json conflicts; merge left in progress. Hand-merge it (keep both sides'\n"
              "requirements/patches), then rerun the lock steps by hand or abort with `git merge --abort`.")
        for f in others:
            print(f"    {f}  -> {hint(f)}")
        return 2
    stage = []
    outdated = composer("validate", "--no-check-publish", "--no-check-all", check=False)
    if any(f in conflicted for f in LOCKS) or "not up to date" in outdated.stdout + outdated.stderr:
        stage = resolve_locks(conflicted)
        if stage is None:
            return 2
    for f in stage:  # explicit paths only, never a directory
        git("add", f)
    if others:
        print("Locks resolved and staged. These still conflict and need a human (merge in progress):")
        for f in others:
            print(f"    {f}  -> {hint(f)}")
        print("Then `git add <named files>` (never a directory) and `git commit --no-edit`, "
              "or `git merge --abort`.")
        return 2
    print(git("diff", "--cached", "--stat").stdout)
    if args.commit:
        git("commit", "--no-edit", "-m",
            f"Merge remote-tracking branch 'origin/{args.branch}' into {args.target}")
        print(f"Committed merge on {args.target}. Not pushed.")
    else:
        print("Merge staged, not committed. Review, then `git commit --no-edit` "
              "(or rerun with --commit). Not pushed.")
    return 0


def main():
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawTextHelpFormatter)
    sub = ap.add_subparsers(dest="cmd", required=True)
    c = sub.add_parser("conflicts")
    c.add_argument("branches", nargs="*")
    c.add_argument("--target", default="main")
    c.add_argument("--md", action="store_true", help="markdown bullets for a PR's Notes section")
    c.add_argument("--max-age-days", type=int, default=45,
                   help="only PRs updated within N days when no branches are named (0 = all; default 45)")
    c.set_defaults(fn=cmd_conflicts)
    s = sub.add_parser("status")
    s.add_argument("--max-age-days", type=int, default=45)
    s.set_defaults(fn=cmd_status)
    m = sub.add_parser("merge")
    m.add_argument("branch")
    m.add_argument("--target", default="staging-test")
    m.add_argument("--commit", action="store_true")
    m.add_argument("--local-target", action="store_true",
                   help="target is a local-only branch (skip syncing with origin)")
    m.set_defaults(fn=cmd_merge)
    args = ap.parse_args()
    sys.exit(args.fn(args))


if __name__ == "__main__":
    main()
