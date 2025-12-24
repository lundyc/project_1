# project_1

A server-hosted web application designed to run on a Linux VPS environment.  
This repository contains **application code only**. Large media, generated assets, and runtime data are intentionally excluded from version control.

---

## 📌 Overview

`project_1` is structured for deployment on a traditional LAMP/LNMP-style stack and is intended to be:

- Lightweight in Git
- Safe to deploy on a VPS
- Separated cleanly between **code** and **data**
- Easy to extend and maintain

All large files (such as videos) are handled at runtime and stored locally on the server, not in GitHub.

---

## 📁 Project Structure

```text
project_1/
├── app/                # Application logic (API endpoints, business logic)
├── config/             # Configuration files
├── public/             # Publicly accessible files (web root)
│   └── videos/         # Runtime video storage (NOT tracked in Git)
├── storage/            # App storage (logs/cache if applicable)
├── py/                 # Python scripts / tooling (no venvs tracked)
├── migrate_videos.sh   # Utility script for video migration
├── .gitignore
├── .htaccess
└── README.md
