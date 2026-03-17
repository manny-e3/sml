# SMLARS API Documentation (v1)

This document provides technical details for integrating with the SMLARS API.

## Base URL
`http://101-php-01.fmdqgroup.com/smlars/api/v1`

---

## Authentication

The API uses **HTTP Basic Authentication**. All requests must include an `Authorization` header.

### Credentials
- **Username:** `sml_system_integrator`
- **Password:** `Z@p7-Wx2!_mKq9_Rst5`

---

## Auction Results

### 1. List Auction Results
**Endpoint:** `GET /auction-results`

**Query Parameters:**
- `security_id` (optional): Filter by security ID.
- `auction_date` (optional): Filter by auction date (YYYY-MM-DD).
- `per_page` (optional): Pagination limit (default: 15).

**Success Response (200 OK):**
```json
{
    "current_page": 1,
    "data": [
        {
            "id": 10,
            "security_id": 1,
            "auction_number": "AUC-2026-200",
            "auction_date": "2026-02-10T00:00:00.000000Z",
            "value_date": "2026-02-27T00:00:00.000000Z",
            "day_of_week": "Tuesday",
            "tenor_days": 17,
            "amount_offered": "400.00",
            "amount_subscribed": "400.00",
            "amount_allotted": "5000.00",
            "amount_sold": "400.00",
            "non_competitive_sales": "399.99",
            "total_amount_sold": "799.99",
            "stop_rate": "39.9900",
            "marginal_rate": null,
            "true_yield": "40.7490",
            "bid_cover_ratio": "0.5000",
            "subscription_level": "100.00",
            "auction_type": "Primary",
            "status": "Completed",
            "approval_status": "approved",
            "remarks": null,
            "created_by": 45,
            "updated_by": 45,
            "approved_by": null,
            "approved_at": "2026-02-25T13:11:36.000000Z",
            "created_at": "2026-02-25T08:50:00.000000Z",
            "updated_at": "2026-02-25T13:11:36.000000Z",
            "deleted_at": null,
            "creator": null,
            "security_name": null,
            "product_name": null
        }
    ],
    "total": 4
}
```

---

## Security Master Data

### 1. List Security Master Data
**Endpoint:** `GET /security-master-data`

**Query Parameters:**
- `category_id` (optional): Filter by market category.
- `per_page` (optional): Pagination limit (default: 15).

**Success Response (200 OK):**
```json
{
    "current_page": 1,
    "data": [
        {
            "id": 9,
            "security_name": "Yeagar",
            "category": { "id": 1, "name": "Bonds" },
            "product": { "id": 3, "name": "FGN Green Bond" },
            "status": false,
            "created_by": "12",
            "created_by_name": null,
            "approval_status": "approved",
            "fields": [
                { "field_id": 1, "field_name": "Issue Category", "value": "new category" },
                { "field_id": 2, "field_name": "Issuer", "value": "Coronation" },
                { "field_id": 3, "field_name": "Security Name", "value": "Yeagar" },
                { "field_id": 4, "field_name": "ISIN", "value": "10200222" },
                { "field_id": 5, "field_name": "Description", "value": "This is the description" },
                { "field_id": 6, "field_name": "Issue Date", "value": "2026-03-20" },
                { "field_id": 7, "field_name": "Maturity Date", "value": "2027-03-20" },
                { "field_id": 8, "field_name": "Tenor", "value": "1" },
                { "field_id": 9, "field_name": "Coupon (%)", "value": "20" },
                { "field_id": 10, "field_name": "Coupon Type", "value": "1" },
                { "field_id": 11, "field_name": "Coupon Frequency", "value": "2" },
                { "field_id": 12, "field_name": "Effective Coupon (%)", "value": "20" },
                { "field_id": 20, "field_name": "Yield at Issue", "value": "20" },
                { "field_id": 22, "field_name": "Listing Status", "value": "1" }
            ]
        }
    ],
    "total": 6
}
```

---

## Error Responses

| Code | Meaning | Description |
|---|---|---|
| 401 | Unauthenticated | Missing or invalid Authorization header. |
| 403 | Forbidden | User does not have permission. |
| 500 | Server Error | Internal server error. |
