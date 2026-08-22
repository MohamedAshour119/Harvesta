# Checkout Gate Implementation Plan

> **For Antigravity:** REQUIRED WORKFLOW: Use `.agent/workflows/execute-plan.md` to execute this plan in single-flow mode.

**Goal:** Prevent users from accessing the checkout page if their cart is empty or if they are logged in as an agent, redirecting them to the cart page in both cases.

**Architecture:** Enhance the existing `PreventAgentCheckoutRoute.tsx` auth guard to check the Redux cart state.

**Tech Stack:** React, Redux (Toolkit), React Router.

---

### Task 1: Update the Checkout Guard component

**Files:**
- Modify: `resources/frontend/src/auth/PreventAgentCheckoutRoute.tsx`

**Step 1: Update the component logic**
Add `items` from `state.cart` and modify the redirect condition.

**Step 2: Verify the logic**
Ensure that both agents and users with empty carts are redirected correctly.

**Step 3: Commit**

```bash
git add resources/frontend/src/auth/PreventAgentCheckoutRoute.tsx
git commit -m "feat: prevent checkout access if cart is empty or user is an agent"
```

---

### Task 2: Manual Verification

**Step 1: Verify Empty Cart Redirect**
1. Open the browser.
2. Ensure the cart is empty.
3. Manually navigate to /checkout.
4. **Expected:** Redirected back to /cart.

**Step 2: Verify Non-Empty Cart Access**
1. Add a product to the cart.
2. Navigate to /checkout.
3. **Expected:** Checkout page loads successfully.
