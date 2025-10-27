#!/bin/bash

# Color codes for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Base URL - adjust if needed
BASE_URL="http://localhost"
API_URL="${BASE_URL}/api"

echo "======================================"
echo "Password Management API Test Script"
echo "======================================"
echo ""

# Test data
TEST_EMAIL="test@example.com"
TEST_PASSWORD="TestPass123"
NEW_PASSWORD="NewPass123"

echo -e "${YELLOW}Note: Make sure your application is running before executing these tests${NC}"
echo ""

# Function to print test results
print_result() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✓ $2${NC}"
    else
        echo -e "${RED}✗ $2${NC}"
    fi
}

echo "======================================"
echo "Test 1: Forgot Password - Send Reset Code"
echo "======================================"
echo ""

RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}" -X POST "${API_URL}/password/forgot" \
  -H "Content-Type: application/json" \
  -d "{\"email\": \"${TEST_EMAIL}\"}")

HTTP_CODE=$(echo "$RESPONSE" | grep "HTTP_CODE:" | cut -d':' -f2)
BODY=$(echo "$RESPONSE" | sed '/HTTP_CODE:/d')

echo "Request:"
echo "POST ${API_URL}/password/forgot"
echo "{\"email\": \"${TEST_EMAIL}\"}"
echo ""
echo "Response (HTTP $HTTP_CODE):"
echo "$BODY" | jq . 2>/dev/null || echo "$BODY"
echo ""

if [ "$HTTP_CODE" = "200" ]; then
    print_result 0 "Reset code request successful"
else
    print_result 1 "Reset code request failed"
fi
echo ""

echo "======================================"
echo "Test 2: Verify Reset Code (Example)"
echo "======================================"
echo ""
echo "Note: Replace '123456' with the actual code from your email"
echo ""

RESET_CODE="123456"

RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}" -X POST "${API_URL}/password/verify-code" \
  -H "Content-Type: application/json" \
  -d "{\"email\": \"${TEST_EMAIL}\", \"code\": \"${RESET_CODE}\"}")

HTTP_CODE=$(echo "$RESPONSE" | grep "HTTP_CODE:" | cut -d':' -f2)
BODY=$(echo "$RESPONSE" | sed '/HTTP_CODE:/d')

echo "Request:"
echo "POST ${API_URL}/password/verify-code"
echo "{\"email\": \"${TEST_EMAIL}\", \"code\": \"${RESET_CODE}\"}"
echo ""
echo "Response (HTTP $HTTP_CODE):"
echo "$BODY" | jq . 2>/dev/null || echo "$BODY"
echo ""

if [ "$HTTP_CODE" = "200" ]; then
    print_result 0 "Code verification successful"
else
    echo -e "${YELLOW}⚠ Code verification failed (expected if using dummy code)${NC}"
fi
echo ""

echo "======================================"
echo "Test 3: Reset Password (Example)"
echo "======================================"
echo ""
echo "Note: Replace '123456' with the actual code from your email"
echo ""

RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}" -X POST "${API_URL}/password/reset" \
  -H "Content-Type: application/json" \
  -d "{\"email\": \"${TEST_EMAIL}\", \"code\": \"${RESET_CODE}\", \"password\": \"${NEW_PASSWORD}\"}")

HTTP_CODE=$(echo "$RESPONSE" | grep "HTTP_CODE:" | cut -d':' -f2)
BODY=$(echo "$RESPONSE" | sed '/HTTP_CODE:/d')

echo "Request:"
echo "POST ${API_URL}/password/reset"
echo "{\"email\": \"${TEST_EMAIL}\", \"code\": \"${RESET_CODE}\", \"password\": \"${NEW_PASSWORD}\"}"
echo ""
echo "Response (HTTP $HTTP_CODE):"
echo "$BODY" | jq . 2>/dev/null || echo "$BODY"
echo ""

