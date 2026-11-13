# Deploy Madar to Railway

Railway builds the application from the root `Dockerfile`. Do not upload `.env`;
the Docker build excludes it, and Railway injects production variables at runtime.

## 1. Create the Railway services

1. Create a Railway project from this GitHub repository.
2. Add a MySQL service to the same project.
3. In the Laravel service's **Settings → Build**, select the repository root as
   the build context. Railway detects the root `Dockerfile`.
4. Add a Railway volume to the Laravel service mounted at `/app/storage`.
   Laravel stores uploaded public and private media there; Railway's container
   filesystem is otherwise ephemeral.
5. In **Settings → Deploy**, set the **Pre-deploy Command** to:

   ```text
   php artisan migrate --force
   ```

   Railway runs the pre-deploy command once before activating each deployment.
   Keeping migrations out of the start command prevents every replica or restart
   from trying to migrate concurrently. Set the **Start Command** to:

   ```text
   /usr/bin/supervisord -n -c /etc/supervisor/conf.d/railway.conf
   ```

   This starts the web server, queue worker, and Laravel scheduler together so
   they share the mounted storage volume.

6. Set the healthcheck path to `/up`, then generate a public domain in
   **Settings → Networking**.

## 2. Configure variables

Add these in the Laravel service's **Variables** page. Replace `MySQL` in the
Railway references if the database service has a different name.

```text
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...                 # Generate once with: php artisan key:generate --show
APP_URL=https://<your-generated-domain>
DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Keep the same `APP_KEY` across deployments. Do not copy the development `.env`
or its local database credentials to Railway.

## 3. Before opening the site

- Set production mail and any required Paymob credentials in Railway Variables.
- Confirm `/up` returns successfully and inspect deployment logs for migration
  or database errors.
- Test sign-in, email delivery, a queued task, a public upload, and access
  control for private media.
- A Railway volume persists app files but is not a backup. Configure a separate
  backup destination before storing important user uploads.
- Keep the service to one replica while using local filesystem storage. The
  app's queue worker and scheduler run in the same container as the web process;
  scaling replicas would give each container its own filesystem and could run
  scheduled work more than once.
