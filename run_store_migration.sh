#!/bin/bash

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}   Store Order Management Migration   ${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Check if database is running
echo -e "${YELLOW}1. Checking database connection...${NC}"
php bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Database is connected${NC}"
else
    echo -e "${RED}✗ Database is not connected${NC}"
    echo -e "${YELLOW}Starting database...${NC}"
    docker compose up -d database
    sleep 5
fi

echo ""

# Run migration
echo -e "${YELLOW}2. Running migration...${NC}"
php bin/console doctrine:migrations:migrate --no-interaction
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Migration completed successfully${NC}"
else
    echo -e "${RED}✗ Migration failed${NC}"
    exit 1
fi

echo ""

# Verify columns
echo -e "${YELLOW}3. Verifying new columns...${NC}"

# Check order_item columns
echo -e "${BLUE}Checking order_item table:${NC}"
php bin/console doctrine:query:sql "SELECT column_name FROM information_schema.columns WHERE table_name = 'order_item' AND column_name IN ('store_status', 'store_notes', 'store_suggestion', 'suggested_product_id', 'store_price', 'store_action_at')" 2>/dev/null | grep -E "store_status|store_notes|store_suggestion|suggested_product_id|store_price|store_action_at" | while read line; do
    echo -e "${GREEN}  ✓ $line${NC}"
done

# Check order column
echo -e "${BLUE}Checking order table:${NC}"
php bin/console doctrine:query:sql "SELECT column_name FROM information_schema.columns WHERE table_name = 'order' AND column_name = 'store_total_amount'" 2>/dev/null | grep "store_total_amount" | while read line; do
    echo -e "${GREEN}  ✓ $line${NC}"
done

echo ""

# Summary
echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}       Migration Summary               ${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
echo -e "${GREEN}✓ Added to order_item:${NC}"
echo "  - store_status (enum)"
echo "  - store_notes (text)"
echo "  - store_suggestion (text)"
echo "  - suggested_product_id (foreign key)"
echo "  - store_price (float)"
echo "  - store_action_at (datetime)"
echo ""
echo -e "${GREEN}✓ Added to order:${NC}"
echo "  - store_total_amount (float)"
echo ""
echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}       Key Changes                     ${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
echo -e "${YELLOW}1. Store Inventory Validation:${NC}"
echo "   Stores can only accept/suggest products in their inventory"
echo ""
echo -e "${YELLOW}2. Automatic Pricing:${NC}"
echo "   Prices fetched from StoreProduct table"
echo "   Formula: quantity × storeProduct.price"
echo ""
echo -e "${YELLOW}3. Product Suggestions:${NC}"
echo "   Stores suggest actual products (not text)"
echo "   Admin approval replaces product and resets to PENDING"
echo ""
echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}       API Endpoints                   ${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
echo "POST /api/store/order-item/accept"
echo "  Body: {orderItemId, notes}"
echo ""
echo "POST /api/store/order-item/refuse"
echo "  Body: {orderItemId, reason}"
echo ""
echo "POST /api/store/order-item/suggest"
echo "  Body: {orderItemId, suggestedProductId, suggestion, notes}"
echo ""
echo "POST /api/admin/order-item/approve-suggestion"
echo "  Body: {orderItemId, adminNotes}"
echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}   Migration Complete! 🎉             ${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo "📚 Read documentation:"
echo "   - STORE_PRODUCT_INVENTORY_SYSTEM.md"
echo "   - STORE_PRODUCT_SUGGESTION_WORKFLOW.md"
echo "   - STORE_SUGGESTION_QUICK_GUIDE.md"
echo ""

