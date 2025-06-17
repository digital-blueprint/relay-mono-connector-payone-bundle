# Changelog

## v0.3.1

* Forward the API locale to the hosted checkout page

## v0.3.0

* Initial release
* config: rename `api_key` to `api_key_id`
* Implement health checks for the API and webhooks
* Set payment status to `pending` after creating a payment, since it can
  no longer be restarted.
* Remove unused guzzle dependency