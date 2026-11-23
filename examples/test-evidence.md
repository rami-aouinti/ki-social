# Test evidence

This file records the automated acceptance scenarios and the local test run reported for the submitted application code. The local result is evidence for that run, not a claim that GitHub Actions passed on the final documentation-only commit.

## Automated scenarios

| Requirement | Test | Expected assertion |
|---|---|---|
| Complete approved post is handed over exactly once | test_complete_approved_post_is_handed_off_only_once_on_retry | Both calls return the same handoff ID; exactly one row exists; status is handed_over. |
| Missing approval blocks handoff | test_missing_approval_blocks_handoff_without_creating_a_record | RuntimeException; no handoff row. |
| Missing media blocks review | test_missing_media_reference_blocks_submission_for_review | Exact validation error; post stays draft; no approvals or handoffs. |
| Changed text requires new approval | test_text_change_after_approval_requires_new_approval | Old approval is stale; no handoff is created. |
| Gateway rejection is not success | test_rejected_gateway_does_not_mark_post_as_handed_over | RuntimeException; post remains approved; no handoff row. |
| Capacity overrun is visible | test_plan_has_three_priorities_two_reserves_and_capacity_warning | B remains at 100 available minutes; 135 planned minutes; warning and 35-minute overrun. |
| Topics come from structured input | test_topics_are_taken_from_the_structured_input | Changing the first input topic changes the generated idea topic. |
| Text report is useful for weekly review | test_text_report_contains_schedule_status_sources_and_capacity_warnings | Report contains date, status, source ID, inspiration disclaimer and reserve assumption. |

## Reproduce locally

Docker:

    docker compose exec app php artisan test

Without Docker:

    composer validate --no-check-publish
    composer install
    cp .env.example .env
    php artisan key:generate
    touch database/database.sqlite
    php artisan migrate --force
    php artisan test

## Recorded local result

The following result was observed in the local Docker environment and reported from the terminal:

- Application code commit: `0d8be0e60ca718780c7bc2decf3b451a00fd90be` (merge commit on `main`).
- PHP version: `8.4.28`.
- Test command: `docker compose exec app php artisan test`.
- Result: **8 tests passed, 30 assertions**.
- Duration: **21.01 seconds**.

The local run output showed both `WeeklyPlanningTest` (3 tests) and `WorkflowTest` (5 tests) passing.

## CI status

GitHub Actions: https://github.com/rami-aouinti/ki-social/actions

A successful CI run for the final merge commit was not independently confirmed when this evidence file was updated. Check the Actions page for the authoritative workflow result; the local PHPUnit result above is separate from CI.