if [ "$HTTP_CODE" = "200" ]; then
    print_result 0 "Password reset successful"
else
    echo -e "${YELLOW}⚠ Password reset failed (expected if using dummy code)${NC}"
fi
echo ""

echo "======================================"
echo "Test 4: Login (to get JWT token)"
echo "======================================"
echo ""

RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}" -X POST "${API_URL}/auth" \
  -H "Content-Type: application/json" \
  -d "{\"email\": \"${TEST_EMAIL}\", \"password\": \"${TEST_PASSWORD}\"}")

HTTP_CODE=$(echo "$RESPONSE" | grep "HTTP_CODE:" | cut -d':' -f2)
BODY=$(echo "$RESPONSE" | sed '/HTTP_CODE:/d')

echo "Request:"
echo "POST ${API_URL}/auth"
echo "{\"email\": \"${TEST_EMAIL}\", \"password\": \"${TEST_PASSWORD}\"}"
echo ""
echo "Response (HTTP $HTTP_CODE):"
echo "$BODY" | jq . 2>/dev/null || echo "$BODY"
echo ""

JWT_TOKEN=""
if [ "$HTTP_CODE" = "200" ]; then
    JWT_TOKEN=$(echo "$BODY" | jq -r '.token' 2>/dev/null)
    if [ -n "$JWT_TOKEN" ] && [ "$JWT_TOKEN" != "null" ]; then
        print_result 0 "Login successful, JWT token obtained"
        echo "Token: ${JWT_TOKEN:0:50}..."
    else
        print_result 1 "Login successful but no token in response"
    fi
else
    print_result 1 "Login failed"
fi
echo ""

echo "======================================"
echo "Test 5: Update Password (Authenticated)"
echo "======================================"
echo ""

if [ -n "$JWT_TOKEN" ] && [ "$JWT_TOKEN" != "null" ]; then
    RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}" -X POST "${API_URL}/user/update-password" \
      -H "Content-Type: application/json" \
      -H "Authorization: Bearer ${JWT_TOKEN}" \
      -d "{\"currentPassword\": \"${TEST_PASSWORD}\", \"newPassword\": \"${NEW_PASSWORD}\", \"confirmPassword\": \"${NEW_PASSWORD}\"}")

    HTTP_CODE=$(echo "$RESPONSE" | grep "HTTP_CODE:" | cut -d':' -f2)
    BODY=$(echo "$RESPONSE" | sed '/HTTP_CODE:/d')

    echo "Request:"
    echo "POST ${API_URL}/user/update-password"
    echo "{\"currentPassword\": \"***\", \"newPassword\": \"***\", \"confirmPassword\": \"***\"}"
    echo ""
    echo "Response (HTTP $HTTP_CODE):"
    echo "$BODY" | jq . 2>/dev/null || echo "$BODY"
    echo ""

    if [ "$HTTP_CODE" = "200" ]; then
        print_result 0 "Password update successful"
    else
        print_result 1 "Password update failed"
    fi
else
    echo -e "${YELLOW}⚠ Skipping test (no JWT token available)${NC}"
fi
echo ""

echo "======================================"
echo "Test Summary"
echo "======================================"
echo ""
echo "API Endpoints tested:"
echo "1. POST /api/password/forgot - Request reset code"
echo "2. POST /api/password/verify-code - Verify reset code"
echo "3. POST /api/password/reset - Reset password with code"
echo "4. POST /api/auth - Login to get JWT"
echo "5. POST /api/user/update-password - Update password (authenticated)"
echo ""
echo "Email Templates:"
echo "- templates/emails/reset_password.html.twig"
echo "- templates/emails/password_changed.html.twig"
echo ""
echo -e "${YELLOW}Important:${NC}"
echo "- Check mailpit at http://localhost:8025 for reset emails"
echo "- Use actual reset codes from emails for tests 2 and 3"
echo "- Update TEST_EMAIL and passwords in this script for real tests"
echo ""

