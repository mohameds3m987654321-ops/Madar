# Paymob payment setup

The course checkout flow is implemented for Vodafone Cash and Fawry. Live payments remain disabled until merchant credentials and integration IDs are configured in the application's local `.env` file. Never commit or send these values.

Configure the following variables from the Paymob dashboard:

```dotenv
APP_URL=https://your-public-domain.example
PAYMOB_BASE_URL=https://accept.paymob.com
PAYMOB_SECRET_KEY=
PAYMOB_PUBLIC_KEY=
PAYMOB_HMAC_SECRET=
PAYMOB_VODAFONE_CASH_INTEGRATION_ID=
PAYMOB_FAWRY_INTEGRATION_ID=
```

Set the secret key, public key, and HMAC secret to the values for the same Paymob account/environment. Use the Vodafone Cash and Fawry integration IDs for the corresponding payment methods. Configure Paymob's transaction callback to:

```text
https://your-public-domain.example/payments/paymob/callback
```

The callback endpoint supports both GET redirects and POST notifications. The application validates the HMAC, payment status, currency, amount, and integration ID before changing a pending enrollment to paid. Keep the endpoint publicly reachable over HTTPS in production.

After updating local environment variables, clear cached Laravel configuration:

```text
php artisan config:clear
```

The automated checkout tests use mocked Paymob HTTP responses; they verify application behavior but do not make or confirm a live transaction. Test the live callback in Paymob's test environment before enabling production credentials.
