---
paths:
  - app/Jobs/ExportCatalogJob.php
---

# Jobs

## Keep work-dir paths relative when writing via Storage::put
In export/import jobs, write files with Storage::disk('local')->put('<path>', ...) using paths RELATIVE to the disk root (e.g. "catalog/exports/{id}/images/x.png"). Never pass absolute paths built with $disk->path() into Storage::put() — the Flysystem local adapter re-bases them under the root and the file lands in the wrong directory, silently breaking the ZIP. Use absolute paths only for fopen/ZipArchive (real filesystem ops).
