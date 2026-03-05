#!/bin/bash
set -e

# Custom Dashboards + Data Lens Demo Video Recording Script
# Records a 1080p demo showing Data Lens widgets on Custom Dashboards
#
# Prerequisites:
#   1. php artisan migrate:fresh --no-interaction
#   2. php artisan db:seed --class=VideoDemoSeeder --no-interaction
#   3. npm run build

BASE_URL="https://packages-demo.padmission.test"
TEAM_ID=$(php artisan tinker --execute="echo \App\Models\Team::first()->id;" 2>/dev/null | tail -1)
OUTPUT_FILE="docs/demo/custom-dashboards-demo.mp4"

echo "==> Team ID: $TEAM_ID"
echo "==> Output: $OUTPUT_FILE"

# Helper: wait for page to stabilize
wait_stable() {
    agent-browser wait --load networkidle 2>/dev/null || true
    sleep "${1:-2}"
}

# Helper: snapshot and return refs
snap() {
    agent-browser snapshot -i 2>&1
}

# ============================================================
# Set viewport to 1920x1080
# ============================================================
echo "==> Setting viewport"
agent-browser set viewport 1920 1080 2>/dev/null

# ============================================================
# Start screen recording
# ============================================================
echo "==> Starting screen recording"
screencapture -v -D 1 "$OUTPUT_FILE" &
RECORD_PID=$!
sleep 3

# ============================================================
# Scene 1: Login
# ============================================================
echo "==> Scene 1: Login"
agent-browser open "$BASE_URL/app/login" 2>/dev/null
wait_stable 2

# Fill login form - refs are stable on login page
agent-browser fill 'input[name="email"]' "video-demo@padmission.com" 2>/dev/null
sleep 0.5
agent-browser fill 'input[name="password"]' "demo2024" 2>/dev/null
sleep 0.5
agent-browser click 'button:has-text("Sign in")' 2>/dev/null
wait_stable 3

# ============================================================
# Scene 2: Data Lens Reports List
# ============================================================
echo "==> Scene 2: Show Data Lens reports"
agent-browser open "$BASE_URL/app/$TEAM_ID/data-lens" 2>/dev/null
wait_stable 3

# ============================================================
# Scene 3: Custom Dashboards (empty state)
# ============================================================
echo "==> Scene 3: Custom Dashboards"
agent-browser open "$BASE_URL/app/$TEAM_ID/custom-dashboards" 2>/dev/null
wait_stable 2

# ============================================================
# Scene 4: Create new dashboard
# ============================================================
echo "==> Scene 4: Create dashboard"
# Click "New custom dashboard" button
REFS=$(snap)
agent-browser click 'button:has-text("New custom dashboard")' 2>/dev/null
wait_stable 2

# Fill dashboard name
agent-browser fill 'input[placeholder=""]' "" 2>/dev/null || true
REFS=$(snap)
# Find the Name field and fill it
NAME_REF=$(echo "$REFS" | grep 'textbox "Name' | head -1 | grep -oE '@e[0-9]+' || echo "")
if [ -n "$NAME_REF" ]; then
    agent-browser fill "$NAME_REF" "Sales Overview" 2>/dev/null
else
    # Fallback: use CSS selector
    agent-browser fill 'input[wire\\:model\\.live\\.debounce\\.500ms]' "Sales Overview" 2>/dev/null || true
fi
sleep 2

# Click Create button (may need double-click due to live validation)
agent-browser click 'button:has-text("Create"):not(:has-text("another"))' 2>/dev/null
sleep 2
agent-browser click 'button:has-text("Create"):not(:has-text("another"))' 2>/dev/null
wait_stable 3

# ============================================================
# Scene 5: Add Data Lens Stats Widget (Revenue KPIs)
# ============================================================
echo "==> Scene 5: Add Stats Widget"

# Click "Insert widget"
agent-browser click 'button:has-text("Insert widget")' 2>/dev/null
wait_stable 2

# Select "Data Lens Stats"
agent-browser click 'button:has-text("Data Lens Stats")' 2>/dev/null
sleep 1

# Click "Configure widget"
agent-browser click 'button:has-text("Configure widget")' 2>/dev/null
wait_stable 2

# Select report: Sales Dashboard
REFS=$(snap)
REPORT_BTN=$(echo "$REFS" | grep 'button "Select an option"' | head -1 | grep -oE '@e[0-9]+')
agent-browser click "$REPORT_BTN" 2>/dev/null
sleep 1
agent-browser click 'li:has-text("Sales Dashboard")' 2>/dev/null || \
    agent-browser click 'option:has-text("Sales Dashboard")' 2>/dev/null || true
wait_stable 2

# Select summary: Revenue KPIs
REFS=$(snap)
SUMMARY_BTN=$(echo "$REFS" | grep 'button "Select an option"' | head -1 | grep -oE '@e[0-9]+')
agent-browser click "$SUMMARY_BTN" 2>/dev/null
sleep 1
agent-browser click 'li:has-text("Revenue KPIs")' 2>/dev/null || \
    agent-browser click 'option:has-text("Revenue KPIs")' 2>/dev/null || true
wait_stable 2

# Select widget from native select
REFS=$(snap)
WIDGET_SELECT=$(echo "$REFS" | grep 'combobox "Widget"' | head -1 | grep -oE '@e[0-9]+')
if [ -n "$WIDGET_SELECT" ]; then
    agent-browser select "$WIDGET_SELECT" "revenue_kpis" 2>/dev/null
    sleep 1
fi

# Click "Insert widget" (the one inside the config form)
REFS=$(snap)
INSERT_BTN=$(echo "$REFS" | grep 'button "Insert widget"' | tail -1 | grep -oE '@e[0-9]+')
agent-browser click "$INSERT_BTN" 2>/dev/null
wait_stable 3

