# Teacher Quiz Button — Findings and Fixes

This document summarizes the investigation and changes related to the teacher quiz button on the student dashboard.

## Findings

- __Active shortcode source__: The active user dashboard shortcode is loaded from `wp-content/themes/hello-theme-child-master0/functions.php` via:
  - `require_once get_stylesheet_directory() . '/includes/users/class-user-dashboard-shortcode.php';`
  - The class file `wp-content/themes/hello-theme-child-master0/includes/users/class-user-dashboard-shortcode.php` registers `[user_dashboard]`.

- __Duplicate initializations__: The shortcode was instantiated in two places:
  - In `functions.php` and also at the bottom of the class file.
  - This can cause overriding/confusion. We commented out the instantiation in `functions.php` and kept the one in the class file.

- __Wrong fallback URL__: The teacher quiz button fallback used `home_url('/quizzes/')`, sending users to the quiz archive when no teacher/quiz was found.

- __Source of button link logic__: In `User_Dashboard_Shortcode::render_dashboard()` inside:
  - `.../hello-theme-child-master0/includes/users/class-user-dashboard-shortcode.php` (around lines 395–416).

## Changes Implemented

- __Fallback link updated__:
  - Replaced both `home_url('/quizzes/')` fallbacks with `'#'` in `class-user-dashboard-shortcode.php`.
  - Paths/area: lines ~405 and ~409.

- __Hide button when none__:
  - Updated logic to render the teacher quiz button only if a latest quiz exists; otherwise the button is hidden.
  - Added debug logs to error log for visibility:
    - `[TEACHER_QUIZ_DEBUG] Student ID ... Teacher ID ...`
    - `[TEACHER_QUIZ_DEBUG] Teacher ... has N quizzes`
    - `[TEACHER_QUIZ_DEBUG] Latest quiz URL: ...`
    - or messages indicating reasons for hiding.

- __Removed duplicate instantiation__:
  - In `.../hello-theme-child-master0/functions.php` we commented out the extra `new User_Dashboard_Shortcode();` to avoid duplicate registrations.

## Current Behavior

- If the student has a teacher with at least one quiz, the button appears and links to that latest quiz permalink.
- If the student has no teacher or no teacher quizzes, the button is hidden (no `#` link shown).

## Verification Steps

1. Visit `/my-courses/` (page that contains `[user_dashboard]`).
2. Confirm:
   - Button appears and links to a specific quiz (e.g., `/quizzes/66/`) when available.
   - Button is not visible when none are available.
3. Check PHP error log for `[TEACHER_QUIZ_DEBUG]` entries to verify teacher ID, quiz count, and URL resolution.

## Notes / Additional Context

- __Groups-based approach__: There is a separate effort to fetch teacher/quiz via LearnDash groups rather than custom tables. The debug page indicates group `test` with leader ID 316 has quiz `66`. If needed, migrate `get_teacher_quizzes()` to use group leader(s) instead of `author` alone.

- __404 on `/my-courses/`__:
  - If `/my-courses/` returns 404, ensure the page exists (slug `my-courses`) and contains `[user_dashboard]`. Re-save permalinks if necessary.
  - From SQL snapshot: page ID `1229` with slug `my-courses` exists and contains `[user_dashboard]`.

## File References

- `wp-content/themes/hello-theme-child-master0/includes/users/class-user-dashboard-shortcode.php`
- `wp-content/themes/hello-theme-child-master0/functions.php`
- (Backups/old) `wp-content/themes/hello-theme-child-master/includes/users/0bu/`

## Next Steps (Optional)

- __Switch to LearnDash groups__: Resolve latest teacher quiz by group leader(s) associated with the student.
- __Tooltip on hidden case__: Optionally render a disabled button with a tooltip explaining no quiz is available.
- __Clean duplicate theme dirs__: Consolidate `hello-theme-child-master` vs `hello-theme-child-master0` to a single canonical child theme to avoid confusion.
