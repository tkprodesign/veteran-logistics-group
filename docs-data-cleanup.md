# Data Cleanup / ID Resequencing Audit

Use this script to audit row counts, ID gaps, and inferred table dependencies before deleting test data:

```bash
php tools/data_cleanup_audit.php
```

The report includes:
- probable "meeting with us" counts (`free_quotes_requests`, `quotes`, `shipment_service_quotes`)
- ID gap analysis for tables with `id` auto-increment primary keys
- inferred dependencies from `*_id` columns
- risk classification of tables (`HARD_TO_TOUCH`, `MEDIUM_TOUCH`, `COOL_TO_TOUCH`)

> Important: Avoid resequencing primary IDs in production. Delete unwanted rows, then reset `AUTO_INCREMENT` to `MAX(id)+1` instead.
