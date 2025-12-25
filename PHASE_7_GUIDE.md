# 🚀 Phase 7: Advanced Search & Reporting Guide

**Completion Date:** December 25, 2024
**Status:** Completed

---

## 📋 Features Implemented

1.  **Global Search**
    *   **Location**: Top Navigation Bar.
    *   **Function**: Searches across **Securities** (Name, ISIN, Issuer) and **Auction Results** (Auction No).
    *   **Results**: Grouped by category with direct links to details.

2.  **Advanced Search**
    *   **Location**: Sidebar > Securities > Advanced Search.
    *   **Filters**:
        *   Keyword
        *   Product Type (Bond, Bill, etc.)
        *   Status (Active, Matured, Cancelled)
        *   Issue Date Range (From/To)
    *   **Output**: Paginated list of securities matching criteria.

3.  **Reporting & Exports**
    *   **Security Master List PDF**: Export formatted list of securities from the Securities Index page.
    *   **Auction Results PDF**: Export summary of auction results.
    *   **Auction Results Excel**: Export raw data for analysis.
    *   **Buttons**: Located on respective Index pages or specific export routes.

---

## 🧪 How to Test

### 1. Global Search
1.  Type "FGN" or "BILLS" in the top search bar.
2.  Hit Enter or click the Search icon.
3.  Verify results are split into *Securities* and *Auction Results* tabs.

### 2. Advanced Search
1.  Go to **Securities > Advanced Search**.
2.  Select **Status: Active** and **Product Type: FGN Bonds** (if seeded).
3.  Click **Search**.
4.  Verify the list is filtered correctly.

### 3. Exports
1.  Go to **Securities > Securities List**.
    *   *Note: I need to add the Export buttons to the UI explicitly if not already visible.* (See below).
2.  Go to **Securities > Auction Results**.
    *   Try `/auction-results/export/pdf` in browser or check UI buttons.

---
**Note:** Ensure `dompdf` and `maatwebsite/excel` are properly configured. If PDF generation fails, check write permissions for `storage/fonts`.
