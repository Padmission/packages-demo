# Custom Dashboards Demo Video Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Record an automated 1080p demo video showing Data Lens widgets on Filament Custom Dashboards.

**Architecture:** Three phases -- (1) create VideoDemoSeeder with curated data and reports with summaries/widgets, (2) seed database and verify the app works, (3) automate browser interactions with agent-browser while recording screen with macOS screencapture.

**Tech Stack:** Laravel seeders, agent-browser CLI, macOS screencapture, Filament Custom Dashboards Plugin, Data Lens widgets.

**App URL:** `https://packages-demo.padmission.test`

**Panel path:** `/app/{tenant-slug}/`

---

### Task 1: Create VideoDemoSeeder -- Shop Data

**Files:**
- Create: `database/seeders/VideoDemoSeeder.php`

**Step 1: Create the seeder file**

Create `database/seeders/VideoDemoSeeder.php` modeled on `DemoSeeder.php` but with curated data. The seeder should:

1. Create a user with email `video-demo@padmission.com` and password `demo2024`
2. Create a team named `TechFlow Inc`
3. Attach user to team as owner

**Shop data (call `seedShopData`):**
- 8 brands with specific names: `['Apple', 'Samsung', 'Sony', 'Nike', 'Adidas', 'Dyson', 'Bose', 'Herman Miller']`
- 6 categories: `['Electronics', 'Audio', 'Sportswear', 'Home & Office', 'Accessories', 'Premium']`
- 40 products with controlled prices ($15-$899), distributed across brands/categories
- 30 customers
- Generate 120+ orders with dates spread over 6 months with a growth trend:
  - Month 1 (6 months ago): ~12 orders
  - Month 2: ~15 orders
  - Month 3: ~18 orders
  - Month 4: ~22 orders
  - Month 5: ~25 orders
  - Month 6 (current): ~30 orders
- Payments with controlled method distribution:
  - credit_card: 45% of payments
  - paypal: 30%
  - bank_transfer: 15%
  - crypto: 10%
- Each order: 1-4 items, prices from products

**Blog data (call `seedBlogData`):**
- 3 authors, 4 categories, 12 posts with comments (reuse existing DemoSeeder blog logic)

Use the same model classes as DemoSeeder. Reference `DemoSeeder.php` for exact factory/model patterns.

**Step 2: Verify seeder compiles**

Run: `php artisan tinker --execute="require 'database/seeders/VideoDemoSeeder.php'; echo 'OK';"`
Expected: No syntax errors

**Step 3: Commit**

```bash
git add database/seeders/VideoDemoSeeder.php
git commit -m "feat: add VideoDemoSeeder with curated shop data"
```

---

### Task 2: Add Data Lens Reports with Summaries to VideoDemoSeeder

**Files:**
- Modify: `database/seeders/VideoDemoSeeder.php`

**Step 1: Add `seedDataLensReports` method**

