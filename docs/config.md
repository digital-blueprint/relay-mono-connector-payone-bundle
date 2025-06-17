# Configuration

## Bundle Configuration

Created via `./bin/console config:dump-reference DbpRelayMonoConnectorPayoneBundle | sed '/^$/d'`

```yaml
# Default configuration for "DbpRelayMonoConnectorPayoneBundle"
dbp_relay_mono_connector_payone:
  # The database DSN
  database_url:         '%env(resolve:DATABASE_URL)%' # Required
  # Zero or more payment contracts. The "payment_contract" can be referenced in the "mono" config.
  payment_contracts:
    # Prototype
    payment_contract:
      # The PAYONE API endpoint.
      api_url:              ~ # Required, Example: 'https://payment.preprod.payone.com/'
      # The merchantId (PSPID) provided by PAYONE
      merchant_id:          ~ # Required
      # The API key ID provided by PAYONE
      api_key:              ~ # Required
      # The Secret API key provided by PAYONE
      api_secret:           ~ # Required
      # The Webhook ID provided by PAYONE
      webhook_id:           ~ # Required
      # The Secret webhook key provided by PAYONE
      webhook_secret:       ~ # Required
      # Zero or more payment methods. The "payment_method" can be referenced in the "mono" config.
      payment_methods:
        # Prototype
        payment_method:
          # A list of payment product labels. See Payment methods in the PAYONE portal
          products:             []
            # Examples:
            # - MasterCard
            # - VISA
```

Example configuration:

```yaml
dbp_relay_mono_connector_payone:
  database_url: '%env(DATABASE_URL)%'
  payment_contracts:
    payone_studienservice:
      api_url: '%env(MONO_CONNECTOR_PAYONE_API_URL)%'
      merchant_id: '%env(MONO_CONNECTOR_PAYONE_MERCHANT_ID)%'
      api_key_id: '%env(MONO_CONNECTOR_PAYONE_API_KEY_ID)%'
      api_secret: '%env(MONO_CONNECTOR_PAYONE_API_SECRET)%'
      webhook_id: '%env(MONO_CONNECTOR_PAYONE_WEBHOOK_ID)%'
      webhook_secret: '%env(MONO_CONNECTOR_PAYONE_WEBHOOK_SECRET)%'
      payment_methods:
        creditcard:
          products: [ 'American Express', 'Diners Club', 'MasterCard', 'JCB', 'Maestro', 'VISA' ]
        googlepay:
          products: [ 'GOOGLEPAY' ]
```

## Web Hook

You can use the `dbp:relay:mono-connector-payone:webhook-info` to see the URL you need to add as webhook endpoint in the PAYONE portal:

```console
./bin/console dbp:relay:mono-connector-payone:webhook-info payone_studienservice
Endpoint url for PAYONE:

http://localhost:8000/mono-connector-payone/webhook/payone_studienservice
```
