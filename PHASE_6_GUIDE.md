# 🚀 Phase 6: Auction Result Module Guide

**Completion Date:** December 25, 2024
**Status:** Ready for Testing

---

## 📋 Features Implemented

1.  **Auction Recording**
    *   Inputter can record new auction results for existing securities.
    *   Captures key data: Amount Offered, Subscribed, Sold, Stop Rates, etc.
    *   Auto-calculation of `Total Amount Sold` (Competitive + Non-Competitive).

2.  **Auto-Calculations**
    *   **Bid-to-Cover Ratio**: Automatically calculated (`Amount Subscribed / Total Amount Sold`).
    *   **Subscription Level**: Automatically calculated as a percentage of `Amount Offered`.

3.  **Visualization**
    *   Dedicated "Auction Results" list with filters (Security, Date).
    *   Detailed view showing financial data, rates, and visual performance ratios (progress bars).

4.  **Integration**
    *   Linked to Securities module.
    *   Access via Sidebar ("Auction Results" and "Record Auction").

---

## 🧪 How to Test

### 1. Record an Auction
1.  Navigate to **Inputter > Record Auction** in the sidebar.
2.  Select a **Security** from the dropdown (make sure you created one in Phase 5!).
3.  Enter **Auction Number**, **Dates**, and **Tenor**.
4.  Enter **Amounts**:
    *   *Amount Offered*: e.g., 100,000,000
    *   *Amount Subscribed*: e.g., 150,000,000
    *   *Amount Sold*: e.g., 90,000,000
    *   *Non-Competitive*: e.g., 10,000,000 (Total Sold becomes 100,000,000)
5.  Enter **Rates** (e.g., Stop Rate: 12.5%).
6.  Click **Record Result**.

### 2. Verify Calculations
On the Result Details page, check:
*   **Total Amount Sold**: Should be 100,000,000.
*   **Bid-to-Cover Ratio**: Should be `1.5` (150M / 100M).
*   **Subscription Level**: Should be `150%` (150M / 100M).

### 3. Filters
1.  Go to **Securities > Auction Results**.
2.  Filter by the Security you just used.

---

## 🛠 Next Steps (Phase 7)
- **Advanced Search**: Global search across securities and auctions.
- **Reporting**: Generate PDF reports for Auction Calendars and Results.
- **Dashboard Widgets**: Show recent auctions on the main dashboard.
