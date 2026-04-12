# PHP 8.4 Upgrade Plan (Phase 2.1)

## Outcome

`composer.json` has been updated to target PHP 8.4:

- `require.php`: `^8.1 || ^8.2` → `^8.4`
- `config.platform.php`: `8.1.0` → `8.4.0`

## Dependency Compatibility Review (from current constraints)

The project is pinned to several older minors/patches that are likely to need bumping before a full PHP 8.4 update will resolve cleanly:

1. `laravel/framework: ~10.1.3`
   - Very early Laravel 10 patch line; PHP 8.4 support is expected in newer framework patches/releases.
   - **Proposed change (next phase):** bump to latest Laravel 10 patch (or Laravel 11+ if doing a broader framework upgrade).

2. Symfony components pinned at `~6.2.x` (`http-client`, `mailgun-mailer`, `postmark-mailer`, `yaml`)
   - Symfony 6.2 is old and may not be validated for PHP 8.4.
   - **Proposed change:** move to `^6.4` (or framework-aligned newer versions).

3. Dev tooling versions are old for PHP 8.4:
   - `phpunit/phpunit: ~10.0.11`
   - `phpstan/phpstan: ~1.10.1`
   - `nunomaduro/larastan: ~2.4.1`
   - `friendsofphp/php-cs-fixer: ~3.14.4`
   - **Proposed change:** upgrade to current PHP 8.4-compatible releases.

4. `laravel/helpers: ~1.6.0`
   - Legacy package and potential compatibility drag in modern Laravel.
   - **Proposed change:** evaluate removal/replacement with native helpers.

5. Many runtime packages use narrow `~` pins
   - Restrictive pins increase solver friction during PHP major-version transitions.
   - **Proposed change:** selectively widen/bump where upstream declares PHP 8.4 support.

## Deprecated Warning Risk Areas (to address after dependency update)

No dependency update or runtime execution was performed in this phase. Likely warning hot-spots to review next:

1. Dynamic properties and implicit property creation in models/services.
2. Deprecated APIs in older pinned vendor packages.
3. Test/tooling stack deprecations under PHP 8.4.

## Proposed Next Step (not executed in this phase)

1. Update package constraints for Laravel/Symfony/dev tools.
2. Run `composer update` on PHP 8.4.
3. Resolve solver conflicts and lockfile updates.
4. Run tests/static analysis and fix concrete deprecations.
