# Environment Variables Setup

This document lists all required environment variables for the application.

## Quick Start

1. Copy the example file: `cp .env.example .env`
2. Edit `.env` and fill in your actual values
3. Never commit `.env` to version control (it's in .gitignore)

## Backend Environment Variables

A complete example is available in `.env.example`. Create a `.env` file in the project root with the following variables:

### Database Configuration
```env
DATABASE_URL=postgresql://app:!ChangeMe!@database:5432/app?serverVersion=15&charset=utf8
```

### JWT Configuration
```env
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_jwt_passphrase_here
```

### MVola Payment Configuration
```env
MVOLA_ENVIRONMENT=test
MVOLA_MERCHANT_NUMBER=your_mvola_merchant_number
MVOLA_COMPANY_NAME=Your Company Name
MVOLA_CONSUMER_KEY=your_mvola_consumer_key
MVOLA_CONSUMER_SECRET=your_mvola_consumer_secret
MVOLA_AUTH_URL=https://api.mvola.mg/token
MVOLA_MAX_RETRIES=3
MVOLA_RETRY_DELAY=1000
MVOLA_CACHE_TTL=3600
```

### MPGS (Mastercard Payment Gateway Services) Configuration

#### Test Environment
```env
MPGS_GATEWAY_BASE_URL=https://test-gateway.mastercard.com
MPGS_PKI_BASE_URL=https://test-gateway.mastercard.com
MPGS_MERCHANT_ID=your_test_merchant_id
MPGS_API_PASSWORD=your_test_api_password
MPGS_API_VERSION=45
MPGS_DEFAULT_CURRENCY=USD
MPGS_CERTIFICATE_PATH=
MPGS_VERIFY_PEER=false
MPGS_VERIFY_HOST=0
```

#### Production Environment
```env
MPGS_GATEWAY_BASE_URL=https://gateway.mastercard.com
MPGS_PKI_BASE_URL=https://gateway.mastercard.com
MPGS_MERCHANT_ID=your_production_merchant_id
MPGS_API_PASSWORD=your_production_api_password
MPGS_API_VERSION=45
MPGS_DEFAULT_CURRENCY=USD
MPGS_CERTIFICATE_PATH=/path/to/your/certificate.pem
MPGS_VERIFY_PEER=true
MPGS_VERIFY_HOST=1
```

### API Configuration
```env
API_URL=http://localhost:8000
# In production: https://your-api-domain.com
```

### Other Configuration
```env
# Add other environment variables as needed
```

---

## Mobile App Environment Variables (React Native Expo)

Create a `.env` file in your React Native Expo project:

```env
# API Configuration
EXPO_PUBLIC_API_URL=https://your-api-domain.com

# MPGS Configuration
EXPO_PUBLIC_MPGS_MERCHANT_ID=your_merchant_id
EXPO_PUBLIC_MPGS_CHECKOUT_JS_URL=https://your-gateway-url/checkout/version/45/checkout.js

# For test environment:
# EXPO_PUBLIC_MPGS_CHECKOUT_JS_URL=https://test-gateway.mastercard.com/checkout/version/45/checkout.js

# For production:
# EXPO_PUBLIC_MPGS_CHECKOUT_JS_URL=https://gateway.mastercard.com/checkout/version/45/checkout.js
```

---

## Getting MPGS Credentials

1. **Sign up for MPGS account**
   - Contact Mastercard or your payment processor
   - Get merchant account credentials

2. **Access Merchant Portal**
   - Login to MPGS merchant portal
   - Navigate to API credentials section

3. **Get API Credentials**
   - Merchant ID
   - API Password (or generate new one)
   - Gateway URLs (test and production)

4. **Configure Webhooks** (Optional but recommended)
   - Set webhook URL in merchant portal
   - Configure webhook events to receive

---

## Security Notes

1. **Never commit `.env` files to version control**
   - Add `.env` to `.gitignore`
   - Use `.env.example` as template

2. **Use different credentials for test and production**
   - Never use production credentials in development
   - Rotate credentials regularly

3. **Protect sensitive values**
   - Use environment variable management tools
   - Encrypt sensitive data at rest
   - Use secure channels for transmission

4. **Certificate Authentication** (Optional)
   - More secure than API password
   - Requires certificate file
   - Set `MPGS_CERTIFICATE_PATH` to certificate file path

---

## Testing

After setting up environment variables:

1. **Test Backend Connection**
   ```bash
   php bin/console debug:container --parameter=mpgs
   ```

2. **Test Payment Intent Creation**
   ```bash
   curl -X POST http://localhost:8000/api/create-payment-intent \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -d '{
       "method": "mpgs",
       "amount": "10.00",
       "reference": "TEST-001"
     }'
   ```

3. **Verify Configuration**
   - Check logs for configuration errors
   - Verify API connectivity
   - Test with MPGS test cards

---

## Troubleshooting

### Common Issues

1. **"Invalid merchant ID"**
   - Verify `MPGS_MERCHANT_ID` is correct
   - Check if using test credentials in test environment

2. **"Authentication failed"**
   - Verify `MPGS_API_PASSWORD` is correct
   - Check if API password was regenerated

3. **"Certificate not found"**
   - Verify `MPGS_CERTIFICATE_PATH` points to valid file
   - Check file permissions

4. **"Connection timeout"**
   - Verify `MPGS_GATEWAY_BASE_URL` is correct
   - Check network connectivity
   - Verify firewall settings

---

## Environment-Specific Configuration

### Development
- Use test credentials
- Set `MPGS_VERIFY_PEER=false`
- Use local API URL

### Staging
- Use test credentials
- Set `MPGS_VERIFY_PEER=true`
- Use staging API URL

### Production
- Use production credentials
- Set `MPGS_VERIFY_PEER=true`
- Use production API URL
- Enable all security features

