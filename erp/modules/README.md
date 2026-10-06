# ERP Module Boundary

Each module will be migrated from the current root-level PHP application into a predictable ERP structure.

## Module order

01 identity
02 theatres
03 movies
04 screens
05 seats
06 shows
07 bookings
08 payments
09 tickets
10 pos
11 inventory
12 procurement
13 hr
14 maintenance
15 finance
16 reports
17 ai
18 security

Keep business rules in services rather than duplicating SQL and validation across pages.
