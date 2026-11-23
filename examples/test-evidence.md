# Test evidence

This file documents the automated acceptance scenarios and how to reproduce them. It does not replace the actual test output from the final submission environment.

## Automated scenarios

| Requirement | Test | Expected assertion |
|---|---|---|
| Complete approved post is handed over exactly once | test_complete_approved_post_is_handed_off_only_once_on_retry | Both calls return the same handoff ID; exactly one row exists; status is handed_over. |
| Missing approval blocks handoff | test_missing_approval_blocks_handoff_without_creating_a_record | RuntimeException; no handoff row. |
| Missing media blocks review | test_missing_media_reference_blocks_submission_for_review | Exact validation error; post stays draft; no approvals or handoffs. |
| Changed text requires new approval | test_text_change_after_approval_requires_new_approval | Old approval is stale; no handoff is created. |
| Gateway rejection is not success | test_rejected_gateway_does_not_mark_post_as_handed_over | RuntimeException; post remains approved; no handoff row. |
| Capacity overrun is visible | test_plan_has_three_priorities_two_reserves_and_capacity_warning | B remains at 100 available minutes; 135 planned minutes; warning and 35-minute overrun. |\n| Topics come from structured input | test_topics_are_taken_from_the_structured_input | Changing the first input topic changes the generated idea topic. |
| Text report is useful for weekly review | test_text_report_contains_schedule_status_sources_and_capacity_warnings | Report contains date, status, source ID, inspiration disclaimer and reserve assumption. |

## Run locally

    composer validate --no-check-publish
    composer install
    cp .env.example .env
    php artisan key:generate
    touch database/database.sqlite
    php artisan migrate --force
    php artisan test

## Final submission evidence

After running the commands above on the final commit, paste the real terminal summary here or attach a screenshot. Do not mark tests as passed based only on the existence of test methods.

- Commit tested: fill in after final run.
- PHP version: fill in from php -v.
- Test command: php artisan test.
- Actual result: fill in with the real PHPUnit summary.
- CI run for the final pull request: add the GitHub Actions URL after it completes.

The repository had a successful CI run on the earlier main-branch commit e87734f. That historical run is not evidence for the changes in this branch.
