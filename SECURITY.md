# Security policy

## Reporting a vulnerability

Email **andriichuk29@gmail.com** with the subject **Pushbox security report**. Do not open a public issue or pull request with exploit details before the issue has been assessed.

Include:

- The affected package version or commit, PHP version, and Laravel version.
- The relevant configuration and the smallest reproduction you can provide.
- The impact, including which access or data an attacker could gain.
- Whether the issue is already publicly known.

Use placeholders for credentials, device tokens, and private application data. Never send production service account keys as part of a report.

Reports are assessed privately so a fix and disclosure can be coordinated. Let the maintainer know whether you would like to be credited.

## Fixes and supported code

Security fixes are developed on `main` and published in a package release. Include the exact version or commit in your report so the affected code can be identified. The current development branch targets PHP 8.4/8.5 and Laravel 12/13.

## Intended use

Pushbox is a developer tool. Install it in `require-dev`, keep it restricted to local development by default, and use an explicit authorization policy when enabling staging access. Test sends deliver real notifications to the configured device. See the [access and sending documentation](README.md#local-and-staging-access).
