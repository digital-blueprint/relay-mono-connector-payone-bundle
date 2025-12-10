# Changelog

## v0.3.6

* Add support for Symfony 7

## v0.3.5

* relay-mono-connector-payone:webhook-info: add curl command for local webhook testing
* Update online-payments/sdk-php to v7
* a bit more logging and tests for webhooks

## v0.3.4

* Add bundle config option to select the template variant for the hosted checkout page

## v0.3.3

* Obfuscate payment data when logging it to the audit log

## v0.3.2

* Add webhook test command
* Log webhook requests to the audit log

## v0.3.1

* Forward the API locale to the hosted checkout page

## v0.3.0

* Initial release
* config: rename `api_key` to `api_key_id`
* Implement health checks for the API and webhooks
* Set payment status to `pending` after creating a payment, since it can
  no longer be restarted.
* Remove unused guzzle dependency