echo "==> Stats widget inserted"

# ============================================================
# Scene 6: Add Data Lens Chart Widget (Payment Pie Chart)
# ============================================================
echo "==> Scene 6: Add Chart Widget"

# Click "+ Insert widget" in edit mode header
REFS=$(snap)
INSERT_BTN=$(echo "$REFS" | grep 'button "Insert widget"' | head -1 | grep -oE '@e[0-9]+')
agent-browser click "$INSERT_BTN" 2>/dev/null
wait_stable 2

# Select "Data Lens Chart"
agent-browser click 'button:has-text("Data Lens Chart")' 2>/dev/null
sleep 1
agent-browser click 'button:has-text("Configure widget")' 2>/dev/null
wait_stable 2

# Select report: Payment Analytics
REFS=$(snap)
REPORT_BTN=$(echo "$REFS" | grep 'button "Select an option"' | head -1 | grep -oE '@e[0-9]+')
agent-browser click "$REPORT_BTN" 2>/dev/null
sleep 1
agent-browser click 'li:has-text("Payment Analytics")' 2>/dev/null || \
    agent-browser click 'option:has-text("Payment Analytics")' 2>/dev/null || true
wait_stable 2

# Select summary: Payment Method Breakdown
REFS=$(snap)
SUMMARY_BTN=$(echo "$REFS" | grep 'button "Select an option"' | head -1 | grep -oE '@e[0-9]+')
agent-browser click "$SUMMARY_BTN" 2>/dev/null
sleep 1
agent-browser click 'li:has-text("Payment Method Breakdown")' 2>/dev/null || \
    agent-browser click 'option:has-text("Payment Method Breakdown")' 2>/dev/null || true
wait_stable 2

# Select widget from native select
REFS=$(snap)
WIDGET_SELECT=$(echo "$REFS" | grep 'combobox "Widget"' | head -1 | grep -oE '@e[0-9]+')
if [ -n "$WIDGET_SELECT" ]; then
    agent-browser select "$WIDGET_SELECT" "method_pie" 2>/dev/null
    sleep 1
fi

# Insert
REFS=$(snap)
INSERT_BTN=$(echo "$REFS" | grep 'button "Insert widget"' | tail -1 | grep -oE '@e[0-9]+')
agent-browser click "$INSERT_BTN" 2>/dev/null
wait_stable 3

echo "==> Chart widget inserted"

# ============================================================
# Scene 7: Add Data Lens Table Widget (Brand Price Overview)
# ============================================================
echo "==> Scene 7: Add Table Widget"

REFS=$(snap)
INSERT_BTN=$(echo "$REFS" | grep 'button "Insert widget"' | head -1 | grep -oE '@e[0-9]+')
agent-browser click "$INSERT_BTN" 2>/dev/null
wait_stable 2

# Select "Data Lens Table"
agent-browser click 'button:has-text("Data Lens Table")' 2>/dev/null
sleep 1
agent-browser click 'button:has-text("Configure widget")' 2>/dev/null
wait_stable 2

# Select report: Product Catalog
REFS=$(snap)
REPORT_BTN=$(echo "$REFS" | grep 'button "Select an option"' | head -1 | grep -oE '@e[0-9]+')
agent-browser click "$REPORT_BTN" 2>/dev/null
sleep 1
agent-browser click 'li:has-text("Product Catalog")' 2>/dev/null || \
    agent-browser click 'option:has-text("Product Catalog")' 2>/dev/null || true
wait_stable 2

# Select summary: Brand Price Overview
REFS=$(snap)
SUMMARY_BTN=$(echo "$REFS" | grep 'button "Select an option"' | head -1 | grep -oE '@e[0-9]+')
agent-browser click "$SUMMARY_BTN" 2>/dev/null
sleep 1
agent-browser click 'li:has-text("Brand Price Overview")' 2>/dev/null || \
    agent-browser click 'option:has-text("Brand Price Overview")' 2>/dev/null || true
wait_stable 2

# No widget select needed for Table widget - just insert
REFS=$(snap)
INSERT_BTN=$(echo "$REFS" | grep 'button "Insert widget"' | tail -1 | grep -oE '@e[0-9]+')
agent-browser click "$INSERT_BTN" 2>/dev/null
wait_stable 3

echo "==> Table widget inserted"

# ============================================================
# Scene 8: Stop editing and show final dashboard
# ============================================================
echo "==> Scene 8: Final dashboard view"

# Scroll to top first
agent-browser eval "window.scrollTo(0, 0)" 2>/dev/null
sleep 1

# Stop editing
agent-browser click 'button:has-text("Stop editing")' 2>/dev/null
wait_stable 3

# Slow scroll down to reveal the full dashboard
agent-browser eval "window.scrollTo({top: 400, behavior: 'smooth'})" 2>/dev/null
sleep 2
agent-browser eval "window.scrollTo({top: 800, behavior: 'smooth'})" 2>/dev/null
sleep 2
agent-browser eval "window.scrollTo({top: document.body.scrollHeight, behavior: 'smooth'})" 2>/dev/null
sleep 3

# Scroll back to top for final view
agent-browser eval "window.scrollTo({top: 0, behavior: 'smooth'})" 2>/dev/null
sleep 3

# ============================================================
# Stop recording
# ============================================================
echo "==> Stopping recording"
kill -2 $RECORD_PID 2>/dev/null || true
wait $RECORD_PID 2>/dev/null || true
sleep 2

echo "==> Recording saved to $OUTPUT_FILE"
if [ -f "$OUTPUT_FILE" ]; then
    ls -lh "$OUTPUT_FILE"
else
    echo "WARNING: Output file not found"
fi
