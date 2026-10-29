# Vercel demo deployment

This project runs on Vercel's Container Runtime using FrankenPHP. The application is configured read-only in the container; its database must be hosted separately.

## 1. Create a MySQL-compatible database

TiDB Cloud Starter is one option: it supports MySQL-compatible connections and currently advertises a free allowance. Create an instance, then use its SQL editor to create a database named `swd_students` and run [`database/schema.sql`](database/schema.sql).

Do not upload the local XAMPP records unless they are confirmed synthetic and approved for public viewing. The app will show an empty list until the hosted database has approved demo records.

## 2. Import this repository into Vercel

In Vercel, choose **Add New → Project**, import `Yakubu-Yusuf13/swd-05-student-records`, and deploy. Vercel detects the root `Dockerfile` and uses its Container preset. The container uses `APP_READ_ONLY=1` by default, hiding and rejecting public create, edit, delete, and seed operations.

## 3. Add database environment variables

In the Vercel project, open **Settings → Environment Variables** and add the values provided by the database connection screen:

| Variable | Value |
| --- | --- |
| `DB_HOST` | Database hostname |
| `DB_PORT` | Database port (usually `4000` for TiDB Cloud Starter) |
| `DB_NAME` | `swd_students` |
| `DB_USER` | Database username |
| `DB_PASSWORD` | Database password |
| `DB_SSL_CA` | `/etc/ssl/cert.pem` for the container's Alpine CA bundle |
| `APP_READ_ONLY` | `1` |
| `APP_SECURE_COOKIES` | `1` |

Keep credentials in Vercel environment settings, not in Git or chat. Select the Production environment and redeploy after adding or changing variables. Restrict database access to TLS and use a database account with only the permissions the app needs.

## 4. Get the live URL

After deployment succeeds, Vercel displays a `*.vercel.app` URL. Open it and verify that the list page loads. A blank list is expected until public demo records are approved and added to the hosted database.

## Local checks

Build and run the container locally with Docker:

```powershell
docker build -t student-records-demo .
docker run --rm -p 8080:80 -e DB_HOST=... -e DB_PORT=4000 -e DB_NAME=swd_students -e DB_USER=... -e DB_PASSWORD=... -e DB_SSL_CA=/etc/ssl/cert.pem student-records-demo
```

Do not paste real database passwords into shared terminal logs. Use a local secret manager or untracked local environment file when testing.
