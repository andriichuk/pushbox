# Contributing to Pushbox

Bug reports, documentation improvements, and focused pull requests are welcome.

## Report a problem or propose a feature

Check [existing issues](https://github.com/andriichuk/pushbox/issues) first, then use the bug report or feature request form. For a bug, include the package version, PHP and Laravel versions, FCM driver version when relevant, and the smallest notification or registration that reproduces it.

See [SUPPORT.md](SUPPORT.md) for usage questions and [SECURITY.md](SECURITY.md) for private security reports. Keep service account files, device tokens, and application secrets out of issues and pull requests.

## Set up the repository

Use PHP 8.4 or 8.5, Composer 2, and Node.js 22. Fork the repository and create a branch for your change, then run:

```bash
composer install
npm ci --ignore-scripts
npx playwright install chromium
```

The automated tests use fake notifications and mocked Firebase responses. A Firebase project, service account, device token, or queue worker is not needed to run them.

## Make a change

- Follow the Laravel Pint rules in `pint.json`. Run `composer lint` to apply them.
- Keep public behavior and documentation consistent. Add a regression test when fixing a behavior bug; a documentation-only change does not need a new test.
- Keep network calls and real credentials out of automated tests.
- If you change templates or CSS, run `npm run build` and commit `resources/dist/pushbox.css` with the source change. Installed packages use the committed bundle.
- Run `npm test` for changes to workspace interactions, templates, or JavaScript.
- Keep dependency updates separate from unrelated changes and include any required lock file updates.

## Check your work

```bash
composer test
composer analyse
composer lint:check
composer validate --strict
```

For frontend changes, also run:

```bash
npm run build
npm test
```

CI additionally checks supported PHP/Laravel combinations, lowest and stable dependencies, Windows, operation without the optional FCM driver, fresh Laravel installations, and dependency audits.

## Open a pull request

Explain the problem, the resulting behavior, and how you verified it. Include a screenshot or short recording for visible UI changes. Link a related issue when there is one, and call out any public API or configuration changes.

Local consumer applications can live in the ignored `examples/` directory. Do not add those applications or their configuration to the package repository.
