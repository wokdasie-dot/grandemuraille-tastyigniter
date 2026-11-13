# Git

This repository is the OVH deployment source. OVH clones it as-is into the `nouveau` folder. Push the deployable tree to `main`.

`vendor/` is versioned because the host does not run Composer. Do not version `.env`, `storage/`, or `gm-data/`.
