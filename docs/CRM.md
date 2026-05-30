# TruckFund CRM hierarchy

## Roles

| Role | Value | Assign leads | Manage users | Request transfer |
|------|-------|--------------|--------------|------------------|
| Admin | 1 | Anyone in CRM | All | — |
| Sales | 2 (`SalesAgent`) | — | — | Yes (to teammate under same TL) |
| Finance Officer | 3 | — | — | — |
| Manager | 5 | TL + Sales in tree | TL + Sales | Approve transfers |
| Team Leader | 6 | Own sales | Own sales | Approve transfers |

Set `reports_to_user_id` on users to build the tree: Manager → TL → Sales.

## Lead visibility

A user sees a lead if they are **Admin**, or the lead is assigned to themselves or any **subordinate** (recursive). Managers/TLs also see **unassigned** leads.

## Transfer workflow

1. Sales submits transfer to another sales under the same Team Leader.
2. TL or Manager (or Admin) approves or rejects on the lead detail page.
3. Approval reassigns the lead; actions are logged in communication notes and audit logs.

## Lead source (`source` column)

- `0` Admin dashboard  
- `1` Sales input  
- `2` External API (`POST /api/leads` with `Authorization: Bearer {TRUCKFUND_API_TOKEN}`)  
- `3` Referral (freelancer-linked)  
- `4` Import  

## Freelancers (References tab)

External referrers without login. Locked after create; only Admin can edit.

## Migrations (included in base schema)

- `0001_01_01_000000_create_users_table` — `reports_to_user_id`  
- `2026_05_21_100000_create_freelancers_table`  
- `2026_05_21_100001_create_leads_table` — `created_by_user_id`, `source`, `freelancer_id`  
- `2026_05_21_100018_create_lead_transfer_requests_table`  

## Demo logins (after `php artisan truckfund:seed-demo`)

| Email | Password | Role |
|-------|----------|------|
| admin@truckfund.test | password | Admin |
| manager@truckfund.test | password | Manager |
| tl@truckfund.test | password | Team Leader |
| sales@truckfund.test | password | Sales |
| sales2@truckfund.test | password | Sales |