Add 4 reports with pre-configured summaries (modeled on DemoSeeder's summary structure). Each summary needs `widget_configurations` with chart/stats widgets.

**Report 1: Sales Dashboard** (model: `Order`)
- Columns: Order #, Customer name (via customer relation), Status (badge), Date, Items Count (aggregate count items), Total Paid (aggregate sum payments.amount)
- Filters: last 6 months, status in [processing, shipped, delivered]
- Summary 1: "Revenue KPIs" -- no grouping, aggregations: [count orders as "Total Orders" (number), sum payments.amount as "Total Revenue" (money), avg payments.amount as "Avg Order Value" (money)]
  - Widget: stats_overview with 3 metrics
- Summary 2: "Monthly Revenue Trend" -- group by created_at month, aggregations: [count as "Order Count" (number), sum payments.amount as "Revenue" (money)]
  - Widget: line chart, x=month, y=Revenue

**Report 2: Payment Analytics** (model: `Payment`)
- Columns: reference, method (badge), provider, amount (money), created_at (datetime), order.customer.name
- Filters: last 3 months
- Summary 1: "Payment Method Breakdown" -- group by method, aggregations: [count as "Transactions" (number), sum amount as "Total" (money)]
  - Widget: pie chart, data_field=Total, label_field=method
- Summary 2: "Monthly Transactions" -- group by created_at month, aggregations: [count as "Count" (number), sum amount as "Volume" (money)]
  - Widget: line chart, x=month, y=Volume

**Report 3: Customer Analytics** (model: `Customer`)
- Columns: name, email, created_at (date), order count (aggregate), lifetime value (aggregate sum orders.total_price)
- Filters: customers with 2+ orders
- Summary 1: "Customer Value Summary" -- no grouping, aggregations: [count as "Total Customers" (number), sum orders.total_price as "Total Lifetime Value" (money), avg orders.total_price as "Avg Lifetime Value" (money), max orders.total_price as "Top Customer Value" (money)]
  - Widget: stats_overview

**Report 4: Product Catalog** (model: `Product`)
- Columns: name, sku, brand.name, price (money), qty (number), is_visible (boolean)
- Filters: visible, in-stock
- Summary 1: "Category Price Overview" -- group by brand.name (via relationship), aggregations: [count as "Products" (number), avg price as "Avg Price" (money), sum qty as "Total Stock" (number)]
  - Widget: bar chart, x=brand, y=Avg Price

Use the exact same JSON structure for columns, filters, summaries, and widget_configurations as seen in `DemoSeeder.php`. Copy the pattern precisely for filter groups, aggregate expressions, summary configurations, and widget configs.

**Step 2: Verify seeder runs**

```bash
php artisan db:seed --class=VideoDemoSeeder --no-interaction
```

Expected: Seeder completes without errors. Check database:
```bash
php artisan tinker --execute="echo 'Reports: ' . \Padmission\DataLens\Models\CustomReport::count() . ', Summaries: ' . \Padmission\DataLens\Models\CustomReportSummary::count();"
```

**Step 3: Commit**

```bash
git add database/seeders/VideoDemoSeeder.php
git commit -m "feat: add Data Lens reports with summaries to VideoDemoSeeder"
```

---

### Task 3: Reset Database and Seed for Video

**Step 1: Migrate fresh and seed**

```bash
php artisan migrate:fresh --no-interaction
php artisan db:seed --class=VideoDemoSeeder --no-interaction
```

**Step 2: Verify data**

```bash
php artisan tinker --execute="
\$team = \App\Models\Team::first();
echo 'Team: ' . \$team->name . PHP_EOL;
echo 'Products: ' . \App\Models\Shop\Product::where('team_id', \$team->id)->count() . PHP_EOL;
echo 'Orders: ' . \App\Models\Shop\Order::where('team_id', \$team->id)->count() . PHP_EOL;
echo 'Payments: ' . \App\Models\Shop\Payment::where('team_id', \$team->id)->count() . PHP_EOL;
echo 'Reports: ' . \Padmission\DataLens\Models\CustomReport::where('team_id', \$team->id)->count() . PHP_EOL;
echo 'Summaries: ' . \Padmission\DataLens\Models\CustomReportSummary::count() . PHP_EOL;
"
```

Expected: Team: TechFlow Inc, ~40 products, ~120 orders, ~80+ payments, 4 reports, 5+ summaries

**Step 3: Build frontend assets**

```bash
npm run build
```

**Step 4: Verify app loads**

Open `https://packages-demo.padmission.test/app/login` in browser. Login with `video-demo@padmission.com` / `demo2024`. Verify Data Lens reports page shows 4 reports and Custom Dashboards page loads.

---

### Task 4: Write Browser Automation Script

**Files:**
- Create: `docs/demo/record-demo.sh`

**Step 1: Create the recording script**

The script uses `agent-browser` CLI for browser automation and `screencapture` for recording. It should be a bash script that:

1. Sets viewport to 1920x1080
2. Starts macOS screen recording with `screencapture -v -R 0,0,1920,1080 docs/demo/custom-dashboards-demo.mp4 &`
3. Waits 2 seconds for recording to initialize
4. Executes the demo flow (see steps below)
5. Stops recording with `kill` signal

**Demo flow commands (with 2-second sleeps between actions):**

```
# Get team slug
TEAM_SLUG=$(php artisan tinker --execute="echo \App\Models\Team::first()->slug ?? \Illuminate\Support\Str::slug(\App\Models\Team::first()->name);" 2>/dev/null | tail -1)
BASE_URL="https://packages-demo.padmission.test"

# Scene 1: Login
agent-browser open "$BASE_URL/app/login"
agent-browser wait --load networkidle
agent-browser snapshot -i
# Fill email, password, click login
agent-browser fill [email-ref] "video-demo@padmission.com"
agent-browser fill [password-ref] "demo2024"
agent-browser click [login-button-ref]
agent-browser wait --load networkidle

# Scene 2: Navigate to Data Lens (show reports exist)
agent-browser open "$BASE_URL/app/$TEAM_SLUG/data-lens"
agent-browser wait --load networkidle
sleep 3  # Pause to show reports list

# Scene 3: Navigate to Custom Dashboards
agent-browser open "$BASE_URL/app/$TEAM_SLUG/custom-dashboards"
agent-browser wait --load networkidle
sleep 2

# Scene 4: Create new dashboard
agent-browser snapshot -i
# Click "Create" header action button
# Fill name: "Sales Overview", slug auto-fills
# Click create/submit
agent-browser wait --load networkidle
sleep 2

# Scene 5: Enter edit mode, add Data Lens Stats widget
# Click "Edit" button in header
# Click "+ Insert Widget" button
# In modal: find and click "Data Lens Stats" widget card
# Click "Configure"
# In config form: select report dropdown -> "Sales Dashboard"
# Wait for summary dropdown to load -> select "Revenue KPIs"
# Wait for widget dropdown to load -> select stats widget
# Click "Insert"
agent-browser wait --load networkidle
sleep 3  # Pause to show stats widget

# Scene 6: Add Data Lens Chart widget
# Click "+ Insert Widget" again
# Select "Data Lens Chart" widget card
# Click "Configure"
# Select report -> "Payment Analytics"
# Select summary -> "Payment Method Breakdown"
# Select widget -> pie chart
# Click "Insert"
agent-browser wait --load networkidle
sleep 3  # Pause to show chart

# Scene 7: Add Data Lens Table widget
# Click "+ Insert Widget"
# Select "Data Lens Table" widget card
# Click "Configure"
# Select report -> "Customer Analytics"
# Select summary -> "Customer Value Summary"
# Click "Insert"
agent-browser wait --load networkidle
sleep 3

# Scene 8: Add non-Data Lens widget (Eloquent data source)
# Click "+ Insert Widget"
# Find and click a built-in widget (e.g., Product stats)
# Configure if needed
# Click "Insert"
agent-browser wait --load networkidle
sleep 2

# Scene 9: Exit edit mode, show final dashboard
# Click "Stop Editing"
agent-browser wait --load networkidle
sleep 5  # Long pause on final view
```

NOTE: The exact element refs (`@e1`, `@e2`) will be determined at runtime by `agent-browser snapshot -i`. The script should use `snapshot -i` before each interaction to get current element refs, then click/fill the correct refs.

**Important:** Due to Livewire's dynamic nature, use `agent-browser wait --load networkidle` after every action that triggers a server request. Use `agent-browser snapshot -i` frequently to re-read the DOM after Livewire updates.

**Step 2: Make script executable**

```bash
chmod +x docs/demo/record-demo.sh
```

**Step 3: Commit**

```bash
git add docs/demo/record-demo.sh
git commit -m "feat: add browser automation script for demo video recording"
```

---

### Task 5: Test Browser Automation (Dry Run)

**Step 1: Test login flow**

Run just the login portion of the script manually:
```bash
agent-browser open "https://packages-demo.padmission.test/app/login"
agent-browser wait --load networkidle
agent-browser snapshot -i
```

Verify elements are visible and identifiable. Test filling login form and submitting.

**Step 2: Test dashboard creation flow**

After login, navigate to custom dashboards and test creating a dashboard:
```bash
agent-browser snapshot -i
# Identify the "Create" button ref and click it
# Fill the form
# Submit
```

**Step 3: Test widget insertion flow**

On the created dashboard, test the full widget insertion cycle:
- Enter edit mode
- Open insert widget modal
- Select Data Lens Stats widget
- Configure with a report/summary
- Insert

Fix any element targeting issues in the script.

**Step 4: Commit fixes**

```bash
git add docs/demo/record-demo.sh
git commit -m "fix: update demo script with correct element refs"
```

---

### Task 6: Record the Demo Video

**Step 1: Fresh database seed**

```bash
php artisan migrate:fresh --no-interaction && php artisan db:seed --class=VideoDemoSeeder --no-interaction
```

**Step 2: Close unnecessary apps**

Close apps that might interfere with screen recording. Set macOS Do Not Disturb on.

**Step 3: Run the recording**

Execute the demo script. Since `screencapture -v` is interactive (requires user to select area), an alternative approach:

```bash
# Start screen recording in background (records entire screen)
screencapture -v -D 1 docs/demo/custom-dashboards-demo.mp4 &
RECORD_PID=$!
sleep 2

# Run automation
# ... (all agent-browser commands)

# Stop recording
kill -2 $RECORD_PID  # SIGINT to stop gracefully
```

Or use `ffmpeg` if available:
```bash
ffmpeg -f avfoundation -framerate 30 -video_size 1920x1080 -i "1:" -c:v libx264 -pix_fmt yuv420p docs/demo/custom-dashboards-demo.mp4 &
```

**Step 4: Verify output**

Check the recording exists and plays:
```bash
ls -la docs/demo/custom-dashboards-demo.mp4
ffprobe docs/demo/custom-dashboards-demo.mp4 2>&1 | grep -E "Duration|Video"
```

Expected: MP4 file, ~60-90 seconds, 1920x1080 resolution.

**Step 5: Commit**

Do NOT commit the video file to git (too large). Add to `.gitignore` if needed.

```bash
echo "docs/demo/*.mp4" >> .gitignore
git add .gitignore
git commit -m "chore: exclude demo videos from git"
```
