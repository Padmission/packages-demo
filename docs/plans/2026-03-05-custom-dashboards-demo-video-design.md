# Custom Dashboards Demo Video Design

## Purpose

Internal dev demo showing how Data Lens widgets work with Filament Custom Dashboards plugin.

## Output

- Format: 1920x1080 MP4
- Duration: ~60-90 seconds
- Location: `docs/demo/custom-dashboards-demo.mp4`

## Demo Data

Dedicated `VideoDemoSeeder` with curated data for visual impact:

- 8 brands, 6 categories, 40+ products ($15-$899)
- 30 customers, 100+ orders over 6 months (growing revenue trend)
- Mixed payment methods (credit card 45%, PayPal 30%, bank transfer 15%, crypto 10%)
- 4 Data Lens reports with pre-configured summaries:
  - Sales Dashboard: monthly revenue line chart, order status pie chart, KPI stats
  - Customer Analytics: lifetime value bar chart, top customer stats
  - Payment Analytics: payment method pie chart, transaction volume line chart
  - Product Catalog: category price comparison bar chart

## Recording Flow

1. Show Data Lens reports list (5s)
2. Navigate to Custom Dashboards (3s)
3. Create new dashboard "Sales Overview" (5s)
4. Add Data Lens Stats widget -- Sales Dashboard KPI (15s)
5. Add Data Lens Chart widget -- Payment method pie chart (15s)
6. Add Data Lens Table widget -- Customer lifetime value (15s)
7. Add non-Data Lens Eloquent widget for contrast (10s)
8. Final dashboard view with all widgets (10s)

## Technical Approach

- `screencapture -v` for macOS native screen recording
- `agent-browser` for automated browser interactions
- 1-2 second pauses between actions for readability
- Chrome at 1920x1080 viewport
- Fresh database seeded with VideoDemoSeeder
