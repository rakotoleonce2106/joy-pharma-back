# Environment Variables Added

## ✅ Created `.env.example` File

A comprehensive `.env.example` file has been created in the project root with all required environment variables, including MPGS configuration.

### Quick Start

```bash
# Copy the example file
cp .env.example .env

# Edit .env and fill in your actual values
nano .env  # or use your preferred editor
```

---

## MPGS Environment Variables Included

The `.env.example` file includes all MPGS configuration variables:

### Test Environment (Default)
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

### Production Environment (Commented)
```env
# MPGS_GATEWAY_BASE_URL=https://gateway.mastercard.com
# MPGS_PKI_BASE_URL=https://gateway.mastercard.com
# MPGS_MERCHANT_ID=your_production_merchant_id
# MPGS_API_PASSWORD=your_production_api_password
# MPGS_API_VERSION=45
# MPGS_DEFAULT_CURRENCY=USD
# MPGS_CERTIFICATE_PATH=/path/to/your/certificate.pem
# MPGS_VERIFY_PEER=true
# MPGS_VERIFY_HOST=1
```

---

## All Variables Included

The `.env.example` file includes:

1. **Database Configuration**
   - DATABASE_URL

2. **JWT Configuration**
   - JWT_SECRET_KEY
   - JWT_PUBLIC_KEY
   - JWT_PASSPHRASE

3. **MVola Payment Configuration**
   - MVOLA_ENVIRONMENT
   - MVOLA_MERCHANT_NUMBER
   - MVOLA_COMPANY_NAME
   - MVOLA_CONSUMER_KEY
   - MVOLA_CONSUMER_SECRET
   - MVOLA_AUTH_URL
   - MVOLA_MAX_RETRIES
   - MVOLA_RETRY_DELAY
   - MVOLA_CACHE_TTL

4. **MPGS Payment Configuration** (NEW)
   - MPGS_GATEWAY_BASE_URL
   - MPGS_PKI_BASE_URL
   - MPGS_MERCHANT_ID
   - MPGS_API_PASSWORD
   - MPGS_API_VERSION
   - MPGS_DEFAULT_CURRENCY
   - MPGS_CERTIFICATE_PATH
   - MPGS_VERIFY_PEER
   - MPGS_VERIFY_HOST

5. **API Configuration**
   - API_URL

6. **Application Configuration**
   - APP_ENV
   - APP_SECRET

7. **Optional Configurations** (Commented)
   - Mailer Configuration
   - Elasticsearch Configuration
   - CORS Configuration

---

## Next Steps

1. **Copy the example file:**
   ```bash
   cp .env.example .env
   ```

2. **Get MPGS Credentials:**
   - Log in to your MPGS merchant portal
   - Get your Merchant ID and API Password
   - Use test credentials for development
   - Use production credentials for production

3. **Fill in the values:**
   - Replace `your_test_merchant_id` with your actual merchant ID
   - Replace `your_test_api_password` with your actual API password
   - Update other values as needed

4. **For Production:**
   - Uncomment the production MPGS configuration
   - Comment out or remove the test configuration
   - Update URLs to production gateway
   - Set `MPGS_VERIFY_PEER=true` and `MPGS_VERIFY_HOST=1`

---

## Security Notes

- ✅ `.env` is in `.gitignore` (won't be committed)
- ✅ `.env.example` is safe to commit (contains no secrets)
- ⚠️ Never commit your actual `.env` file
- ⚠️ Never share your MPGS credentials
- ⚠️ Use different credentials for test and production

---

## File Location

- **Example file:** `.env.example` (in project root)
- **Your file:** `.env` (create from example, not in git)

---

## Documentation

For more details, see:
- `docs/ENV_SETUP.md` - Complete environment setup guide
- `docs/MPGS_QUICK_START.md` - Quick start guide
- `docs/MPGS_IMPLEMENTATION_SUMMARY.md` - Implementation summary

