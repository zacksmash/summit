{{-- App Guidelines --}}
## shadcn-vue Components

- `resources/js/components/ui` is installed from the shadcn-vue registry. The starter kit's own changes to those files live in `resources/js/components/ui/starter-kit.patch`, never as untracked hand edits.
- `php artisan shadcn:update` reinstalls every component in that folder from the registry, then applies the patch. Review the result with `git diff` and run the usual JS checks before committing.
- If the patch no longer applies, shadcn-vue changed the same lines. Re-apply the starter kit change by hand on top of the new registry code, then regenerate the patch.
- To change the patch: run `git apply --reverse resources/js/components/ui/starter-kit.patch`, stage the plain registry files with `git add resources/js/components/ui`, run `git apply resources/js/components/ui/starter-kit.patch`, make the edit, then run `git diff -- resources/js/components/ui ':(exclude)*.patch' > resources/js/components/ui/starter-kit.patch`.
- `chisel-paths.php` lists the `input-otp` component files by name. If a reinstall adds or removes files there, update that list.

### During a starter kit upgrade

When an upstream starter kit change touches `resources/js/components/ui`:
- A new component folder: add it. `shadcn:update` picks it up from then on.
- A change to an existing component: don't take the kit's whole file, which is based on an older registry version. Add only the kit's change to `starter-kit.patch`.
