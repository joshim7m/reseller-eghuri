---
paths:
  - bootstrap/app.php
---

# Bootstrap

## Handle PostTooLargeException with a friendly 413
PHP rejects over-limit uploads before Laravel validation with PostTooLargeException. bootstrap/app.php's withExceptions->respond() converts it to a JSON 413 {errors:{file:...}} for expectsJson requests and a back() redirect with flash('error') otherwise. Keep this handler; server php.ini upload_max_filesize/post_max_size must still be raised independently (see docs/catalog-import-export.md Operations).
