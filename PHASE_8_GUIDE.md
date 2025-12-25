# 🚀 Phase 8: Authoriser Workflow (Maker-Checker) Guide

**Completion Date:** December 25, 2024
**Status:** Ready for Testing

---

## 📋 Features Implemented

1.  **Pending Actions**
    *   **Logic**: Actions (Create, Update, Delete) performed by **Inputters** are intercepted.
    *   **Storage**: Saved to `pending_actions` table instead of being applied immediately.
    *   **Modules Covered**: Securities, Auction Results.

2.  **Authoriser Dashboard**
    *   **Location**: Sidebar > Authoriser > Pending Approvals.
    *   **Function**: Lists all actions awaiting approval.
    *   **Review**: Detailed view showing field comparisons (Old vs New values).

3.  **Approval/Rejection**
    *   **Approve**: Executes the action (inserts/updates/deletes the actual record).
    *   **Reject**: Marks action as rejected and provides a reason.

---

## 🧪 How to Test

### 1. Initiate Action (Inputter)
1.  Login as a user with **Inputter** role (and NOT Super Admin).
2.  Go to **Securities > Add Security**.
3.  Fill the form and submit.
4.  **Expectation**: You should see a success message: *"Security creation submitted for approval."* The security should **NOT** appear in the main list yet.

### 2. Review Action (Authoriser)
1.  Login as a user with **Authoriser** role (or Super Admin).
2.  Go to **Authoriser > Pending Approvals**.
3.  You should see the "Create Security" action listed.
4.  Click **Review**.

### 3. Approve Action
1.  On the Review page, check the data.
2.  Click **Approve & Execute**.
3.  **Expectation**: Success message. The Security should now appear in the **Securities List**.

### 4. Reject Action (Optional)
1.  Create another pending action.
2.  As Authoriser, click **Reject**.
3.  Enter a reason and confirm.
4.  **Expectation**: Action is marked rejected. Security is NOT created.

---

## ⚠️ Notes
*   **Super Admins** currently bypass this workflow and commit changes immediately effectively acting as "God Mode".
*   **Notifications**: Database notifications are prepared in the background logic but UI alerts (bell icon) rely on `DatabaseNotification` polling or listeners (to be fully integrated in next iterations if needed).
