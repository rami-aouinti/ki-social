# Test evidence — expected scenarios

This matrix describes the expected assertions encoded in PHPUnit. It is not a claim that tests have already been executed in the remote editing session. Run the suite locally and attach the actual terminal output or screenshots before submission.

| Case | Input | Expected result |
|---|---|---|
| 1. Retry | Complete post, current approval, handoff twice | One publishing_handoffs row; both calls return same ID |
| 2. Missing approval | Draft post, handoff requested | RuntimeException; no handoff row |
| 2b. Missing media | Media reference null, submit requested | RuntimeException; no approval |
| 3. Edited content | Approve v1, change body and version to v2/draft, handoff | RuntimeException; new approval required |
| 4. Capacity | Person B has 100 available minutes | Priority workload 135 minutes; warning of 35 minutes; availability remains 100 |

Run: `php artisan test`.
